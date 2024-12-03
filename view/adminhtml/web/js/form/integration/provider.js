/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/provider',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (FormProvider, $, registry) {
    'use strict';

    return FormProvider.extend({
        defaults: {
            retryCount: 45,
            timeout: '150', // ms
            selectorPrefix: '.modal-content',
            messagesClass: 'messages',
            modalSelector: 'klevu_integration_store_listing_klevu_integration_store_listing_klevu_integration_wizard_modal',
            lastFieldsetSelector: 'klevu_integration_close_modal',
            listingIndex: 'klevu_integration_store_listing', // index of store listing ui component to refresh
        },

        /**
         * Initializes component.
         *
         * @returns {FormProvider} Chainable.
         */
        initialize: function () {
            const self = this;

            self._super();

            return self;
        },

        /**
         * @param {number} attempt
         * @returns {FormProvider} Chainable.
         */
        hideTabsOnLoad: function (attempt = 0) {
            const self = this;
            const lastElement = document.querySelector(
                "[data-index='" + self.lastFieldsetSelector + "']"
            );
            if (!lastElement) {
                // The node we need has not yet been rendered.
                if (attempt > self.retryCount) {
                    // too many attempts, stop possible infinite loop
                    console.error('Element ' + lastElement + ' was not rendered after ' + self.retryCount + ' attempts.');
                } else {
                    // Wait and try again
                    window.setTimeout(() => {
                        self.hideTabsOnLoad(attempt + 1)
                    }, self.timeout);
                }

                return self;
            }
            const firstElement = lastElement.parentNode.firstElementChild;
            self.hideOtherTabs(firstElement);

            return self;
        },

        /**
         * @returns {FormProvider} Chainable.
         */
        startProcess: function () {
            const self = this;
            const $body = $('body');
            $body.trigger('processStart');

            return self;
        },

        /**
         * @returns {FormProvider} Chainable.
         */
        stopProcess: function () {
            const self = this;
            const $body = $('body');
            $body.trigger('processStop');

            return self;
        },

        /**
         * @returns {FormProvider} Chainable.
         */
        clearMessages: function () {
            const self = this;
            let $body = $('body');
            $body.notification('clear');

            return self;
        },

        /**
         * @param {Array} messages
         * @param {String} status
         * @returns {FormProvider} Chainable.
         */
        displayMessages: function (messages, status) {
            const self = this;
            const selectorPrefix = self.selectorPrefix;
            const messagesClass = self.messagesClass;
            let $body = $('body');

            $.each(messages || [], function (key, message) {
                $body.notification('add', {
                    error: status !== 'success',
                    message: message,

                    /**
                     * Insert method.
                     *
                     * @param {String} msg
                     */
                    insertMethod: function (msg) {
                        let $wrapper = $('<div></div>').addClass(messagesClass).html(msg);

                        $(selectorPrefix).before($wrapper);
                        $('html, body').animate({
                            scrollTop: $(selectorPrefix).offset().top
                        });
                    }
                });
            });

            return self;
        },

        /**
         * @param {Object} element
         * @returns {FormProvider} Chainable.
         * @private
         */
        openNextTab: function (element) {
            const self = this;
            const target = self.getNextTab(element);

            if ("undefined" !== typeof target && null !== target) {
                const confirmationTitle = document.querySelector(
                    "[data-index='" + target.dataset.index + "'] > .fieldset-wrapper-title"
                );
                if (confirmationTitle.dataset.stateCollapsible === 'closed') {
                    $(confirmationTitle).trigger('click');
                }
            }

            return self;
        },

        /**
         * @param {Object} currentElement
         * @returns {FormProvider} Chainable.
         */
        closeAllTabs: function (currentElement) {
            const self = this;
            const siblings = this.getSiblings(currentElement);

            const currentTitle = document.querySelector(
                "[data-index='" + currentElement.dataset.index + "'] > .fieldset-wrapper-title"
            );
            if ("undefined" !== typeof currentTitle && currentTitle.dataset.stateCollapsible === 'open') {
                $(currentTitle).trigger('click');
            }
            siblings.forEach(element => {
                const title = document.querySelector(
                    "[data-index='" + element.dataset.index + "'] > .fieldset-wrapper-title"
                );
                if ("undefined" !== typeof title && title.dataset.stateCollapsible === 'open') {
                    $(title).trigger('click');
                }
            });

            return self;
        },

        /**
         * @param {Object} element
         * @param {number} attempt
         * @returns {FormProvider} Chainable.
         */
        showNextTab: function (element, attempt = 0) {
            const self = this;
            const target = self.getNextTab(element);
            $(target).show();

            return self;
        },

        /**
         * @param {Object} element
         * @param {number} attempt
         * @returns {Object}
         */
        getNextTab: function (element, attempt = 0) {
            const nextSibling = element.nextElementSibling;
            let dataIndexNext = null;
            if ("undefined" !== typeof nextSibling && null !== nextSibling) {
                dataIndexNext = nextSibling.dataset.index;
            }
            const target = document.querySelector("[data-index='" + dataIndexNext + "']");
            if (!target) {
                // The node we need has not yet been rendered.
                if (attempt > self.retryCount) {
                    // too many attempts, stop possible infinite loop
                    console.error('Element ' + target + ' was not rendered after ' + self.retryCount + ' attempts.');
                } else {
                    // Wait and try again
                    window.setTimeout(self.getNextTab, self.timeout, element, attempt + 1);
                }
            }

            return target;
        },

        /**
         * @param {Object} currentElement
         * @returns {FormProvider} Chainable.
         */
        hideOtherTabs: function (currentElement) {
            const self = this;
            const siblings = this.getSiblings(currentElement);

            siblings.forEach(element => {
                self.hideTab(element);
            });

            return self;
        },

        /**
         * @param {Object} element
         * @param {number} attempt
         * @returns {FormProvider} Chainable.
         * @private
         */
        hideTab: function (element,  attempt = 0) {
            const target = document.querySelector(
                "[data-index='" + element.dataset.index + "']"
            );
            if ("undefined" === typeof target || !target) {
                // The node we need has not yet been rendered.
                if (attempt > self.retryCount) {
                    // too many attempts, stop possible infinite loop
                    console.error('Element ' + target + ' was not rendered after ' + self.retryCount + ' attempts.');
                } else {
                    // Wait and try again
                    window.setTimeout(self.hideTab, self.timeout, element, attempt + 1);
                }
                return self;
            }
            $(target).hide();

            return self;
        },

        /**
         * @param {Object} element
         * @returns {*[]}
         */
        getSiblings: function (element) {
            let siblings = [];
            if ("undefined" === typeof element || !element || !element.parentNode) {
                return siblings;
            }
            // first child of the parent node
            let sibling = element.parentNode.firstChild;

            // collecting siblings
            while (sibling) {
                if (sibling.nodeType === 1 && sibling !== element) {
                    siblings.push(sibling);
                }
                sibling = sibling.nextSibling;
            }

            return siblings;
        },

        /**
         * Close the Modal as the final step of the integration
         * @param {string} modalSelector
         * @returns {FormProvider} Chainable.
         */
        closeModal: function (modalSelector) {
            const self = this;
            modalSelector = modalSelector || self.modalSelector;

            const closeButton = document.querySelector(
                "." + modalSelector + " .action-close"
            );
            $(closeButton).trigger('click');

            return self;
        },

        /**
         * @returns {FormProvider} Chainable.
         */
        reloadStoreListing: function () {
            const self = this;

            const listing = registry.get('index = ' + self.listingIndex);
            if ("undefined" !== typeof listing) {
                listing.source.reload({
                    refresh: true
                });
            }

            return self;
        }
    });
});
