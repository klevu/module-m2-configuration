/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @api
 */
define([
    'Klevu_Configuration/js/form/integration/provider',
    'jquery',
    'mage/translate'
], function (FormProvider, $) {
    'use strict';

    return FormProvider.extend({
        defaults: {
            modalSelector: 'klevu_integration_store_listing_klevu_integration_store_listing_klevu_integration_removal_modal',
            ApiKeysFormSelector: 'klevu_remove_auth_keys',
            removeApiKeysUrl: '/rest/default/V1/klevu-configuration/remove-api-keys',
            listingIndex: 'klevu_integration_store_listing', // index of store listing ui component to refresh
        },

        /**
         * Initializes component.
         *
         * @returns {FormProvider} Chainable.
         */
        initialize: function () {
            this._super()
                .clearMessages();

            return this;
        },

        /**
         * Check API Keys & Get account details
         */
        removeApiKeys: function () {
            const self = this;
            let check = $.Deferred();
            const url = self.removeApiKeysUrl;

            self.clearMessages()
                .startProcess();

            if (typeof url === 'undefined' || !url) {
                self.displayMessages([$.mage.__('URL "removeApiKeysUrl" is not set')], 'error')
                    .stopProcess();

                return check.resolve();
            }
            const ajaxParams = {
                form_key: window.FORM_KEY,
                isAjax: true,
                scopeId: self.scope_id,
                scopeType: self.scope
            };
            const headers = {
                'Content-type': 'application/json',
                'Authorization': 'Bearer ' + self.bearer
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
                        self.displayMessages(messages, resp.status)
                    }
                    if (resp.status === 'success') {
                        self.reloadStoreListing()
                            .closeModal(self.modalSelector);
                        check.resolve();
                        return true;
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
                    self.displayMessages(messages, resp.status);
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    self.stopProcess();
                }
            });

            return check.promise();
        },
    });
});
