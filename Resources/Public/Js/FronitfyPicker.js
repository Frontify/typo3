define("optional", [], {
    load : function (moduleName, parentRequire, onload, config){

        var onLoadSuccess = function(moduleInstance){
            // Module successfully loaded, call the onload callback so that
            // requirejs can work its internal magic.
            onload(moduleInstance);
        }

        var onLoadFailure = function(err){
            // optional module failed to load.
            var failedId = err.requireModules && err.requireModules[0];
            console.warn("Could not load optional module: " + failedId);

            // Undefine the module to cleanup internal stuff in requireJS
            requirejs.undef(failedId);

            // Now define the module instance as a simple empty object
            // (NOTE: you can return any other value you want here)
            define(failedId, [], function(){return {};});

            // Now require the module make sure that requireJS thinks
            // that is it loaded. Since we've just defined it, requirejs
            // will not attempt to download any more script files and
            // will just call the onLoadSuccess handler immediately
            parentRequire([failedId], onLoadSuccess);
        }

        parentRequire([moduleName], onLoadSuccess, onLoadFailure);
    }
});

require([
    'jquery',
    'nprogress',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity',
    'optional!TYPO3/CMS/Backend/Utility/MessageUtility'
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