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

namespace Payflexi\Checkout\Plugin;

class CsrfValidatorSkip {
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
         /* Magento 2.1.x, 2.2.x */
        if ($request->getModuleName() == 'payflexi_checkout') {
            return; // Skip CSRF check
        }

        /* Magento 2.3.x */
        if (strpos($request->getOriginalPathInfo(), 'payflexi') !== false) {
            return; // Skip CSRF check
        }

        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
    
}