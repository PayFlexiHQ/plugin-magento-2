<?php
/*
 * Payflexi Flexible Checkout payment gateway Magento2 extension
 *
 * Copyright (c) 2021 Payflexi.
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Payflexi <hello@payflexi.co>
*/

namespace Payflexi\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;

class Webhook extends Action
{
    protected $resultPageFactory;

    /**
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     *
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $orderInterface;
    protected $checkoutSession;
    protected $method;
    protected $messageManager;

    /**
     *
     * @var \Payflexi\Checkout\Model\Ui\ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     *
     * @var \Magento\Framework\App\Request\Http 
     */
    protected $request;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Payflexi\Checkout\Model\Ui\ConfigProvider $configProvider,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\App\Request\Http $request,
        \Psr\Log\LoggerInterface $logger,
        \Payflexi\Checkout\Model\LogHandler $handler
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;
        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->configProvider = $configProvider;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->logger->setHandlers ( [$this->handler] );

        parent::__construct($context);
    }

    public function execute() {

        $this->logger->info("PayFlexi Webhook processing started.");

        $finalMessage = "failed";
        
        $resultFactory = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);

        try {

            $input = file_get_contents('php://input');

            $secretKey = $this->configProvider->getSecretKey();

            if(!$_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] || ($_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] !== hash_hmac('sha512', $input, $secretKey))){
                return;
            }

            $event = json_decode($input);
            
            http_response_code(200);
            /* It is a important to log all events received. Add code *
            * here to log the signature and body to db or file       */
            $this->logger->debug('PAYFLEXI_LOG', (array)$event);

            // Do something with $event->obj
            // Give value to your customer but don't give any output
            // Remember that this is a call from PayFlexi's servers and
            // Your customer is not seeing the response here at all
            switch ($event->event) {

                case 'transaction.approved':
                    if ('approved' === $event->data->status) {
                        $ch = curl_init();
                        $transaction = new \stdClass();

                        // set url
                        curl_setopt($ch, CURLOPT_URL, "https://api.payflexi.test/merchants/transactions/" . rawurlencode($event->data->reference));

                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Authorization: Bearer '. $secretKey
                        ));

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HEADER, false);

                        //Remove for Product
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                        // Make sure CURL_SSLVERSION_TLSv1_2 is defined as 6
                        // cURL must be able to use TLSv1.2 to connect to Payflexi servers
                        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
                            define('CURL_SSLVERSION_TLSv1_2', 6);
                        }
                        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
                        // exec the cURL
                        $response = curl_exec($ch);

                        // should be 0
                        if (curl_errno($ch)) {
                            // curl ended with an error
                            $transaction->error = "cURL said:" . curl_error($ch);
                            curl_close($ch);
                        } else {

                            //close connection
                            curl_close($ch);

                            // Then, after your curl_exec call:
                            $body = json_decode($response);

                            if($body->errors == true){
                                // paystack has an error message for us
                                $transaction->error = "Payflexi API said: " . $body->message;
                            } else {
                                // get body returned by Paystack API
                                $transaction = $body->data;

                            }
                        }

                        $reference = $transaction->reference;

                        $order = $this->orderInterface->loadByIncrementId($reference);

                        if((!$order || !$order->getId()) && isset($event->data->meta->quoteId)){

                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $searchCriteriaBuilder = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder');
                            $searchCriteria = $searchCriteriaBuilder->addFilter('quote_id', $event->data->meta->quoteId, 'eq')->create();
                            $items = $this->orderRepository->getList($searchCriteria);
                            if($items->getTotalCount() == 1){
                                $order = $items->getFirstItem();
                            }

                        }

                        if ($order && $order->getId()) {
                            // dispatch the `payment_verify_after` event to update the order status
                            $this->eventManager->dispatch('payflexi_payment_verify_after', [
                                "payflexi_order" => $order,
                            ]);

                            $resultFactory->setContents("success");
                            return $resultFactory;
                        }
                    }
                break;
        }

        } catch (Exception $exc) {
            $finalMessage = $exc->getMessage();
        }
        
        $resultFactory->setContents($finalMessage);
        return $resultFactory;
    }

}
