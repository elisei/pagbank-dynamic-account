<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Plugin\PagBank\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use PagBank\PaymentMagento\Gateway\Http\TransferFactory;
use PagBank\PaymentMagento\Gateway\Request\MetadataRequest;
use O2TI\PagBankDynamicAccount\Helper\Data;

class ModifyTransferFactory
{
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @param TransferBuilder $transferBuilder
     * @param TransferFactory $transferFactory
     * @param Data $helper
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        TransferFactory $transferFactory,
        Data $helper
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->transferFactory = $transferFactory;
        $this->helper = $helper;
    }

    /**
     * Around Create, change auth.
     *
     * @param TransferFactory $subject
     * @param \Closure $proceed
     * @param array $request
     */
    public function aroundCreate(
        TransferFactory $subject,
        \Closure $proceed,
        array $request
    ) {
        $result = $proceed($request);

        $storeId = $request[MetadataRequest::METADATA][0][MetadataRequest::STORE_ID];
        $accountId = $request[MetadataRequest::METADATA][0]['account_id'];

        $uri = $result->getUri();
        $body = $result->getBody();
        $clientConfig = $result->getClientConfig();
        $originalHeader = $result->getHeaders($storeId);
        
        $headers = $this->getModifiedHeaders($originalHeader, $accountId);

        $modifiedTransfer = $this->transferBuilder
            ->setUri($uri)
            ->setBody($body)
            ->setClientConfig($clientConfig)
            ->setHeaders($headers)
            ->build();

        return $modifiedTransfer;
    }

    /**
     * Modified Headers.
     *
     * @param array $originalHeader
     * @param string $accountId
     * @return array
     */
    public function getModifiedHeaders($originalHeader, $accountId)
    {
        
        $oAuth = $this->helper->getOauthByAccountId($accountId);

        if ($oAuth) {
            $originalHeader['Authorization'] = $oAuth;
        }

        return $originalHeader;
    }
}
