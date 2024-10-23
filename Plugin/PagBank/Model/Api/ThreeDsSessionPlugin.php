<?php

namespace O2TI\PagBankDynamicAccount\Plugin\PagBank\Model\Api;

use Magento\Framework\Session\SessionManager;
use O2TI\PagBankDynamicAccount\Helper\Data;
use Laminas\Http\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PagBank\PaymentMagento\Gateway\Config\Config as ConfigBase;
use PagBank\PaymentMagento\Model\Api\ThreeDsSession;

class ThreeDsSessionPlugin
{
    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param SessionManager $session
     * @param Data $helper
     * @param ClientFactory $httpClientFactory
     * @param ConfigBase $configBase
     * @param Json $json
     */
    public function __construct(
        SessionManager $session,
        Data $helper,
        ClientFactory $httpClientFactory,
        ConfigBase $configBase,
        Json $json
    ) {
        $this->session = $session;
        $this->helper = $helper;
        $this->httpClientFactory = $httpClientFactory;
        $this->configBase = $configBase;
        $this->json = $json;
    }

    /**
     * Around plugin for getSessionInPagBank method.
     *
     * @param ThreeDsSession $subject
     * @param callable $proceed
     * @return array|null
     */
    public function aroundGetSessionInPagBank(ThreeDsSession $subject, callable $proceed)
    {
        // Obtém o store ID
        $storeId = null;

        // Recupera o Seller Account ID da sessão
        $sellerAccountId = $this->session->getSellerAccountId();

        // Se o Seller Account ID estiver disponível, utiliza o helper para obter os headers
        if ($sellerAccountId) {
            $headers = $this->helper->getApiHeadersByAccountId($sellerAccountId, $storeId);
        } else {
            // Caso contrário, chama o método original
            return $proceed();
        }

        // Resto da implementação original, com headers alterados
        /** @var \Laminas\Http\Client $client */
        $client = $this->httpClientFactory->create();
        $url = $this->configBase->getApiSDKUrl($storeId);
        $apiConfigs = $this->configBase->getApiConfigs();
        $uri = $url . 'checkout-sdk/sessions';

        try {
            $client->setUri($uri);
            $client->setHeaders($headers);
            $client->setMethod(\Laminas\Http\Request::METHOD_POST);
            $client->setOptions($apiConfigs);
            $responseBody = $client->send()->getBody();

            // Retorna o corpo da resposta após processamento
            return $this->json->unserialize($responseBody);
        } catch (\InvalidArgumentException $exc) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Invalid JSON was returned by the gateway')
            );
        }
    }
}
