<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model\Storage\Client;

use Magento\ReviewsStorefront\Model\Storage\Data\DocumentFactory;
use Magento\ReviewsStorefront\Model\Storage\Data\DocumentIteratorFactory;
use Magento\ReviewsStorefront\Model\Storage\Data\EntryIteratorInterface;
use Magento\ReviewsStorefront\Model\Storage\Data\SearchResultIteratorFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * Elasticsearch client adapter for read access operations.
 */
class ElasticsearchQuery implements QueryInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConnectionPull
     */
    private $connectionPull;

    /**
     * @var DocumentFactory
     */
    private $documentFactory;

    /**
     * @var DocumentIteratorFactory
     */
    private $documentIteratorFactory;

    /**
     * @var SearchResultIteratorFactory
     */
    private $searchResultIteratorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Config $config
     * @param ConnectionPull $connectionPull
     * @param DocumentFactory $documentFactory
     * @param DocumentIteratorFactory $documentIteratorFactory
     * @param SearchResultIteratorFactory $searchResultIteratorFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        ConnectionPull $connectionPull,
        DocumentFactory $documentFactory,
        DocumentIteratorFactory $documentIteratorFactory,
        SearchResultIteratorFactory $searchResultIteratorFactory,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->documentFactory = $documentFactory;
        $this->documentIteratorFactory = $documentIteratorFactory;
        $this->searchResultIteratorFactory = $searchResultIteratorFactory;
        $this->connectionPull = $connectionPull;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getEntries(string $indexName, string $entityName, array $ids, array $fields): EntryIteratorInterface
    {
        $query = [
            'index' => $indexName,
            'type' => $entityName,
            '_source' => $fields,
            'body' => ['ids' => \array_values($ids)],
        ];

        try {
            $result = $this->connectionPull->getConnection()->mget($query);
        } catch (\Throwable $throwable) {
            throw new RuntimeException(
                __("Storage error: {$throwable->getMessage()} Query was:" . \json_encode($query)),
                $throwable
            );
        }

        $this->checkErrors($result, $indexName);

        return $this->documentIteratorFactory->create($ids, $result);
    }

    /**
     * @inheritdoc
     */
    public function searchFilteredEntries(
        string $indexName,
        string $entityName,
        array $terms,
        ?int $size,
        ?int $cursor
    ): EntryIteratorInterface {
        $searchBody = [];

        foreach ($terms as $key => $value) {
            $searchBody['query']['bool']['filter'][]['term'][$key] = $value;
        }

        if (null !== $size) {
            $searchBody['size'] = $size;
            $searchBody['sort'][] = ['_id' => 'desc'];
        }

        if ($cursor > 0) {
            $searchBody['search_after'] = [$cursor];
        }

        return $this->searchResultIteratorFactory->create($this->searchRequest($indexName, $entityName, $searchBody));
    }

    /**
     * @inheritdoc
     */
    public function getEntriesCount(string $indexName, string $entityName, array $terms): int
    {
        $searchBody['size'] = 0;

        foreach ($terms as $key => $value) {
            $searchBody['aggs']['entries_count']['filter']['bool']['filter'][]['term'][$key] = $value;
        }

        $result = $this->searchRequest($indexName, $entityName, $searchBody);

        return $result['aggregations']['entries_count']['doc_count'] ?? 0;
    }

    /**
     * Perform client search request.
     *
     * @param string $indexName
     * @param string $entityName
     * @param array $searchBody
     *
     * @return array
     *
     * @throws RuntimeException
     */
    private function searchRequest(string $indexName, string $entityName, array $searchBody): array
    {
        $query = [
            'index' => $indexName,
            'type' => $entityName,
            'body' => $searchBody,
        ];

        try {
            $result = $this->connectionPull->getConnection()->search($query);
        } catch (\Throwable $throwable) {
            throw new RuntimeException(
                __("Storage error: {$throwable->getMessage()} Query was:" . \json_encode($query)),
                $throwable
            );
        }

        return $result;
    }

    /**
     * Handle the error occurrences of each returned document.
     *
     * @param array $result
     * @param string $indexName
     *
     * @return void
     *
     * @throws NotFoundException
     */
    private function checkErrors(array $result, string $indexName): void
    {
        $errors = [];
        $notFound = [];

        if (!isset($result['docs'])) {
            return;
        }

        foreach ($result['docs'] as $doc) {
            if (!empty($doc['error'])) {
                $errors [] = \sprintf("Entity id: %d\nReason: %s", $doc['_id'], $doc['error']['reason']);
            } elseif (isset($doc['found']) && false === $doc['found']) {
                $notFound[] = $doc['_id'];
            }
        }

        if (!empty($errors)) {
            throw new NotFoundException(__("Index name: {$indexName}\nList of errors: '" . \implode(', ', $errors)));
        }

        if (!empty($notFound)) {
            $this->logger->notice(\sprintf('Items "%s" not found in index %s', \implode(', ', $notFound), $indexName));
        }
    }
}
