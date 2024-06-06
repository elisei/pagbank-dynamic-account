<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use O2TI\PagBankDynamicAccount\Model\Api\Credential;

/**
 * Class oAuth - Create Authorization.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Oauth extends \Magento\Backend\App\Action
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

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
     * @param Context               $context
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param JsonFactory           $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param Credential            $credential
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        Json $json,
        Credential $credential
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->credential = $credential;
        parent::__construct($context);
    }

    /**
     * ACL - Check is Allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('O2TI_PagBankDynamicAccount::config_pagbank_dynamic_account_general');
    }

    /**
     * Excecute.
     *
     * @return json
     */
    public function execute()
    {
        $configDefault = false;

        $params = $this->getRequest()->getParams();
        $webSiteId = (int) $params['website'];

        if (!$webSiteId) {
            $configDefault = true;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $oAuth = null;

        if (isset($params['code'])) {
            $oAuthResponse = $this->credential->getAuthorize(
                $webSiteId,
                $params['code'],
                $params['code_verifier']
            );

            if ($oAuthResponse) {
                $oAuthResponse = $this->json->unserialize($oAuthResponse);
                
                if ($oAuthResponse['access_token']) {
                    $publicKey = $this->credential->getPublicKey($oAuthResponse['access_token'], $webSiteId);
                    $publicKey = $this->json->unserialize($publicKey);
                    $configs = [
                        'status' => 1,
                        'account_id'    => $oAuthResponse['account_id'],
                        'access_token'  => $oAuthResponse['access_token'],
                        'refresh_token' => $oAuthResponse['refresh_token'],
                        'public_key'    => $publicKey['public_key'],
                    ];
                    $this->credential->setNewConfigs(
                        $configs,
                        $configDefault,
                        $webSiteId
                    );
                    $this->cacheTypeList->cleanType('config');
                    $this->messageManager->addSuccess(__('You are connected to PagBank. =)'));
                    $resultRedirect->setUrl($this->getUrlConfig());

                    return $resultRedirect;
                }
            }
        }

        $this->messageManager->addError(__('Unable to get the code, try again. =('));
        $resultRedirect->setUrl($this->getUrlConfig());

        return $resultRedirect;
    }

    /**
     * Get store from request.
     *
     * @return Store
     */
    public function getStore()
    {
        $webSiteId = (int) $this->getRequest()->getParam('website');

        return $this->storeManager->getStore($webSiteId);
    }

    /**
     * Get Url.
     *
     * @return string
     */
    private function getUrlConfig()
    {
        return $this->getUrl(
            'adminhtml/system_config/edit/section/payment/',
            [
                'website' => $this->getStore()->getId(),
            ]
        );
    }
}
