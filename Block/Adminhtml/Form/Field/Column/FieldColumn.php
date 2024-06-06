<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Block\Adminhtml\Form\Field\Column;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Ui\Component\Form\AttributeMapper;

/**
 * Class FieldColumn - Create Field to Column.
 */
class FieldColumn extends Select
{
    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Render Options.
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $options = [
            [
                'label' => __('Disabled'),
                'value' => '0',
            ],
            [
                'label' => __('Enabled'),
                'value' => '1',
            ]
        ];

        return $options;
    }
}
