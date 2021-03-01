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

class Callback extends AbstractPayflexiStandard {

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {

        $reference = $this->request->get('reference');
        $message = "";

        if (!$reference) {
            return $this->redirectToFinal(false, "No reference supplied");
        }

        try {
            $ch = curl_init();
            $transaction = new \stdClass();

            // set url
            curl_setopt($ch, CURLOPT_URL, "https://api.payflexi.test/merchants/transactions/" . rawurlencode($reference));

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '. $this->secretKey
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

            $reference = explode('_', $transaction->reference, 2);
            $reference = ($reference[0])?: 0;

            $order = $this->orderInterface->loadByIncrementId($reference);

            if ($order && $reference === $order->getIncrementId()) {
                // dispatch the `payment_verify_after` event to update the order status

                $this->eventManager->dispatch('payflexi_payment_verify_after', [
                    "payflexi_order" => $order,
                ]);

                return $this->redirectToFinal(true);
            }

            $message = "Invalid reference or order number";

        } catch (\Exception $e) {
            $message = $e->getMessage();

        } catch (Exception $e) {
            $message = $e->getMessage();

        }

        return $this->redirectToFinal(false, $message);
    }

}
