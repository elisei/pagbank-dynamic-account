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
 * Class DeepLink - DeepLink payment information.
 */
class DeepLinkPlugin
{
    /**
     * Custom template path
     */
    private const ACCOUNT_ID_TEMPLATE = 'O2TI_PagBankDynamicAccount::info/deep-link/instructions.phtml';

    /**
     * Change the template path.
     *
     * @param \PagBank\PaymentMagento\Block\Sales\Info\DeepLink $subject
     * @param string $result
     * @return string
     */
    public function afterGetTemplate(\PagBank\PaymentMagento\Block\Sales\Info\DeepLink $subject, $result)
    {
        return self::ACCOUNT_ID_TEMPLATE;
    }
}
