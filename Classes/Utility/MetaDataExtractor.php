<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2020-01-25 */

namespace Frontify\Typo3\Utility;

use Frontify\Typo3\ApiServiceProvider;
use Frontify\Typo3\Asset\FrontifyAsset;
use Frontify\Typo3\Storage\StorageUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaDataExtractor implements ExtractorInterface {

    /**
     * @var ApiServiceProvider
     */
    private $apiServiceProvider;

    /**
     * Returns an array of supported file types;
     * An empty array indicates all filetypes
     *
     * @return array
     */
    public function getFileTypeRestrictions() {
        return [];
    }

    /**
     * Get all supported DriverClasses
     *
     * Since some extractors may only work for local files, and other extractors
     * are especially made for grabbing data from remote.
     *
     * Returns array of string with driver names of Drivers which are supported,
     * If the driver did not register a name, it's the classname.
     * empty array indicates no restrictions
     *
     * @return array
     */
    public function getDriverRestrictions() {
        return ['frontify'];
    }

    /**
     * Returns the data priority of the extraction Service.
     * Defines the precedence of Data if several extractors
     * extracted the same property.
     *
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority() {
        return 10;
    }

    /**
     * Returns the execution priority of the extraction Service
     * Should be between 1 and 100, 100 means runs as first service, 1 runs at last service
     *
     * @return int
     */
    public function getExecutionPriority() {
        return 10;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     *
     * @return bool
     */
    public function canProcess(File $file) {
        return true;
    }

    private function getApiServiceProvider(): ApiServiceProvider {
        if (!isset($this->apiServiceProvider)) {
            $this->apiServiceProvider = GeneralUtility::makeInstance(ApiServiceProvider::class);
        }

        return $this->apiServiceProvider;
    }

    /**
     * The actual processing TASK
     *
     * Should return an array with database properties for sys_file_metadata to write
     *
     * @param File $file
     * @param array $previousExtractedData optional, contains the array of already extracted data
     *
     * @return array
     */
    public function extractMetaData(File $file, array $previousExtractedData = []) {
        $asset = $this->getApiServiceProvider()->getAssetByIdentifier($file->getIdentifier());

        return array_merge($previousExtractedData, [
            'width' => $asset->width,
            'height' => $asset->height,
            'title' => $asset->title,
            'description' => $asset->description,
        ]);
    }
}