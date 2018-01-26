<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\FreeShippingWithDiscount\Plugin;

use Magento\OfflineShipping\Model\Carrier\Freeshipping as OriginalFreeShipping;
use Magento\Checkout\Model\Session;

class FreeShipping
{
    /** @var Session|\Magento\Backend\Model\Session\Quote */
    protected $session;

    /**
     * FreeShipping constructor.
     *
     * @param Session                              $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     * @param \Magento\Framework\App\State         $state
     * @param \Magento\Customer\Model\Session      $customerSession
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Model\Session $customerSession
    ) {
        if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->session = $backendQuoteSession;
        } else {
            $this->session = $checkoutSession;
        }
    }

    /**
     * @param OriginalFreeShipping $subject
     * @param callable             $proceed
     * @param                      $field
     * @return float
     */
    public function aroundGetConfigData(OriginalFreeShipping $subject, callable $proceed, $field)
    {
        $value = $proceed($field);

        if ($field != 'free_shipping_subtotal') {
            return $value;
        }

        // Add discount to the initial value from config
        // In this case base subtotal with tax should be equal or greater then value including discount
        // Lets
        // Value == 150
        // Subtotal == 140
        // Tax == 14
        // Discount == 28
        // Default condition will check it like:
        // 140 + 14 >= 150 returns true
        // modified condition will check:
        // 140 + 14 >= 150 + 28 returns false
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->session->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $baseDiscount = $shippingAddress->getBaseDiscountAmount();
        $value += abs($baseDiscount);

        return $value;
    }
}