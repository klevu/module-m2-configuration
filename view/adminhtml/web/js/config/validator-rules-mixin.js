/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/lib/validation/validator',
    'jquery',
    'mage/translate'
], function (validator, $) {
    'use strict';
    return function (target) {
        validator.addRule(
            'validate-klevu-js-api',
            function (value) {
                return !value || value.startsWith('klevu-');
            },
            $.mage.__("Klevu JS API key must begin with 'klevu-'.")
        );
        validator.addRule(
            'validate-klevu-rest-auth',
            function (value) {
                return !value || value.length >= 10;
            },
            $.mage.__('Klevu Rest Auth key must be at least 10 characters long.')
        );
        validator.addRule(
            'validate-positive-integer',
            function (value) {
                return value && $.isNumeric(value) && (Math.floor(value) == value) && value > 0;
            },
            $.mage.__('Positive Integers only please.')
        );

        return target;
    };
});
