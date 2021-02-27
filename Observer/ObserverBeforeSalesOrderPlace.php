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

namespace Payflexi\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class ObserverBeforeSalesOrderPlace implements ObserverInterface
{


    public function __construct() {

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Observer execution code...
        /** @var \Magento\Sales\Model\Order $order **/
        $order = $observer->getEvent()->getOrder();

        if ($order) {
            $order->setCanSendNewEmailFlag(false)
                    ->setCustomerNoteNotify(false);
        }
    }
}
