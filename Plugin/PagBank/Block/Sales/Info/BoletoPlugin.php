<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Plugin\PagBank\Block\Sales\Info;

/**
 * Class Boleto - Boleto payment information.
 */
class BoletoPlugin
{
    /**
     * Custom template path
     */
    private const ACCOUNT_ID_TEMPLATE = 'O2TI_PagBankDynamicAccount::info/boleto/instructions.phtml';

    /**
     * Change the template path.
     *
     * @param \PagBank\PaymentMagento\Block\Sales\Info\Boleto $subject
     * @param string $result
     * @return string
     */
    public function afterGetTemplate(\PagBank\PaymentMagento\Block\Sales\Info\Boleto $subject, $result)
    {
        return self::ACCOUNT_ID_TEMPLATE;
    }
}
