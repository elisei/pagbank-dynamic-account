<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Model\Console\Command\Basic;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use O2TI\PagBankDynamicAccount\Model\Api\Credential;
use O2TI\PagBankDynamicAccount\Model\Console\Command\AbstractModel;
use O2TI\PagBankDynamicAccount\Helper\Data;

/**
 * Class Refresh Token.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Refresh extends AbstractModel
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Credential
     */
    protected $credential;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param State                 $state
     * @param ScopeConfigInterface  $scopeConfig
     * @param ConfigBase            $configBase
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param Credential            $credential
     * @param Data                  $helper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        State $state,
        ScopeConfigInterface $scopeConfig,
        ConfigBase $configBase,
        StoreManagerInterface $storeManager,
        Json $json,
        Credential $credential,
        Data $helper
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->state = $state;
        $this->scopeConfig = $scopeConfig;
        $this->configBase = $configBase;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->credential = $credential;
        $this->helper = $helper;
    }

    /**
     * Command Preference.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function newToken($storeId = null)
    {
        $newData = [];
        $currentAccounts = $this->helper->getConfigDynamicAccounts($storeId);

        foreach ($currentAccounts as $indice => $accountDetail) {
            if (isset($accountDetail['refresh_token'])) {
                $newData = $this->refreshToken($accountDetail);

                if ($newData && $newData['account_id'] === $accountDetail['account_id']) {
                    $currentAccounts[$indice] = array_replace($currentAccounts[$indice], $newData);
                    $this->credential->setRefreshNewConfigs($currentAccounts);
                    $this->cacheTypeList->cleanType('config');
                    $message = __('Refresh token successful');
                    $this->writeln('<info>' . $message . '</info>');
                }
            }
        }

        $this->writeln(__('Finished'));

        return 1;
    }

    /**
     * Refresh Token.
     *
     * @param array $accountDetail
     *
     * @return array
     */
    public function refreshToken($accountDetail)
    {
        $newToken = $this->credential->generateNewOAuth($accountDetail['refresh_token']);
        $newToken = $this->json->unserialize($newToken);
        $newConfig = [];
        if (isset($newToken['access_token'])) {

            $publicKey = $this->credential->getPublicKey($newToken['access_token'], 0);
            $publicKey = $this->json->unserialize($publicKey);

            $newConfig = [
                'status'        => $accountDetail['status'],
                'account_id'    => $newToken['account_id'],
                'access_token'  => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'],
                'public_key'    => $publicKey['public_key'],
            ];
        }

        if (isset($newToken['error_messages'])) {
            foreach ($newToken['error_messages'] as $errors) {
                $message = __('token update returns errors code: %1', $errors['code']);
                $this->writeln('<error>'.$message.'</error>');
            }
        }

        return $newConfig;
    }
}
