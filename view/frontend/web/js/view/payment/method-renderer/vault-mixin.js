/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    var vaultMixin = {

        getData: function () {
            
            var data = wrapper.wrap(this._super, function (originalFunction) {
                var originalData = originalFunction();

                if (this.useSellerAccountId()) {
                    originalData['additional_data']['account_id'] = this.getAccountId();
                }
                return originalData;
            });

            return data.call(this);
        },

        /**
         * Get Account Id
         * @returns {string}
         */
        getAccountId: function () {
            return this.details['account_id'];
        },

        /**
         * Get Seller Account Id
         * @returns {String|Boolean}
         */
        useSellerAccountId: function () {
            return window.checkoutConfig.o2ti_pagbank_dynamic_account.hasOwnProperty('enable') ?
            window.checkoutConfig.o2ti_pagbank_dynamic_account.enable
            : false;
        }
    };

    return function (target) {
        return target.extend(vaultMixin);
    };
});
