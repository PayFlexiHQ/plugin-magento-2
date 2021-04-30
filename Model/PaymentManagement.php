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

namespace Payflexi\Checkout\Model;

use Exception;
use Magento\Payment\Helper\Data as PaymentHelper;
use Payflexi\Checkout\Model\Payment\Payflexi;

class PaymentManagement implements \Payflexi\Checkout\Api\PaymentManagementInterface
{

    protected $payflexiPaymentInstance;
    protected $orderInterface;
    protected $checkoutSession;
     /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    public function __construct(
        PaymentHelper $paymentHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Checkout\Model\Session $checkoutSession

    ) {
        $this->eventManager = $eventManager;
        $this->payflexiPaymentInstance = $paymentHelper->getMethodInstance(Payflexi::CODE);
        $this->logger = $logger;
        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;

        $this->secretKey = $this->payflexiPaymentInstance->getConfigData('live_secret_key');
        if ($this->payflexiPaymentInstance->getConfigData('test_mode')) {
            $this->secretKey = $this->payflexiPaymentInstance->getConfigData('test_secret_key');
        }
    }

    /**
     * @param string $reference
     * @return bool
     */
    public function verifyPayment($reference)
    {
        $ref = explode('_-~-_', $reference);
        $reference = $ref[0];
        $quoteId = $ref[1];

        try {
            
            $transaction = new \stdClass();
            
            //cURL must be able to use TLSv1.2 to connect to Payflexi servers
            if (!defined('CURL_SSLVERSION_TLSv1_2')) {
                define('CURL_SSLVERSION_TLSv1_2', 6);
            }

            $url = 'https://api.payflexi.test/merchants/transactions/' . rawurlencode($reference);

            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '. $this->secretKey
                ),
            ));
    
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $transaction->error = "cURL said:" . curl_error($ch);
                curl_close($ch);
            } else {
                curl_close($ch);
                $body = json_decode($response);
                if($body->errors == true){
                    $transaction->error = "Payflexi API Response Error: " . $body->message;
                } else {
                    $transaction = $body->data;
                }
            }

            $order = $this->getOrder();

            if ($order && $order->getQuoteId() === $quoteId && $transaction->meta->quoteId === $quoteId) {
                // dispatch the `payflexi_payment_verify_after` event to update the order status
                $this->eventManager->dispatch('payflexi_payment_verify_after', [
                    'payflexi_order' => $order,
                    'payflexi_transaction_reference' => $reference,
                    'payflexi_transaction_txn_amount' => $transaction->txn_amount,
                    'payflexi_transaction_total_amount_paid' => $transaction->txn_amount,
                    'payflexi_transaction_status' => $transaction->status,
                    'payflexi_transaction_initial_reference' => '',
                ]);

                return json_encode($transaction);
            }
        } catch (Exception $e) {
            return json_encode([
                'status'=>0,
                'message'=>$e->getMessage()
            ]);
        }
        return json_encode([
            'status'=>0,
            'message'=>"quoteId doesn't match transaction"
        ]);
    }

    /**
     * Loads the order based on the last real order
     * @return boolean
     */
    private function getOrder()
    {
        // get the last real order id
        $lastOrder = $this->checkoutSession->getLastRealOrder();
        if($lastOrder){
            $lastOrderId = $lastOrder->getIncrementId();
        } else {
            return false;
        }

        if ($lastOrderId) {
            // load and return the order instance
            return $this->orderInterface->loadByIncrementId($lastOrderId);
        }
        return false;
    }

}
