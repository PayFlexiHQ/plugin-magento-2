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
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory
     */
    protected $invoiceCollectionFactory;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
    * @var \Magento\Sales\Model\Service\InvoiceService
    */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
    * @var \Magento\Sales\Api\OrderRepositoryInterface
    */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

   protected $logger;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
       \Payflexi\Checkout\Model\LogHandler $handler
    ) {
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->logger->setHandlers ( [$this->handler] );
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getPayflexiOrder();

        $order_amount = (int)round($order->getGrandTotal(), 2);
        $transaction_amount = (int)$observer->getData('payflexi_transaction_txn_amount');
        $transaction_total_amount_paid = (int)$observer->getData('payflexi_transaction_total_amount_paid');
        $transaction_status = $observer->getData('payflexi_transaction_status');
        $transaction_initial_reference = $observer->getData('payflexi_transaction_initial_reference');
        $transaction_reference = $observer->getData('payflexi_transaction_reference');

        if ($order && $order->getStatus() == "pending" && ($transaction_reference === $transaction_initial_reference || empty($transaction_initial_reference))  && $transaction_status == "approved") {
            
            $orderId = $order->getId();

            if ($transaction_amount < $order_amount) {

                $this->createInvoice($orderId, $transaction_amount); 

                $order->setTotalPaid($transaction_amount);
                $order->setBaseTotalPaid($transaction_amount);
                $order->setBaseTotalDue($order_amount - $transaction_amount);

                $order->setState(Order::STATE_PENDING_PAYMENT)
                        ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, __('Payflexi upfront payment successful with transaction ID #%1.', $transaction_reference), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();

            }

            if ($transaction_amount >= $order_amount) {

                $order->setTotalPaid($transaction_amount);
                $order->setBaseTotalPaid($transaction_amount);
                $order->setBaseTotalDue(0.00);

                $order->setState(Order::STATE_PROCESSING)
                        ->addStatusToHistory(Order::STATE_PROCESSING, __('Payflexi one-time payment successful with transaction ID #%1.', $transaction_reference), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();

                $this->orderSender->send($order, true);
            }

        }

        if ($order && $order->getStatus() == "pending_payment" && $transaction_reference !== $transaction_initial_reference && !empty($transaction_initial_reference)  && $transaction_status == "approved") {
            
            $orderId = $order->getId();

            if ($transaction_total_amount_paid < $order_amount) {

                $this->createInvoice($orderId, $transaction_amount); 
                
                $order->setTotalPaid($transaction_total_amount_paid);
                $order->setBaseTotalPaid($transaction_total_amount_paid);

                $order->setState(Order::STATE_PENDING_PAYMENT)
                        ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, __('Payflexi instalment payment successful with transaction ID #%1.', $transaction_reference), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();

            }

            if ($transaction_total_amount_paid >= $order_amount) {

                $this->createInvoice($orderId, $transaction_amount); 

                $order->setTotalPaid($transaction_total_amount_paid);
                $order->setBaseTotalPaid($transaction_total_amount_paid);

                $order->setState(Order::STATE_PROCESSING)
                        ->addStatusToHistory(Order::STATE_PROCESSING, __('Payflexi final instalment payment successful with transaction ID #%1.', $transaction_reference), true)
                        ->setCanSendNewEmailFlag(true)
                        ->setCustomerNoteNotify(true);
                $order->save();

                $this->orderSender->send($order, true);

            }

        }


        if ($order && $order->getStatus() == "pending" && ($transaction_reference === $transaction_initial_reference || empty($transaction_initial_reference)) && $transaction_status == "failed") {

            $order->setState(Order::STATE_CANCELED)
                ->addStatusToHistory(Order::STATE_CANCELED, __("Payflexi Payment Failed and Order is cancelled"), true)
                ->setCustomerNoteNotify(false);
            $order->save();

        }

    }


    protected function createInvoice($orderId, $transaction_amount)
    {
        try 
        {
            $order = $this->orderRepository->get($orderId);

            if ($order)
            {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->setSubtotal($transaction_amount);
                $invoice->setBaseSubtotal($transaction_amount);
                $invoice->setGrandTotal($transaction_amount);
                $invoice->setBaseGrandTotal($transaction_amount);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();

                return $invoice;
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

}
