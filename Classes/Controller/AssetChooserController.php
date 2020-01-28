<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2020-01-16 */

namespace Frontify\Typo3\Controller;

use Frontify\Typo3\ApiServiceProvider;
use Frontify\Typo3\Asset\FrontifyAsset;
use Frontify\Typo3\Storage\FrontifyDriver;
use Frontify\Typo3\Storage\StorageUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AssetChooserController {

    /** @var ApiServiceProvider */
    private $apiServiceProvider;

    public function __construct() {
        $this->apiServiceProvider = GeneralUtility::makeInstance(ApiServiceProvider::class);
    }

    public function addFiles(ServerRequestInterface $request) {
        /** @var FrontifyAsset[] $assets */
        $assets = array_map([$this, 'mapToFrontifyAssets'], $request->getParsedBody()['assets']);
        $storage = $this->getStorage();

        $fileIds = [];
        foreach ($assets as $asset) {
            $file = $storage->getFile('/' . $asset->identifier);
            $fileIds[] = $file->getUid();
        }

        $response = new Response();
        $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode([
            'success' => true,
            'file_ids' => $fileIds
        ]));
        return $response;
    }

    public function assetToFileRecord(FrontifyAsset $assetInput, ResourceStorage $storage) {
        return [
            'missing' => 0,
            'type' => File::FILETYPE_IMAGE,
            'storage' => $storage->getUid(),
            'identifier' => '/' . $assetInput->identifier,
            'identifier_hash' => md5($assetInput->identifier),
            'extension' => $assetInput->ext,
            'mime_type' => $assetInput->mimeType,
            'name' => $assetInput->name,
            'sha1' => sha1($assetInput->id),
            'size' => $assetInput->fileSize,
            'creation_date' => strtotime($assetInput->createdAt),
            'modification_date' => strtotime($assetInput->modifiedAt),
            'folder_hash' => md5('/'),
        ];
    }

    protected function getFileIndexRepository() {
        return FileIndexRepository::getInstance();
    }

    protected function getStorage(): ResourceStorage {
        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->findByStorageType('frontify')[0];
        return $storage;

        // $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        // return $resourceFactory->getDefaultStorage();
    }

    protected function mapToFrontifyAssets(array $asset): FrontifyAsset {
        return $this->apiServiceProvider->getAssetById($asset['id']);
    }

}