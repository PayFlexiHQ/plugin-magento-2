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

class ObserverAfterPaymentVerify implements ObserverInterface
{
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\OrderFactory $_orderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session $_checkoutSession
     */
    protected $_checkoutSession;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Observer execution code...
        /** @var \Magento\Sales\Model\Order $order **/
        $order = $observer->getPayflexiOrder();
        $payflexi_transaction_status = $observer->getData('payflexi_tranaction_status');


        if ($order && $order->getStatus() == "pending" && $payflexi_transaction_status == "approved") {
            // sets the status to processing since payment has been received
            $order->setState(Order::STATE_PROCESSING)
                    ->addStatusToHistory(Order::STATE_PROCESSING, __("Payflexi Payment Verified and Order is being processed"), true)
                    ->setCanSendNewEmailFlag(true)
                    ->setCustomerNoteNotify(true);
            $order->save();
        }

        if ($order && $order->getStatus() == "pending" && $payflexi_transaction_status == "failed") {
            // sets the status to cancelled since payment failed
            $order->setState(Order::STATE_CANCELED)
                    ->addStatusToHistory(Order::STATE_CANCELED, __("Payflexi Payment Failed and Order is cancelled"), true)
                    ->setCustomerNoteNotify(false);
            $order->save();
        }
    }
}
