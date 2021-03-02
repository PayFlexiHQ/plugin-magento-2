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

use Magento\Payment\Helper\Data as PaymentHelper;


abstract class AbstractPayflexiStandard extends \Magento\Framework\App\Action\Action {

    protected $quote;

    protected $resultPageFactory;

    protected $orderRepository;

    protected $orderInterface;

    protected $checkoutSession;

    protected $method;

    protected $messageManager;

    protected $configProvider;

    protected $payflexi;

    protected $eventManager;

    protected $logger;
 
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
            PaymentHelper $paymentHelper,
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
        $this->method = $paymentHelper->getMethodInstance(\Payflexi\Checkout\Model\Payment\Payflexi::CODE);
        $this->messageManager = $messageManager;
        $this->configProvider = $configProvider;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->logger->setHandlers ( [$this->handler] );

        $this->payflexi = $this->initPayflexiPay();

        parent::__construct($context);
    }

     /**
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initPayflexiPay() {
        $secretKey = $this->method->getConfigData('live_secret_key');
        if ($this->method->getConfigData('test_mode')) {
            $secretKey = $this->method->getConfigData('test_secret_key');
        }
        if (!is_string($secretKey) || !(substr($secretKey, 0, 3)==='pf_')) {
            throw new \Magento\Framework\Exception\LocalizedException(__('A Valid Payflexi Secret Key must start with \'sk_\'.'));
        }
        return $secretKey;
    }

    protected function redirectToFinal($successFul = true, $message="") {
        if($successFul){
            if($message) $this->messageManager->addSuccessMessage(__($message));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        } else {
            if($message) $this->messageManager->addErrorMessage(__($message));
            $order = $this->orderRepository->get($this->checkoutSession->getLastOrderId());
            if ($order) {
                $order->cancel();
                $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, __('Canceled by customer.'));
                $order->save();
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
    }
    /**
     * Return checkout quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

}
