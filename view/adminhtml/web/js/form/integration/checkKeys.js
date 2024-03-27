/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'Klevu_Configuration/js/form/integration/provider',
    'jquery',
    'mage/translate'
], function (Fieldset, KlevuIntegrationFormProvider, $) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            checkApiKeysFormSelector: 'klevu_integration_auth_keys',
            checkApiKeysUrl: '/rest/default/V1/klevu-configuration/check-api-keys',
            integrationFormSelector: 'klevu_integration_account_confirmation',
            klevuIntegrationFormProvider: null,
        },

        /**
         * @returns {KlevuIntegrationFormProvider}
         */
        getKlevuIntegrationFormProvider: function () {
            const self = this;

            return self.klevuIntFromProvider || KlevuIntegrationFormProvider();
        },

        /**
         * Initializes component.
         *
         * @returns {Fieldset} Chainable.
         */
        initialize: function () {
            const self = this;
            const klevuIntegrationFormProvider = self.getKlevuIntegrationFormProvider();

            self._super();
            klevuIntegrationFormProvider.clearMessages()
                .hideTabsOnLoad();

            return self;
        },

        /**
         * Check API Keys & Get account details
         */
        checkApiKeys: function () {
            const self = this;
            let check = $.Deferred();
            const url = self.checkApiKeysUrl;
            const currentElement = document.querySelector(
                "[data-index='" + self.checkApiKeysFormSelector + "']"
            );
            const klevuIntegrationFormProvider = self.getKlevuIntegrationFormProvider();

            klevuIntegrationFormProvider.clearMessages()
                .startProcess();

            if ("undefined" === typeof url || !url) {
                klevuIntegrationFormProvider.displayMessages([$.mage.__('URL "checkApiKeysUrl" is not set')], 'error')
                    .stopProcess();

                return check.resolve();
            }
            const ajaxParams = {
                form_key: window.FORM_KEY,
                isAjax: true,
                apiKey: self.source.js_api_key,
                authKey: self.source.rest_auth_key,
                scopeId: self.source.scope_id,
                scopeType: self.source.scope
            };
            const headers = {
                'Content-type': 'application/json',
                'Authorization': 'Bearer ' + self.source.bearer
            };

            $.ajax({
                url: url,
                data: JSON.stringify(ajaxParams),
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json; charset=UTF-8',
                headers: headers,
                beforeSend: function (xhr) {
                    // Empty to remove Magento default handler
                },

                /**
                 * Success callback.
                 * @param {Object} resp
                 * @returns {Boolean}
                 */
                success: function (resp) {
                    const message = ("undefined" !== typeof resp.message) ? [resp.message] : [];
                    const messages = resp.messages || message;
                    if (messages.length) {
                        klevuIntegrationFormProvider.displayMessages(messages, resp.status);
                    }
                    if (resp.status === 'success') {
                        klevuIntegrationFormProvider.closeAllTabs(currentElement)
                            .showNextTab(currentElement)
                            .openNextTab(currentElement);
                        self._populateAccountDetails(resp.data);
                        check.resolve();
                        return true;
                    } else {
                        klevuIntegrationFormProvider.hideOtherTabs(currentElement);
                    }
                },

                /**
                 * Error callback.
                 * @param {Object} resp
                 * @returns {Boolean}
                 */
                error: function (resp) {
                    const message = ("undefined" !== typeof resp.message) ? [resp.message] : [$.mage.__('An error occurred. No message was returned.')];
                    const messages = resp.messages || message;
                    klevuIntegrationFormProvider.displayMessages(messages, resp.status)
                        .hideOtherTabs(currentElement);
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    klevuIntegrationFormProvider.stopProcess();
                }
            });

            return check.promise();
        },

        /**
         * @param {String} jsonData§
         * @param {number} attempt
         * @returns {Fieldset} Chainable.
         * @private
         */
        _populateAccountDetails: function (jsonData, attempt = 0) {
            const self = this;
            const wrapper = document.querySelector(
                "[data-index='" + self.integrationFormSelector + "'] > .admin__collapsible-content"
            );
            const target = document.querySelector(
                "[data-index='" + self.integrationFormSelector + "'] > .admin__collapsible-content .admin__field-value"
            );
            if (!target) {
                // The node we need has not yet been rendered.
                if (attempt > self.retryCount) {
                    // too many attempts, stop possible infinite loop
                    console.error('Element ' + target + ' was not rendered after ' + self.retryCount + ' attempts.');
                } else {
                    // Wait and try again
                    window.setTimeout(() => {
                        self._populateAccountDetails(jsonData, attempt + 1)
                    }, self.timeout);
                }
                return self;
            }
            const data = JSON.parse(jsonData);
            $(wrapper).find("[name='email']").text(data.email);
            $(wrapper).find("[name='company']").text(data.company);
            $(wrapper).find("[name='platform']").text(data.platform);
            $(wrapper).find("[name='active']").text(data.active);
            $(wrapper).find("[name='api_key']").text(data.apiKey);
            $(wrapper).find("[name='auth_key']").text(data.authKey);

            return self;
        },
    });
});
