<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-17 */

namespace Frontify\Typo3\Storage;

use Frontify\Typo3\ApiServiceProvider;
use Frontify\Typo3\Asset\FrontifyAsset;
use Frontify\Typo3\Utility\Frontify;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SysLog\Action\Cache;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontifyDriver implements DriverInterface {

    public const ROOT_FOLDER = '';

    /** @var int */
    protected $storageUid;

    /** @var int */
    protected $capabilities;

    /**
     * @var
     */
    private $rootDirectory;

    private $frontifyAssetCache = [];

    /**
     * FrontifyDriver constructor.
     */
    public function __construct() {
        $this->capabilities = ResourceStorage::CAPABILITY_BROWSABLE | ResourceStorage::CAPABILITY_PUBLIC | ResourceStorage::CAPABILITY_WRITABLE;
    }

    /**
     *
     */
    public function processConfiguration() {
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid($storageUid) {
        $this->storageUid = $storageUid;
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize() {
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities() {
        return $this->capabilities;
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     *
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities) {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     *
     * @return bool
     */
    public function hasCapability($capability) {
        return $this->capabilities & $capability === (int) $capability;
    }

    /**
     * Frontify is case sensitive
     *
     * @return bool
     */
    public function isCaseSensitiveFileSystem() {
        return true;
    }

    /**
     * Frontify allows all file name
     *
     * @param string $fileName
     * @param string $charset
     *
     * @return string
     */
    public function sanitizeFileName($fileName, $charset = '') {
        return $fileName;
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     *
     * @return string
     */
    public function hashIdentifier($identifier) {
        return md5($identifier);
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder() {
        return self::ROOT_FOLDER;
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder() {
        return self::ROOT_FOLDER;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     *
     * @return string
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier) {
        return self::ROOT_FOLDER;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to public web path (rawurlencoded).
     *
     * @param string $identifier
     *
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($identifier) {
        return Frontify::extractUrl($identifier);
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     *
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false) {
        throw new \Exception('Creating a folder is not possible');
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     *
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName) {
        throw new \Exception('Renaming a folder is not possible');
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     *
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false) {
        throw new \Exception('Deleting a folder is not possible');
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     *
     * @return bool
     */
    public function fileExists($fileIdentifier) {
        if (array_key_exists($fileIdentifier, $this->frontifyAssetCache)) {
            return true;
        }

        return $this->isValidIdentifier($fileIdentifier);
    }

    /**
     * Validates the identifier to be a valid frontify identifier
     *
     * @param string $identifier
     *
     * @return bool
     */
    private function isValidIdentifier(string $identifier): bool {
        $data = Frontify::extractIdAndToken($identifier);

        if (!$data) {
            return false;
        }

        return Frontify::isValidUrl($data[1]);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     *
     * @return bool
     */
    public function folderExists($folderIdentifier) {
        return $folderIdentifier === self::ROOT_FOLDER;
    }

    public function isFolderEmpty($folderIdentifier) {
        return false;
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true) {
        throw new \Exception('You can not add a file to frontify.');
    }

    public function createFile($fileName, $parentFolderIdentifier) {
        throw new \Exception('You can not create a file on frontify');
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName) {
        throw new \Exception('You can not copy a file within frontify.');
    }

    public function renameFile($fileIdentifier, $newName) {
        throw new \Exception('You can not rename a file from frontify.');
    }

    public function replaceFile($fileIdentifier, $localFilePath) {
        throw new \Exception('You can not replace a file from frontify.');
    }

    public function deleteFile($fileIdentifier) {
        return true;
    }

    public function hash($fileIdentifier, $hashAlgorithm) {
        if ($hashAlgorithm === 'md5') {
            return md5($fileIdentifier);
        }

        if ($hashAlgorithm === 'sha1') {
            return sha1($fileIdentifier);
        }

        throw new \Exception(sprintf('Hash algorithm `%s` not implemented.', $hashAlgorithm));
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName) {
        throw new \Exception('You can not move a file in frontify.');
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
        throw new \Exception('You can not move a folder in frontify.');
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
        throw new \Exception('You can not copy a folder in frontify.');
    }

    public function getFileContents($fileIdentifier) {
        return file_get_contents(Frontify::extractUrl($fileIdentifier));
    }

    public function setFileContents($fileIdentifier, $contents) {
        throw new \Exception('You can not set the file on frontify.');
    }

    public function fileExistsInFolder($fileName, $folderIdentifier) {
        return false;
    }

    public function folderExistsInFolder($folderName, $folderIdentifier) {
        return false;
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true) {
        $tmpName = GeneralUtility::tempnam('frontify-');
        $resource = fopen($tmpName, 'w+');

        if (!$resource) {
            return false;
        }

        GeneralUtility::makeInstance(ApiServiceProvider::class)->writeToResource($fileIdentifier, $resource);

        if (is_resource($resource)) {
            fclose($resource);
        }

        return $tmpName;
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     *
     * @return array
     */
    public function getPermissions($identifier) {
        return [
            'r' => $this->fileExists($identifier),
            'w' => false,
        ];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     */
    public function dumpFileContents($identifier) {
        readfile(Frontify::extractUrl($identifier), 0);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     *
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier) {
        return $folderIdentifier === self::ROOT_FOLDER;
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     *
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []) {
        /** @var ApiServiceProvider $apiServiceProvider */
        $apiServiceProvider = GeneralUtility::makeInstance(ApiServiceProvider::class);
        $asset = $apiServiceProvider->getAssetByIdentifier($fileIdentifier);

        $info = [
            'storage' => $this->storageUid,
            'missing' => 0,
            'identifier' => '/' . $asset->identifier,
            'identifier_hash' => $this->hashIdentifier($asset->identifier),
            'extension' => $asset->ext,
            'mime_type' => $asset->mimeType,
            'mimetype' => $asset->mimeType,
            'name' => $asset->name,
            'size' => $asset->fileSize,
            'creation_date' => strtotime($asset->createdAt),
            'modification_date' => strtotime($asset->modifiedAt),
        ];

        if (empty($propertiesToExtract)) {
            return $info;
        }

        $data = [];
        foreach ($propertiesToExtract as $property) {
            if (array_key_exists($property, $info)) {
                $data[$property] = $info[$property];
            }
        }

        return $data;
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     *
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier) {
        return [
            'identifier' => $folderIdentifier,
            'name' => 'Frontify',
            'mtime' => 0,
            'ctime' => 0,
            'storage' => $this->storageUid
        ];
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     *
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier) {
        return '';
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     *
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false) {
        return [];
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     *
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier) {
        return '';
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     *
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false) {
        return [];
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     *
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []) {
        return 0;
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     *
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = []) {
        return 0;
    }
}