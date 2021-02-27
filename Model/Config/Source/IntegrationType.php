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

namespace Payflexi\Checkout\Model\Config\Source;

/**
 * Option source for Integration types
 *
 * inline    : Popup type
 * standard  : Redirecting type
 *
 */
class IntegrationType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'inline', 'label' => __('Inline - (Popup)')], ['value' => 'standard', 'label' => __('Standard - (Redirect)')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ["inline" => __('Inline - (Popup)'), 'standard' => __('Standard - (Redirect)')];
    }
}
