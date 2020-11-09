<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\DataProvider;

use Magento\ReviewsStorefront\Model\Storage\Client\QueryInterface;
use Magento\ReviewsStorefront\Model\Storage\State;
use Magento\ReviewsStorefront\Model\Storage\Client\Config\Review;
use Magento\ReviewsStorefrontApi\Api\Data\PaginationRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Review storage reader.
 */
class ReviewDataProvider
{
    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * @var State
     */
    private $storageState;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $pageSize;

    /**
     * @var int
     */
    private $cursor;

    /**
     * @param QueryInterface $query
     * @param State $storageState
     * @param LoggerInterface $logger
     * @param int $pageSize
     * @param int $cursor
     */
    public function __construct(
        QueryInterface $query,
        State $storageState,
        LoggerInterface $logger,
        int $pageSize = 12,
        int $cursor = 0
    ) {
        $this->query = $query;
        $this->storageState = $storageState;
        $this->logger = $logger;
        $this->pageSize = $pageSize;
        $this->cursor = $cursor;
    }

    /**
     * Fetch reviews by product id and scope code.
     *
     * @param string $productId
     * @param string $scope
     * @param PaginationRequestInterface|null $pagination
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function fetchByProductId(string $productId, string $scope, ?PaginationRequestInterface $pagination): array
    {
        return $this->fetchReviews(
            [
                'product_id' => $productId,
                'visibility' => $scope,
            ],
            $pagination
        );
    }

    /**
     * Fetch reviews by customer id and scope code.
     *
     * @param string $customerId
     * @param string $scope
     * @param PaginationRequestInterface|null $pagination
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function fetchByCustomerId(string $customerId, string $scope, ?PaginationRequestInterface $pagination): array
    {
        return $this->fetchReviews(
            [
                'customer_id' => $customerId,
                'visibility' => $scope,
            ],
            $pagination
        );
    }

    /**
     * Fetch reviews by specified parameters.
     *
     * @param array $params
     * @param PaginationRequestInterface|null $pagination
     *
     * @return array
     *
     * @throws \Throwable
     */
    private function fetchReviews(array $params, ?PaginationRequestInterface $pagination): array
    {
        try {
            $entities = $this->query->searchFilteredEntries(
                $this->storageState->getCurrentDataSourceName([Review::ENTITY_NAME]),
                Review::ENTITY_NAME,
                $params,
                $pagination ? $pagination->getSize() : $this->pageSize,
                $pagination ? $pagination->getCursor() : $this->cursor
            );
        } catch (\Throwable $e) {
            $this->logger->error($e);
            throw $e;
        }

        return $entities->toArray();
    }

    /**
     * Retrieve product reviews count
     *
     * @param string $productId
     * @param string $scope
     *
     * @return int
     *
     * @throws \Throwable
     */
    public function getProductReviewsCount(string $productId, string $scope): int
    {
        $storageName = $this->storageState->getCurrentDataSourceName([Review::ENTITY_NAME]);

        try {
            $reviewsCount = $this->query->getEntriesCount(
                $storageName,
                Review::ENTITY_NAME,
                ['product_id' => $productId, 'visibility' => $scope]
            );
        } catch (\Throwable $e) {
            $this->logger->error($e);
            throw $e;
        }

        return $reviewsCount;
    }
}
