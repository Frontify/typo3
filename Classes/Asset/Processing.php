<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2020-01-27 */

namespace Frontify\Typo3\Asset;

use Frontify\Typo3\ApiServiceProvider;
use Frontify\Typo3\Storage\FrontifyDriver;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Processing {

    public const DEFAULT_WIDTH = 2400;
    public const MIN_SIZE = 1;
    public const MAX_SIZE = 4000;

    /**
     * Legacy signal for Typo3 < 10 support
     *
     * @param $fileProcessingService
     * @param DriverInterface $driver
     * @param ProcessedFile $processedFile
     * @param $file
     * @param $taskType
     * @param array $configuration
     */
    public function processLegacy($fileProcessingService, DriverInterface $driver, ProcessedFile $processedFile, $file, $taskType, array $configuration){
        if (!$driver instanceof FrontifyDriver) {
            return;
        }

        $this->process($processedFile, $configuration);
    }

    /**
     * Typo3 > 10
     *
     * @param BeforeFileProcessingEvent $event
     */
    public function processAsset(BeforeFileProcessingEvent $event) {
        if (!$event->getDriver() instanceof FrontifyDriver) {
            return;
        }

        $processedFile = $event->getProcessedFile();
        $configuration = $event->getConfiguration();
        $this->process($processedFile, $configuration);
    }

    public function process(ProcessedFile $processedFile, array $configuration) {
        $crop = $configuration['crop'] ?? null;

        // Check if processing is needed
        if (!$this->shouldProcess($processedFile)) {
            return;
        }

        /** @var FrontifyAsset $asset */
        $asset = GeneralUtility::makeInstance(ApiServiceProvider::class)->getAssetByIdentifier($processedFile->getIdentifier());

        // Get the requested size parameters and calculate the height corresponding to that.
        list($width, $height) = $this->extractSize($configuration, $asset);

        // Apply cropping and make sure the height is proportional
        list($width, $height, $cropParameter) = $this->applyCrop($width, $height, $crop, $asset);

        // Enforce usage of original
        $processedFile->setUsesOriginalFile();

        // Update the identifier to use the correct width and height
        $processedFile->setIdentifier(
            str_replace('width={width}', "width={$width}". $cropParameter, $processedFile->getIdentifier())
        );

        // Updated the properties and save it.
        $processedFile->updateProperties([
            'width' => $width,
            'height' => $height,
        ]);

        $this->persistProcessedFile($processedFile);
    }

    private function applyCrop(int $requestedWidth, int $requestedHeight, $cropArea, FrontifyAsset $asset): array {
        if (!$cropArea instanceof Area) {
            return [$requestedWidth, $requestedHeight, ''];
        }

        // Get rect
        $rect = $cropArea->asArray();
        $x = (float) $rect['x'];
        $y = (float) $rect['y'];
        $width = (float) $rect['width'];
        $height = (float) $rect['height'];
        $referenceWidth = (int) $asset->width;

        list($finalWidth, $finalHeight) = $this->recalculateSizeToFitRect($requestedWidth, $requestedHeight, $width, $height);

        // Resize
        return [
            $finalWidth,
            $finalHeight,
            "&rect={$x},{$y},{$width},{$height}&reference_width={$referenceWidth}"
        ];
    }

    private function recalculateSizeToFitRect(int $requestedWidth, int $requestedHeight, int $resizedWidth, int $resizedHeight): array {
        // 1. Make height proportionally
        $requestedHeight = $requestedWidth * $resizedHeight / $resizedWidth;

        // 2. Make width fit into new dimensions
        if ($requestedWidth < $resizedWidth) {
            return [
                (int) $requestedWidth,
                (int) $requestedHeight
            ];
        }

        // Resize
        $resizeFactor = $resizedWidth / $requestedWidth;
        return [
            (int) floor($requestedWidth * $resizeFactor),
            (int) floor($requestedHeight * $resizeFactor)
        ];
    }

    private function extractSize(array $configuration, FrontifyAsset $asset): array {
        $maxWidth = $configuration['maxWidth'] ?? self::MAX_SIZE;
        $minWidth = $configuration['minWidth'] ?? self::MIN_SIZE;
        $maxHeight = $configuration['maxHeight'] ?? self::MAX_SIZE;
        $minHeight = $configuration['minHeight'] ?? self::MIN_SIZE;

        // Provided height's and width's
        $providedWidth = $configuration['width'];
        $providedHeight = $configuration['height'];

        // If a width is given, take everything from the width
        if (isset($providedWidth)) {
            return $this->extractSizeFromWidth(
                $this->getBetween($minWidth, $maxWidth, $providedWidth), $asset
            );
        }

        if (isset($providedHeight)) {
            return $this->extractSizeFromHeight(
                $this->getBetween($minHeight, $maxHeight, $providedHeight), $asset
            );
        }

        // We have a given max-height
        if ($maxHeight !== self::MAX_SIZE) {
            return $this->extractSizeFromHeight(
                $this->getBetween(0, self::MAX_SIZE, $maxHeight),
                $asset
            );
        }

        // As a default we get if from the width
        return $this->extractSizeFromWidth(
            $this->getBetween(0, self::MAX_SIZE, $maxWidth === self::MAX_SIZE ? self::DEFAULT_WIDTH : $maxWidth),
            $asset
        );
    }

    private function getBetween(int $min, int $max, int $value): int {
        if ($max > self::MAX_SIZE) {
            $max = self::MAX_SIZE;
        }

        if ($min < self::MIN_SIZE) {
            $min = self::MIN_SIZE;
        }

        return max(min($value, $max), $min);
    }

    private function extractSizeFromWidth(int $width, FrontifyAsset $asset): array {
        $height = (int) round($width / $asset->width * $asset->height, 0);
        return [
            $width,
            $height
        ];
    }

    private function extractSizeFromHeight(int $height, FrontifyAsset $asset): array {
        $width = (int) round($height / $asset->height * $asset->width, 0);
        return [
            $width,
            $height
        ];
    }

    private function shouldProcess(ProcessedFile $processedFile) {
        return strpos($processedFile->getIdentifier(), 'width={width}') !== false;
    }

    private function persistProcessedFile(ProcessedFile $processedFile) {
        /** @var ProcessedFileRepository $processedFileRepository */
        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $processedFileRepository->add($processedFile);
    }

}