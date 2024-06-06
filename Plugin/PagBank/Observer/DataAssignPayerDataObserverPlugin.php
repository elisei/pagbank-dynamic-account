<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Plugin\PagBank\Observer;

use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\Event\Observer;
use PagBank\PaymentMagento\Observer\DataAssignPayerDataObserver;

/**
 * Class Extend Data Assign Payer Data Observer - Capture pix and boleto payment information.
 */
class DataAssignPayerDataObserverPlugin
{
    /**
     * Método aroundExecute do Plugin para estender a função Execute do DataAssignCcObserver.
     *
     * @param DataAssignPayerDataObserver $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        DataAssignPayerDataObserver $subject,
        callable $proceed,
        Observer $observer
    ) {
        $result = $proceed($observer);

        $dataObject = $this->readDataArgument($observer);
        
        $paymentInfo = $this->readPaymentModelArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (isset($additionalData['account_id'])) {
            $paymentInfo->setAdditionalInformation(
                'account_id',
                $additionalData['account_id']
            );
        }

        return $result;
    }

    /**
     * Retorna o objeto de dados do evento.
     *
     * @param Observer $observer
     * @return mixed
     */
    protected function readDataArgument(Observer $observer)
    {
        $event = $observer->getEvent();
        return $event->getDataByKey(AbstractDataAssignObserver::DATA_CODE);
    }

    /**
     * Retorna o modelo de pagamento do evento.
     *
     * @param Observer $observer
     * @return mixed
     */
    protected function readPaymentModelArgument(Observer $observer)
    {
        $event = $observer->getEvent();
        return $event->getDataByKey(AbstractDataAssignObserver::MODEL_CODE);
    }
}
