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

namespace Payflexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\Store as Store;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{

    protected $method;

    public function __construct(PaymentHelper $paymentHelper, Store $store)
    {
        $this->method = $paymentHelper->getMethodInstance(\Payflexi\Checkout\Model\Payment\Payflexi::CODE);
        $this->store = $store;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $publicKey = $this->method->getConfigData('live_public_key');
        if ($this->method->getConfigData('test_mode')) {
            $publicKey = $this->method->getConfigData('test_public_key');
        }

        $integrationType = $this->method->getConfigData('integration_type')?: 'inline';
        $enabledGateway = $this->method->getConfigData('enabled_gateway')?: 'stripe';

        return [
            'payment' => [
                \Payflexi\Checkout\Model\Payment\Payflexi::CODE => [
                    'public_key' => $publicKey,
                    'integration_type' => $integrationType,
                    'enabled_gateway' => $enabledGateway,
                    'api_url' => $this->store->getBaseUrl() . 'rest/',
                    'integration_type_standard_url' => $this->store->getBaseUrl() . 'payflexi/payment/setup',
                    'recreate_quote_url' => $this->store->getBaseUrl() . 'payflexi/payment/recreate',
                ]
            ]
        ];
    }

    public function getStore() {
        return $this->store;
    }

    /**
     * Get secret key array for webhook process
     *
     * @return array
     */
    public function getSecretKeyArray(){
        $data = ["live" => $this->method->getConfigData('live_secret_key')];
        if ($this->method->getConfigData('test_mode')) {
            $data = ["test" => $this->method->getConfigData('test_secret_key')];
        }

        return $data;
    }

     /**
     * Get secret key for transaction processing
     *
     * @return string
     */
    public function getSecretKey(){
        $data = $this->method->getConfigData('live_secret_key');
        if ($this->method->getConfigData('test_mode')) {
            $data = $this->method->getConfigData('test_secret_key');
        }

        return $data;
    }
    
    /**
     * Get Enabled Payment Gateway
     *
     * @return string
     */
    public function getEnabledGateway(){
        $gateway = $this->method->getConfigData('enabled_gateway');
        return $gateway;
    }

}
