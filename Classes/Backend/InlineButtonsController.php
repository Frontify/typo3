<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-17 */

namespace Frontify\Typo3\Backend;

use Frontify\Typo3\ApiServiceProvider;
use Frontify\Typo3\Storage\FrontifyDriver;
use TYPO3\CMS\Backend\Form\Container\InlineControlContainer;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class InlineButtonsController
 * @package Frontify\Typo3\Backend
 */
class InlineButtonsController extends InlineControlContainer {

    /** @var array */
    protected $inlineConfiguration;

    /** @var ApiServiceProvider */
    protected $apiService;

    /**
     * @param array $inlineConfiguration
     *
     * @return string
     */
    protected function renderPossibleRecordsSelectorTypeGroupDB(array $inlineConfiguration) {
        $selector = parent::renderPossibleRecordsSelectorTypeGroupDB($inlineConfiguration);
        $this->inlineConfiguration = $inlineConfiguration;

        /** @var ApiServiceProvider $apiService */
        $this->apiService = GeneralUtility::makeInstance(ApiServiceProvider::class);

        if (!$this->canAccessFrontify()) {
            return $selector;
        }

        return $this->appendFrontifyButton($selector);
    }

    /**
     * Returns an awway with allowed extensions
     * @return array
     */
    protected function getAllowedExtensions(): array {
        return GeneralUtility::trimExplode(',', $this->inlineConfiguration['selectorOrUniqueConfiguration']['config']['appearance']['elementBrowserAllowed'] ?? '', true);
    }

    /**
     * @param string $selector
     *
     * @return string
     */
    protected function appendFrontifyButton(string $selector): string {
        $search = '</div><div class="help-block">';

        // To use the JS Inline Upload
        $foreign_table = $this->inlineConfiguration['foreign_table'];
        $currentStructureDomObjectIdPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        $prefix = $currentStructureDomObjectIdPrefix . '-' . $foreign_table;

        // First check if the help button exists.
        // It seems to be the easiest way to append the button.
        // If not found, we just append the button
        if (strpos($selector, $search) === false) {
            return $search . $this->getButtonMarkUp($prefix, $this->getAllowedExtensions());
        }

        $buttonMarkup = $this->getButtonMarkUp($prefix, $this->getAllowedExtensions()) . $search;
        return str_replace($search, $buttonMarkup, $selector);
    }

    /**
     * @param string $objPrefix
     * @param array $allowedExtensions
     *
     * @return string
     */
    protected function getButtonMarkUp(string $objPrefix, array $allowedExtensions): string {
        return ' <button 
            class="frontify-chooser-button btn btn-default"
            data-token="' . $this->apiService->getToken() . '"
            data-domain="' . $this->apiService->getFrontifyBaseUrl() . '" 
            data-prefix="' . $objPrefix . '"
            data-extensions="' . implode(',', $this->getAllowedExtensions()) . '"
            ' . $this->disabledButtonMarkup() . '
            >' . $this->buttonMessage() . '</button>';
    }

    /**
     * @return string
     */
    protected function disabledButtonMarkup(): string {
        if ($this->apiService->isEnabled()) {
            return '';
        }

        return ' disabled';
    }

    /**
     * @return string
     */
    protected function buttonMessage(): string {
        if ($this->apiService->isEnabled()) {
            return 'Choose from Frontify';
        }

        return 'Choose from Frontify (not configured)';
    }

    /**
     * Check if the user can Access the Frontify Storage
     * @return bool
     */
    protected function canAccessFrontify(): bool {
        $availableStorageClasses = $this->getBackendUserAuthentication()->getFileStorages();

        foreach ($availableStorageClasses as $storage) {
            if ($storage->getDriverType() === 'frontify') {
                return true;
            }
        }

        // The Frontify storage does not exist.
        return false;
    }

}