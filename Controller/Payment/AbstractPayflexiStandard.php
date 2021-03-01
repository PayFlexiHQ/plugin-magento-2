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

use Magento\Payment\Helper\Data as PaymentHelper;


abstract class AbstractPayflexiStandard extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;

    /**
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     *
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $orderInterface;
    protected $checkoutSession;
    protected $method;
    protected $messageManager;

    /**
     *
     * @var \Payflexi\Checkout\Model\Ui\ConfigProvider
     */
    protected $configProvider;

    /**
     *
     * @var Payflexi
     */
    protected $payflexi;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     *
     * @var \Magento\Framework\App\Request\Http 
     */
    protected $request;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
            \Magento\Sales\Api\Data\OrderInterface $orderInterface,
            \Magento\Checkout\Model\Session $checkoutSession,
            PaymentHelper $paymentHelper,
            \Magento\Framework\Message\ManagerInterface $messageManager,
            \Payflexi\Checkout\Model\Ui\ConfigProvider $configProvider,
            \Magento\Framework\Event\Manager $eventManager,
            \Magento\Framework\App\Request\Http $request,
            \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;
        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;
        $this->method = $paymentHelper->getMethodInstance(\Payflexi\Checkout\Model\Payment\Payflexi::CODE);
        $this->messageManager = $messageManager;
        $this->configProvider = $configProvider;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->logger = $logger;

        $this->payflexi = $this->initPayflexiPay();

        parent::__construct($context);
    }

     /**
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initPayflexiPay() {
        $secretKey = $this->method->getConfigData('live_secret_key');
        if ($this->method->getConfigData('test_mode')) {
            $secretKey = $this->method->getConfigData('test_secret_key');
        }
        if (!is_string($secretKey) || !(substr($secretKey, 0, 3)==='pf_')) {
            throw new \Magento\Framework\Exception\LocalizedException(__('A Valid Payflexi Secret Key must start with \'sk_\'.'));
        }
        return $secretKey;
    }

    protected function redirectToFinal($successFul = true, $message="") {
        if($successFul){
            if($message) $this->messageManager->addSuccessMessage(__($message));
            return $this->_redirect('checkout/onepage/success');
        } else {
            if($message) $this->messageManager->addErrorMessage(__($message));
            return $this->_redirect('checkout/onepage/failure');
        }
    }
}
