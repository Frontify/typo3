<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2020-01-16 */

namespace Frontify\Typo3\Asset;

use Frontify\Typo3\Utility\Frontify;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class AssetInput
 * @package Frontify\Typo3\Controller
 *
 * @property-read int $id
 * @property-read string $identifier
 * @property-read string|null $description
 * @property-read string $title
 * @property-read string $name
 * @property-read int $width
 * @property-read int $height
 * @property-read string $ext
 * @property-read string $genericUrl
 * @property-read string $previewUrl
 * @property-read string mimeType
 * @property-read int fileSize
 * @property-read array|null metaData
 * @property-read string createdAt
 * @property-read string|null modifiedAt
 */
final class FrontifyAsset {

    private $attributes = [];

    public function __construct(
        int $id,
        string $title,
        string $name,
        ?string $description,
        int $width,
        int $height,
        string $ext,
        string $mimeType,
        string $genericUrl,
        string $previewUrl,
        string $createdAt,
        string $modifiedAt,
        string $fileSize,
        ?array $metaData
    ) {
        // Append extension. Always needed for typo3
        $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($fileExtension !== $ext) {
            $name = $name . '.' . $ext;
        }

        $this->attributes = [
            'id' => $id,
            'description' => $description,
            'identifier' => Frontify::identifierByIdAndUrl($id, $genericUrl),
            'title' => $title,
            'name' => $name,
            'width' => $width,
            'height' => $height,
            'ext' => $ext,
            'mimeType' => $mimeType,
            'genericUrl' => $genericUrl,
            'previewUrl' => $previewUrl,
            'createdAt' => $createdAt,
            'modifiedAt' => $modifiedAt,
            'fileSize' => $fileSize,
            'metaData' => $metaData ?? []
        ];
    }

    public function __isset($name) {
        return array_key_exists($name, $this->attributes);
    }

    public function __get($name) {
        if ($this->__isset($name)) {
            return $this->attributes[$name];
        }

        throw new \Exception('Attribute not defined: ' . $name);
    }
}