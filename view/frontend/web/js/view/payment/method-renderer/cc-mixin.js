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

    var ccMixin = {

        getData: function () {
            if (this.useSellerAccountId()) {
                var data = wrapper.wrap(this._super, function (originalFunction) {
                    var originalData = originalFunction();
                    
                    originalData['additional_data']['account_id'] = this.getSellerAccountId();
    
                    return originalData;
                });
    
                return data.call(this);
            }
        },

        /**
         * Use Seller account id
         * @returns {Boolean}
         */
        useSellerAccountId: function () {
            return window.checkoutConfig.o2ti_pagbank_dynamic_account.hasOwnProperty('enable') ?
            window.checkoutConfig.o2ti_pagbank_dynamic_account.enable
            : false;
        },

        /**
         * Get Seller Account Id
         * @returns {String|Boolean}
         */
        getSellerAccountId: function () {
            return window.checkoutConfig.o2ti_pagbank_dynamic_account.hasOwnProperty('account_id') ?
            window.checkoutConfig.o2ti_pagbank_dynamic_account.account_id
            : false;
        }
    };

    return function (target) {
        return target.extend(ccMixin);
    };
});
