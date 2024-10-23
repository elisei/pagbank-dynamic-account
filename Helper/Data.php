<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Math\Random;
use PagBank\PaymentMagento\Gateway\Config\Config;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $configBase;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param ScopeConfigInterface  $scopeConfig
     * @param Config                $configBase
     * @param Random                $mathRandom
     * @param SerializerInterface   $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $configBase,
        Random $mathRandom,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configBase = $configBase;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
    }

    /**
     * Get Config Data.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getConfigDynamicAccounts($storeId = null)
    {
        $environment = $this->configBase->getAddtionalValue('environment', $storeId);
        $pathPattern = 'dynamic_accounts_%s';
        $data = [];

        $sellers = $this->configBase->getAddtionalValue(
            sprintf($pathPattern, $environment)
        );

        if ($sellers) {
            $data = $this->serializer->unserialize($sellers);
        }

        return $data;
    }

    /**
     * Get Random Seller.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     *
     * @return array|null
     */
    public function getRandomSeller($cart)
    {
        $storeId = $cart->getStoreId();
        $sellers = $this->getConfigDynamicAccounts($storeId);

        if (empty($sellers)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No sellers available'));
        }

        // Filtra sellers com status 1
        $activeSellers = array_filter($sellers, function ($seller) {
            return isset($seller['status']) && (bool)$seller['status'] == 1;
        });

        if (empty($activeSellers)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No active sellers available'));
        }

        $randomIndex = $this->mathRandom->getRandomNumber(0, count($activeSellers) - 1);
        return $activeSellers[array_keys($activeSellers)[$randomIndex]];
    }

    /**
     * Get oAuth By Account Id.
     *
     * @param string $accountId
     *
     * @return string|null
     */
    public function getOauthByAccountId($accountId)
    {
        $sellers = $this->getConfigDynamicAccounts();

        foreach ($sellers as $seller) {
            if (isset($seller['account_id']) && $seller['account_id'] === $accountId) {
                return $seller['access_token'];
            }
        }

        return null;
    }

    /**
     * Get Api Headers By Account Id.
     *
     * @param string $accountId
     * @param int|null $storeId
     *
     * @return array
     */
    public function getApiHeadersByAccountId($accountId, $storeId = null)
    {
        $oAuth = $this->getOauthByAccountId($accountId);

        return [
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Bearer '.$oAuth,
            'x-api-version'     => '4.0'
        ];
    }
}
