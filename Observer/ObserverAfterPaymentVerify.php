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
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Payflexi\Checkout\Model\LogHandler $handler
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->logger->setHandlers ( [$this->handler] );
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getPayflexiOrder();
        $order_2 = $observer->getData('payflexi_order');
 
        $this->logger->info('Order 1', (array)$order);

        $this->logger->info('Order 2', (array)$order_2);

        $order_amount = (int)round($order->getGrandTotal(), 2);
        $transaction_amount = $observer->getData('payflexi_transaction_txn_amount');
        $transaction_status = $observer->getData('payflexi_transaction_status');
        $transaction_initial_reference = $observer->getData('payflexi_transaction_initial_reference');
        $transaction_reference = $observer->getData('payflexi_transaction_reference');

        if ($order && $order->getStatus() == "pending" && $transaction_status == "approved") {
            if ( $transaction_amount < $order_amount ) {

                $order->setState(Order::STATE_PENDING_PAYMENT)
                        ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, __("Payflexi Payment Verified and Order is being processed"), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();


            }else{
                $order->setState(Order::STATE_PROCESSING)
                        ->addStatusToHistory(Order::STATE_PROCESSING, __("Payflexi Payment Verified and Order is being processed"), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();
            }
        }

        if ($order && $order->getStatus() == "pending" && $transaction_status == "failed") {
            $order->setState(Order::STATE_CANCELED)
                    ->addStatusToHistory(Order::STATE_CANCELED, __("Payflexi Payment Failed and Order is cancelled"), true)
                    ->setCustomerNoteNotify(false);
            $order->save();
        }
    }
}
