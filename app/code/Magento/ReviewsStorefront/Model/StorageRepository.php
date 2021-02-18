<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model;

use Magento\StorefrontStorage\Model\Client\CommandInterface;
use Magento\StorefrontStorage\Model\Client\DataDefinitionInterface;
use Magento\StorefrontStorage\Model\State;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

/**
 * Repository for storing data to data storage.
 */
class StorageRepository
{
    private const DELETE = 'delete';
    private const SAVE = 'save';

    /**
     * @var CommandInterface
     */
    private $storageWriteSource;

    /**
     * @var State
     */
    private $storageState;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DataDefinitionInterface
     */
    private $storageSchemaManager;

    /**
     * @param CommandInterface $storageWriteSource
     * @param DataDefinitionInterface $storageSchemaManager
     * @param State $storageState
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandInterface $storageWriteSource,
        DataDefinitionInterface $storageSchemaManager,
        State $storageState,
        LoggerInterface $logger
    ) {
        $this->storageWriteSource = $storageWriteSource;
        $this->storageSchemaManager = $storageSchemaManager;
        $this->storageState = $storageState;
        $this->logger = $logger;
    }

    /**
     * Save catalog data to the internal storage
     *
     * @param array $dataPerType
     *
     * @return void
     *
     * @throws BulkException
     * @throws CouldNotSaveException
     */
    public function saveToStorage(array $dataPerType): void
    {
        foreach ($dataPerType as $entityType => $dataPerStore) {
            foreach ($dataPerStore as $storeCode => $data) {
                $sourceName = $this->storageState->getCurrentDataSourceName([$storeCode, $entityType]);
                $this->deleteEntities($data[self::DELETE] ?? [], $sourceName, $entityType);
                $this->saveEntities($data[self::SAVE] ?? [], $sourceName, $entityType);
            }
        }
    }

    /**
     * Delete bulk of entities by data, source name and entity type
     *
     * @param array $data
     * @param string $sourceName
     * @param string $entityType
     *
     * @return void
     */
    private function deleteEntities(array $data, string $sourceName, string $entityType): void
    {
        if (empty($data)) {
            return;
        }

        if (!$this->storageSchemaManager->existsDataSource($sourceName)) {
            $this->logger->debug(
                \sprintf('Cannot delete entities "%s": Index "%s" does not exist', \implode(',', $data), $sourceName)
            );

            return;
        }

        $this->logger->debug(
            \sprintf('Delete from storage "%s" %s record(s)', $sourceName, \count($data)),
            ['verbose' => $data]
        );

        $this->storageWriteSource->bulkDelete($sourceName, $entityType, $data);
    }

    /**
     * Save bulk of entities by data, source name and entity type
     *
     * @param array $data
     * @param string $sourceName
     * @param string $entityType
     *
     * @return void
     *
     * @throws CouldNotSaveException
     * @throws BulkException
     */
    private function saveEntities(array $data, string $sourceName, string $entityType): void
    {
        if (empty($data)) {
            return;
        }

        $this->logger->debug(
            \sprintf('Save to storage "%s" %s record(s)', $sourceName, \count($data)),
            ['verbose' => $data]
        );

        if (!$this->storageSchemaManager->existsDataSource($sourceName)) {
            $this->storageSchemaManager->createDataSource($sourceName, []);
            $this->storageSchemaManager->createEntity($sourceName, $entityType, []);
        }

        $this->storageWriteSource->bulkInsert($sourceName, $entityType, $data);
    }
}
