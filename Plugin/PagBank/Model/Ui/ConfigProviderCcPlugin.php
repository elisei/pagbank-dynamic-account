<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Plugin\PagBank\Model\Ui;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Api\Data\CartInterface;
use O2TI\PagBankDynamicAccount\Helper\Data;
use PagBank\PaymentMagento\Model\Ui\ConfigProviderCc;

class ConfigProviderCcPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Construct.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CartInterface $cart
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CartInterface $cart,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->helper = $helper;
    }

    /**
     * Around plugin for getConfig method
     *
     * @param ConfigProviderCc $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetConfig(ConfigProviderCc $subject, callable $proceed)
    {
        $result = $proceed();
        $storeId = $this->cart->getStoreId();

        // Accounts vai ser sorteado no helper passando o cart.
        // assim quiser pode implementar nova lógica baseado no carrinho.
        $sellerData = $this->helper->getRandomSeller($this->cart);
        
        $accountsEnabled = $this->scopeConfig->getValue(
            'payment/pagbank_paymentmagento/dynamic_account_enable',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result['o2ti_pagbank_dynamic_account'] = [
            'enable'     => (bool) $accountsEnabled,
            'account_id' => $sellerData['account_id'],
        ];

        if (isset($result['payment']['pagbank_paymentmagento_cc']['public_key']) && $accountsEnabled) {
            $result['payment']['pagbank_paymentmagento_cc']['public_key'] = $sellerData['public_key'];
        }
      
        return $result;
    }
}
