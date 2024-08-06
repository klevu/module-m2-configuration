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
            integrationFormSelector: 'klevu_integration_account_confirmation',
            integrationUrl: '/rest/default/V1/klevu-configuration/integrate-api-keys',
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
         * Integrate Api Keys
         *
         * @param {Object} action - action configuration
         */
        integrate: function (action) {
            const self = this;
            let check = $.Deferred();
            const url = self.integrationUrl;
            const currentElement = document.querySelector(
                "[data-index='" + self.integrationFormSelector + "']"
            );
            const klevuIntegrationFormProvider = self.getKlevuIntegrationFormProvider();

            klevuIntegrationFormProvider.clearMessages()
                .startProcess();

            if ("undefined" === typeof url || !url) {
                klevuIntegrationFormProvider.displayMessages([$.mage.__('URL "integrationUrl" is not set')], 'error')
                    .stopProcess();

                return check.resolve();
            }
            const ajaxParams = {
                form_key: window.FORM_KEY,
                isAjax: true,
                apiKey: self.source.js_api_key,
                authKey: self.source.rest_auth_key,
                scopeId: self.source.scope_id,
                scopeType: self.source.scope,
                loggerScopeId: self.source.logger_scope_id // required to log to correct store name in SSM
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
                            .openNextTab(currentElement)
                            .reloadStoreListing();
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
    });
});
