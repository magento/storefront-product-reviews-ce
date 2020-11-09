<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model\Storage\Client;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\ReviewsStorefront\Model\Storage\Data\EntryIteratorInterface;

/**
 * Storage client interface for Read access operations.
 */
interface QueryInterface
{
    /**
     * Access entries of Entity by array of unique identifier.
     *
     * $fields argument needs to specify array of fields that need to retrieve from document to avoid situation
     * of retrieving entire document that could badly influence on bandwidth, elapsed time and performance in general.
     *
     * Any query operations MUST work only through alias endpoint to avoid data integrity problems.
     *
     * @param string $indexName
     * @param string $entityName
     * @param array $ids
     * @param array $fields
     *
     * @return EntryIteratorInterface
     *
     * @throws NotFoundException
     * @throws RuntimeException
     */
    public function getEntries(
        string $indexName,
        string $entityName,
        array $ids,
        array $fields
    ): EntryIteratorInterface;

    /**
     * Search entries by specified filter terms.
     *
     * @param string $indexName
     * @param string $entityName
     * @param array $terms
     * @param int|null $size
     * @param int|null $cursor
     *
     * @return EntryIteratorInterface
     *
     * @throws RuntimeException
     */
    public function searchFilteredEntries(
        string $indexName,
        string $entityName,
        array $terms,
        ?int $size,
        ?int $cursor
    ): EntryIteratorInterface;

    /**
     * Retrieve entries count by specified filter terms.
     *
     * @param string $indexName
     * @param string $entityName
     * @param array $terms
     *
     * @return int
     *
     * @throws RuntimeException
     */
    public function getEntriesCount(string $indexName, string $entityName, array $terms): int;
}
