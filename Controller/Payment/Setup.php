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

class Setup extends AbstractPayflexiStandard {

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {

        $message = '';
        $order = $this->orderInterface->loadByIncrementId($this->checkoutSession->getLastRealOrder()->getIncrementId());
        if ($order && $this->method->getCode() == $order->getPayment()->getMethod()) {

            try {
                return $this->processAuthorization($order);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $order->addStatusToHistory($order->getStatus(), $message);
                $this->orderRepository->save($order);
            }
        }

        $this->redirectToFinal(false, $message);
    }

    protected function processAuthorization(\Magento\Sales\Model\Order $order) {


        $ch = curl_init();
        $transaction = new \stdClass();
        $transaction->orderId = $order->getId();

        // set url
        curl_setopt($ch, CURLOPT_URL, "https://api.payflexi.test/merchants/transactions");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '. $this->configProvider->getSecretKey(),
            'Content-Type: application/json'
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'reference' => $order->getIncrementId(),
            'amount' => (int)round($order->getGrandTotal(), 2),
            'email' => $order->getCustomerEmail(),
            'name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'callback_url' => $this->configProvider->store->getBaseUrl() . "payflexi/checkout/payment/callback",
            'domain' => 'global',
            'metadata' => json_encode([
                "order_id" => $transaction->orderId,
            ]),
        ]));

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
            if(!$body->status){
                // payflexi has an error message for us
                $transaction->error = "Payflexi API said: " . $body->message;
            } else {
                // get body returned by Paystack API
                $transaction = $body->data;
            }
        }

        $redirectFactory = $this->resultRedirectFactory->create();
        $redirectFactory->setUrl($transaction->checkoutUrl);


        return $redirectFactory;
    }

}
