<?php
/*
 * Payflexi Flexible Checkout payment gateway Magento2 extension
 *
 * Copyright (c) 2019 Payflexi.
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Payflexi <hello@payflexi.co>
*/

namespace Payflexi\Checkout\Model\Payment;

/**
 * Class Payflexi
 * @package Payflexi\Checkout\Model
 */
class Payflexi extends \Magento\Payment\Model\Method\AbstractMethod
{

    const CODE = 'payflexi_checkout';

    protected $_code = self::CODE;
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
