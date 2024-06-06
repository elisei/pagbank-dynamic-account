<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use O2TI\PagBankDynamicAccount\Block\Adminhtml\Form\Field\Column\FieldColumn;
use Magento\Framework\Math\Random;
use PagBank\PaymentMagento\Gateway\Config\Config;

/**
 * Class Add Accounts - Add Accounts to field.
 */
class AddAccounts extends AbstractFieldArray
{
    /**
     * @var FieldColumn
     */
    protected $fieldRenderer;

    /**
     * @var Oauth
     */
    protected $oauth;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @var string
     */
    protected $codeVerifier;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Random $mathRandom
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Random $mathRandom,
        array $data = []
    ) {
        $this->config = $config;
        $this->mathRandom = $mathRandom;
        parent::__construct($context, $data);
    }

    /**
     * Prepare rendering the new field by adding all the needed columns.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareToRender()
    {
        $this->addColumn('status', [
            'label' => __('Status'),
            'renderer' => $this->getFieldRenderer(),
        ]);

        $this->addColumn('account_id', [
            'label'    => __('Account Id'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('access_token', [
            'label'    => __('Access Token'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('public_key', [
            'label' => __('Public Key'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('refresh_token', [
            'label' => __('Refresh Token'),
            'class' => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $field = $row->getField();
        if ($field !== null) {
            $options['option_'.$this->getFieldRenderer()->calcOptionHash($field)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create Block FieldColumn.
     *
     * @throws LocalizedException
     *
     * @return FieldColumn
     */
    private function getFieldRenderer()
    {
        if (!$this->fieldRenderer) {
            $this->fieldRenderer = $this->getLayout()->createBlock(
                FieldColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->fieldRenderer;
    }

    /**
     * Custom render function for Add button.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        $addUrl = $this->getUrlToConnect(); // Obtém a URL do método getUrlToConnect
        $html .= '<button type="button" class="action-add" onclick="window.location.href=\'' . $addUrl . '\'"><span>' . __('Connect New Account') . '</span></button>';
        return $html;
    }

    /**
     * Url Authorize.
     *
     * @return string
     */
    public function getUrlAuthorize()
    {
        $storeUri = $this->getUrl(
            'o2ti/system_config/oauth',
            [
                'website'       => 0,
                'code_verifier' => $this->codeVerifier,
            ]
        );

        return $storeUri;
    }

    /**
     * Url to connect.
     *
     * @return string
     */
    public function getUrlToConnect()
    {
        $storeId = 0;
        $urlConnect = Config::ENDPOINT_CONNECT_PRODUCTION;
        $appId = Config::APP_ID_PRODUCTION;
        $scope = Config::OAUTH_SCOPE;
        $state = Config::OAUTH_STATE;
        $responseType = Config::OAUTH_CODE;

        $codeChallenge = $this->getCodeChallenge();
        $redirectUri = $this->getUrlAuthorize();
        $codeChallengeMethod = Config::OAUTH_CODE_CHALLENGER_METHOD;

        if ($this->config->getEnvironmentMode($storeId) === Config::ENVIRONMENT_SANDBOX) {
            $urlConnect = Config::ENDPOINT_CONNECT_SANDBOX;
            $appId = Config::APP_ID_SANDBOX;
        }

        $params = [
            'response_type'         => $responseType,
            'client_id'             => $appId,
            'scope'                 => $scope,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'redirect_uri'          => $redirectUri,
        ];

        $link = $urlConnect.'?'.http_build_query($params, '&');

        return urldecode($link);
    }

    /**
     * Get Code Challenger.
     *
     * @return string
     */
    public function getCodeChallenge()
    {
        $params = $this->getRequest()->getParams();

        $this->codeVerifier = sha1($this->mathRandom->getRandomString(100));

        if (isset($params['key'])) {
            $this->codeVerifier = $params['key'];
        }

        $codeChallenge = $this->getBase64UrlEncode(
            pack('H*', hash('sha256', $this->codeVerifier))
        );

        return $codeChallenge;
    }

    /**
     * Get Base64 Url Encode.
     *
     * @param string $code
     *
     * @return string
     */
    public function getBase64UrlEncode($code)
    {
        return rtrim(strtr(base64_encode($code), '+/', '-_'), '=');
    }
}
