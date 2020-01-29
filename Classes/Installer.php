<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-18 */

namespace Frontify\Typo3;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

class Installer {

    public function execute(string $extensionKey) {
        // Only run after the installation of frontify typo3
        if ($extensionKey !== 'frontify_typo3') {
            return;
        }

        // Only create the driver once.
        if (!$this->hasFrontifyDriver()) {
            $this->createDriver();
        }
    }

    private function hasFrontifyDriver(): bool {
        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        return count($storageRepository->findByStorageType('frontify') ?? []) >= 1;
    }

    protected function getDatabaseConnection(string $table): Connection {
        return GeneralUtility
            ::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);
    }

    protected function createDriver() {
        $driverValues = [
            'pid' => 0,
            'tstamp' => time(),
            'crdate' => time(),
            'cruser_id' => 0,
            'deleted' => 0,
            'description' => 'This is the Frontify File System',
            'name' => 'Frontify/ (auto-created)',
            'driver' => 'frontify',

            // No configuration is needed.
            'configuration' => '',
            'is_browsable' => 1,
            'is_public' => 1,
            'is_default' => 0,
            'is_online' => 1,
            'is_writable' => 0,
            'auto_extract_metadata' => 1,
            'processingfolder' => '',
        ];

        $db = $this->getDatabaseConnection('sys_file_storage');
        $db->insert('sys_file_storage', $driverValues);
    }

}