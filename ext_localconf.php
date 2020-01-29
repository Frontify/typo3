<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-17 */

// Prevent Script from beeing called directly
defined('TYPO3_MODE') || die();

(function(){
    $majorVersionNumber = (int) explode('.',TYPO3_version, 2)[0];

    /**
     * Register File System Driver
     */
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['frontify'] = [
        'class' => \Frontify\Typo3\Storage\FrontifyDriver::class,
        'label' => 'Frontify Assets',
        'shortName' => 'Frontify',
        'flexFormDS' => 'FILE:EXT:frontify_typo3/Configuration/FrontifyStorageFlexForm.xml',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433198160] = [
        'nodeName' => 'inline',
        'priority' => 50,
        'class' => \Frontify\Typo3\Backend\InlineButtonsController::class,
    ];

    $extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
    $extractorRegistry->registerExtractionService(\Frontify\Typo3\Utility\MetaDataExtractor::class);
    unset($extractorRegistry);

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $dispatcher */
    $signalDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    // Add legacy Events
    if ($majorVersionNumber === 9) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalDispatcher->connect(
            TYPO3\CMS\Core\Resource\ResourceStorage::class,
            \TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess,
            \Frontify\Typo3\Asset\Processing::class,
            'processLegacy'
        );
    }

    // Caching
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['frontify'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['frontify'] = [];
    }

    // Add scripts and style
    if (TYPO3_MODE === 'BE') {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer $renderer */
        $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $renderer->addJsFile('EXT:frontify_typo3/Resources/Public/Js/FronitfyPicker.js', 'text/javascript', false, false, '', true, '|', false, '');
        $renderer->addCssFile('EXT:frontify_typo3/Resources/Public/Css/FrontifyPicker.css','stylesheet');
    }

    // Installation event, creates storage driver
    $signalDispatcher->connect(
        \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
        'afterExtensionInstall',
        \Frontify\Typo3\Installer::class,
        'execute'
    );

    // Save memory
    unset($signalDispatcher);
})();