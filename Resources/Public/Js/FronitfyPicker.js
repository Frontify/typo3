require([
    'jquery',
    'nprogress',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity',
    'TYPO3/CMS/Backend/Utility/MessageUtility'
], function ($, NProgress, Modal, Severity, MessageUtility) {
    'use strict';

    var frontifyPicker = {
        init: function () {
            this.hasEventListener = false;
            this.opener = null;
            this.isLoading = false;
            this.prefix = null;
            $(document).on('click', '.frontify-chooser-button', function (event) {
                event.preventDefault();
                var $button = $(event.currentTarget);
                this.openChooser($button);
            }.bind(this));
        },

        attachMessageListener: function () {
            if (this.hasEventListener) return;
            this.hasEventListener = true;

            // Add event listener to the right window
            if (self === top) {
                window.addEventListener('message', this.onMessage.bind(this));
                return;
            }

            parent.window.addEventListener('message', this.onMessage.bind(this));
        },


        onMessage: function (event) {
            // Sort out unnecessary events
            if (event.origin !== this.config.domain || !event.data || !this.config) return;

            var message = event.data;

            if (message.configurationRequested === true) {
                return this.onConfigurationRequired();
            }

            if (message.aborted === true) {
                return this.onAborted();
            }

            if (message.assetsChosen && message.assetsChosen.length > 0) {
                return this.onAssetChosen(message.assetsChosen);
            }

            console.error('Unrecognized Message', message);
        },

        onConfigurationRequired: function () {
            var chooser = this.$modal.find('iframe')[0].contentWindow;

            chooser.postMessage(
                {
                    token: this.config.accessToken,
                    mode: 'tree',
                    multiSelectionAllowed: true
                },
                this.config.domain + '/external-asset-chooser'
            );
        },

        onAborted: function () {
            Modal.dismiss();
        },

        onAssetChosen: function (assets) {
            if (this.isLoading) return;
            this.isLoading = true;

            assets.forEach(function (asset) {
                console.log(asset);
            });

            // Add loader and dismiss Modal
            NProgress.start();
            Modal.dismiss();

            $.post(TYPO3.settings.ajaxUrls['frontify_assetchooser_files'], {
                assets: assets,
            }, function (rawInput) {
                const data = JSON.parse(rawInput);
                this.isLoading = false;

                if (typeof inline !== 'undefined') {
                    inline.importElementMultiple(
                        this.prefix,
                        'sys_file',
                        data.file_ids,
                        'file'
                    );
                } else {
                    data.file_ids.forEach((fileId) => {
                        MessageUtility.MessageUtility.send({
                            objectGroup: this.prefix,
                            table: 'sys_file',
                            uid: fileId,
                            actionName: 'typo3:elementBrowser:elementInserted'
                        });
                    });
                }

                NProgress.done();
            }.bind(this));
        },

        destroyChooser: function () {
            this.config = undefined;
            this.$modal = undefined;
        },

        openChooser: function ($button) {
            this.config = {
                accessToken: $button.data('token'),
                domain: $button.data('domain'),
            };
            this.prefix = $button.data('prefix');
            this.attachMessageListener();

            this.$modal = Modal.advanced({
                type: Modal.types.iframe,
                title: 'Frontify Asset Chooser',
                content: this.config.domain + '/external-asset-chooser',
                size: Modal.sizes.large,
                severity: Severity.default,
            });
        },


    };

    frontifyPicker.init();
});