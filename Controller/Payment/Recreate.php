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

use Magento\Sales\Model\Order;

class Recreate extends AbstractPayflexiStandard {

    public function execute() {

        $order = $this->checkoutSession->getLastRealOrder();
       // echo "<pre>"; print_r($order->debug()); die("dead");
        if ($order->getId() && $order->getState() == Order::STATE_CANCELED) {
            $order->registerCancellation("Returned from PayFlexi without completing payment. Order cancelled.")->save();
        }

        $this->checkoutSession->restoreQuote();
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }

}
