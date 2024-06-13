<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Model\Api;

use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\LaminasClient;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;

/**
 * Class Credential - Get access credential on PagBank.
 */
class Credential
{
    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * Constructor.
     *
     * @param Config                $resourceConfig
     * @param StoreManagerInterface $storeManager
     * @param ConfigBase            $configBase
     * @param ClientFactory         $httpClientFactory
     * @param SerializerInterface   $serializer
     * @param Random                $mathRandom
     */
    public function __construct(
        Config $resourceConfig,
        StoreManagerInterface $storeManager,
        ConfigBase $configBase,
        ClientFactory $httpClientFactory,
        SerializerInterface $serializer,
        Random $mathRandom
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->serializer = $serializer;
        $this->mathRandom = $mathRandom;
    }

    /**
     * Set New Configs.
     *
     * @param array $configs
     * @param bool  $storeIdIsDefault
     * @param int   $webSiteId
     * @param int   $storeId
     *
     * @return void
     */
    public function setNewConfigs(
        $configs,
        bool $storeIdIsDefault,
        int $webSiteId = 0,
        int $storeId = 0
    ) {
        $scope = ScopeInterface::SCOPE_WEBSITES;

        $environment = $this->configBase->getEnvironmentMode($storeId);

        $basePathConfig = 'payment/pagbank_paymentmagento/%s_%s';

        $valueConfig = sprintf('%s_%s', 'dynamic_accounts', $environment);

        $currentValue = $this->configBase->getAddtionalValue($valueConfig);

        $currentData = [];

        if ($currentValue) {
            $currentData = $this->serializer->unserialize($currentValue);
        }
        
        $uniqueKey = '_' . $this->mathRandom->getUniqueHash('_');

        $currentData[$uniqueKey] = $configs;

        $updatedSerializedValue = $this->serializer->serialize($currentData);

        if ($storeIdIsDefault) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }

        $this->resourceConfig->saveConfig(
            sprintf($basePathConfig, 'dynamic_accounts', $environment),
            $updatedSerializedValue,
            $scope,
            $webSiteId
        );
    }

    /**
     * Set Refresh New Configs.
     *
     * @param array $configs
     *
     * @return void
     */
    public function setRefreshNewConfigs(
        $configs
    ) {
        $scope = ScopeInterface::SCOPE_WEBSITES;
        $currentData = [];

        $environment = $this->configBase->getEnvironmentMode();

        $basePathConfig = 'payment/pagbank_paymentmagento/%s_%s';

        $updatedSerializedValue = $this->serializer->serialize($configs);

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $this->resourceConfig->saveConfig(
            sprintf($basePathConfig, 'dynamic_accounts', $environment),
            $updatedSerializedValue,
            $scope
        );
    }

    /**
     * Get Authorize.
     *
     * @param int    $storeId
     * @param string $code
     * @param string $codeVerifier
     *
     * @return json
     */
    public function getAuthorize($storeId, $code, $codeVerifier)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $headers = $this->configBase->getPubHeader($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $uri = $url.'oauth2/token';

        $store = $this->storeManager->getStore('admin');
        $storeCode = '/'.$store->getCode().'/';
        $redirectUrl = (string) $store->getUrl('o2ti/system_config/oauth', [
            'website'       => $storeId,
            'code_verifier' => $codeVerifier,
        ]);

        $search = '/'.preg_quote($storeCode, '/').'/';
        $redirectUrl = preg_replace($search, '/', $redirectUrl, 0);

        $data = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUrl,
            'code_verifier' => $codeVerifier,
        ];

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($this->serializer->serialize($data));

        $send = $client->send();

        return $send->getBody();
    }

    /**
     * Get Public Key.
     *
     * @param string $oAuth
     * @param int    $storeId
     *
     * @return string
     */
    public function getPublicKey($oAuth, $storeId)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $uri = $url.'public-keys/';
        $apiConfigs = $this->configBase->getApiConfigs();

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$oAuth,
        ];

        $data = ['type' => 'card'];

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($this->serializer->serialize($data));

        $send = $client->send();

        return $send->getBody();
    }

    /**
     * Generate New oAuth.
     *
     * @param string $currentRefresh
     * @param int $storeId
     *
     * @return string
     */
    public function generateNewOAuth($currentRefresh, int $storeId = 0)
    {
        $url = $this->configBase->getApiUrl($storeId);
        $uri = $url.'oauth2/refresh';
        $header = $this->configBase->getPubHeader($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();

        $data = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $currentRefresh,
        ];

        $payload = $this->serializer->serialize($data);

        /** @var LaminasClient $client */
        $client = $this->httpClientFactory->create();

        $client->setUri($uri);
        $client->setHeaders($header);
        $client->setMethod(Request::METHOD_POST);
        $client->setOptions($apiConfigs);
        $client->setRawBody($payload);
        $send = $client->send();

        return $send->getBody();
    }
}
