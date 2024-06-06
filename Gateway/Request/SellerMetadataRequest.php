<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Request\MetadataRequest;

/**
 * Class Items Data Request - Item structure for orders.
 */
class SellerMetadataRequest implements BuilderInterface
{
    /**
     * Seller oAuth block name.
     */
    public const ACCOUNT_ID = 'account_id';

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject): array
    {
        $result = [];

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment * */
        $payment = $paymentDO->getPayment();

        $result[MetadataRequest::METADATA][] = $this->getSellerAccountId($payment);

        return $result;
    }

    /**
     * Get Seller Account Id.
     *
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function getSellerAccountId(
        $payment
    ) {
        $accountId = $payment->getAdditionalInformation('account_id') ?: 1;

        $result[self::ACCOUNT_ID] = $accountId;

        return $result;
    }
}
