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

class Callback extends AbstractPayflexiStandard
{

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        if (isset($_GET['pf_cancelled'])) {
            return $this->redirectToFinal(false, 'Payment was cancelled by customer');
        }

        if (isset($_GET['pf_declined'])) {
            return $this->redirectToFinal(false, 'Payment declined by PayFlexi gateway');
        }

        if (isset($_GET['pf_approved'])) {
           
            $reference = $this->request->get('pf_approved');

            $this->logger->info('Request', ['Reference' => $reference]);
            $message = "";

            if (!$reference) {
                return $this->redirectToFinal(false, "No reference supplied");
            }

            try {
               
                $transaction = new \stdClass();

                $url = 'https://api.payflexi.test/merchants/transactions/' . rawurlencode($reference);

                // set url
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $this->secretKey
                ));

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
                    $transaction->error = "cURL said:" . curl_error($ch);
                    curl_close($ch);
                } else {
                    curl_close($ch);
                    $body = json_decode($response, true);
                    if ($body->errors == true) {
                        $transaction->error = "Payflexi API said: " . $body->message;
                    } else {
                        $transaction = $body->data;
                    }
                }

                $reference = explode('-', $transaction->reference);
                $order_id = $reference[1];
                //$reference = ($reference[0]) ?: 0;

                $order = $this->orderInterface->loadByIncrementId($order_id);

                if ($order && $order_id === $order->getIncrementId()) {
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
}
