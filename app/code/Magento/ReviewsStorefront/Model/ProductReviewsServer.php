<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model;

use Magento\ReviewsStorefrontApi\Api\Data\CustomerProductReviewRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\CustomerProductReviewResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\CustomerProductReviewResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\DeleteReviewsRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\DeleteReviewsResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\DeleteReviewsResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\ImportReviewArrayMapper;
use Magento\ReviewsStorefrontApi\Api\Data\ImportReviewsRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ImportReviewsResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ImportReviewsResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\PaginationResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewCountRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewCountResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewCountResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ProductReviewResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\ReadReviewMapper;
use Magento\ReviewsStorefrontApi\Api\ProductReviewsServerInterface;
use Magento\ReviewsStorefront\DataProvider\ReviewDataProvider;
use Psr\Log\LoggerInterface;

/**
 * Class for retrieving & importing reviews data
 */
class ProductReviewsServer implements ProductReviewsServerInterface
{
    /**
     * @var ImportReviewArrayMapper
     */
    private $importReviewArrayMapper;

    /**
     * @var ImportReviewsResponseInterfaceFactory
     */
    private $importReviewsResponseInterfaceFactory;

    /**
     * @var DeleteReviewsResponseInterfaceFactory
     */
    private $deleteReviewsResponseInterfaceFactory;

    /**
     * @var StorageRepository
     */
    private $storageRepository;

    /**
     * @var ReviewDataProvider
     */
    private $reviewDataProvider;

    /**
     * @var ReadReviewMapper
     */
    private $readReviewMapper;

    /**
     * @var ProductReviewResponseInterfaceFactory
     */
    private $productReviewResponseInterfaceFactory;

    /**
     * @var CustomerProductReviewResponseInterfaceFactory
     */
    private $customerProductReviewResponseInterfaceFactory;

    /**
     * @var PaginationResponseInterfaceFactory
     */
    private $paginationResponseInterfaceFactory;

    /**
     * @var ProductReviewCountResponseInterfaceFactory
     */
    private $productReviewCountResponseInterfaceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ImportReviewArrayMapper $importReviewArrayMapper
     * @param ImportReviewsResponseInterfaceFactory $importReviewsResponseInterfaceFactory
     * @param DeleteReviewsResponseInterfaceFactory $deleteReviewsResponseInterfaceFactory
     * @param StorageRepository $storageRepository
     * @param ReviewDataProvider $reviewDataProvider
     * @param ReadReviewMapper $readReviewMapper
     * @param ProductReviewResponseInterfaceFactory $productReviewResponseInterfaceFactory
     * @param CustomerProductReviewResponseInterfaceFactory $customerProductReviewResponseInterfaceFactory
     * @param PaginationResponseInterfaceFactory $paginationResponseInterfaceFactory
     * @param ProductReviewCountResponseInterfaceFactory $productReviewCountResponseInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImportReviewArrayMapper $importReviewArrayMapper,
        ImportReviewsResponseInterfaceFactory $importReviewsResponseInterfaceFactory,
        DeleteReviewsResponseInterfaceFactory $deleteReviewsResponseInterfaceFactory,
        StorageRepository $storageRepository,
        ReviewDataProvider $reviewDataProvider,
        ReadReviewMapper $readReviewMapper,
        ProductReviewResponseInterfaceFactory $productReviewResponseInterfaceFactory,
        CustomerProductReviewResponseInterfaceFactory $customerProductReviewResponseInterfaceFactory,
        PaginationResponseInterfaceFactory $paginationResponseInterfaceFactory,
        ProductReviewCountResponseInterfaceFactory $productReviewCountResponseInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->importReviewArrayMapper = $importReviewArrayMapper;
        $this->importReviewsResponseInterfaceFactory = $importReviewsResponseInterfaceFactory;
        $this->deleteReviewsResponseInterfaceFactory = $deleteReviewsResponseInterfaceFactory;
        $this->storageRepository = $storageRepository;
        $this->reviewDataProvider = $reviewDataProvider;
        $this->readReviewMapper = $readReviewMapper;
        $this->productReviewResponseInterfaceFactory = $productReviewResponseInterfaceFactory;
        $this->customerProductReviewResponseInterfaceFactory = $customerProductReviewResponseInterfaceFactory;
        $this->paginationResponseInterfaceFactory = $paginationResponseInterfaceFactory;
        $this->productReviewCountResponseInterfaceFactory = $productReviewCountResponseInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function importProductReviews(ImportReviewsRequestInterface $request): ImportReviewsResponseInterface
    {
        $response = $this->importReviewsResponseInterfaceFactory->create();

        try {
            $reviewsInElasticFormat = [];

            foreach ($request->getReviews() as $review) {
                $review = $this->importReviewArrayMapper->convertToArray($review);
                $reviewsInElasticFormat['review'][$request->getStore()]['save'][] = $review;
            }

            $this->storageRepository->saveToStorage($reviewsInElasticFormat);

            $response->setMessage('Records imported successfully');
            $response->setStatus(true);
        } catch (\Throwable $exception) {
            $response->setMessage($message = \sprintf('Cannot process reviews import: %s', $exception->getMessage()));
            $response->setStatus(false);
            $this->logger->error($message, ['exception' => $exception]);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function deleteProductReviews(DeleteReviewsRequestInterface $request): DeleteReviewsResponseInterface
    {
        $response = $this->deleteReviewsResponseInterfaceFactory->create();

        try {
            $reviewsInElasticFormat = [
                'review' => [
                    $request->getStore() => [
                        'delete' => $request->getReviewIds(),
                    ]
                ]
            ];

            $this->storageRepository->saveToStorage($reviewsInElasticFormat);

            $response->setMessage('Reviews were removed successfully');
            $response->setStatus(true);
        } catch (\Throwable $exception) {
            $response->setMessage(
                $message = \sprintf('Cannot process reviews delete operation: %s', $exception->getMessage())
            );
            $response->setStatus(false);
            $this->logger->error($message, ['exception' => $exception]);
        }

        return $response;
    }

    /**
     * @inheritdoc
     * TODO encapsulate common (getProductReviews + getCustomerProductReviews) part into private method
     */
    public function getProductReviews(ProductReviewRequestInterface $request): ProductReviewResponseInterface
    {
        $items = [];
        $reviews = $this->reviewDataProvider->fetchByProductId(
            $request->getProductId(),
            $request->getStore(),
            $request->getPagination()
        );

        foreach ($reviews as $review) {
            $items[$review['id']] = $this->readReviewMapper->setData($review)->build();
        }

        $result = $this->productReviewResponseInterfaceFactory->create();
        $result->setItems($items);

        if (!empty($request->getPagination()) && !empty($items)) {
            $paginationResult = $this->paginationResponseInterfaceFactory->create();
            $paginationResult->setPageSize(\count($items));
            $paginationResult->setCursor(\array_key_last($items));
            $result->setPagination($paginationResult);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerProductReviews(
        CustomerProductReviewRequestInterface $request
    ): CustomerProductReviewResponseInterface {
        $items = [];
        $reviews = $this->reviewDataProvider->fetchByCustomerId(
            $request->getCustomerId(),
            $request->getStore(),
            $request->getPagination()
        );

        foreach ($reviews as $review) {
            $items[] = $this->readReviewMapper->setData($review)->build();
        }

        $result = $this->customerProductReviewResponseInterfaceFactory->create();
        $result->setItems($items);

        if (!empty($request->getPagination()) && !empty($items)) {
            $paginationResult = $this->paginationResponseInterfaceFactory->create();
            $paginationResult->setPageSize(\count($items));
            $paginationResult->setCursor(\array_key_last($items));
            $result->setPagination($paginationResult);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getProductReviewCount(
        ProductReviewCountRequestInterface $request
    ): ProductReviewCountResponseInterface {
        $reviewCount = $this->reviewDataProvider->getProductReviewsCount(
            $request->getProductId(),
            $request->getStore()
        );

        $result = $this->productReviewCountResponseInterfaceFactory->create();
        $result->setReviewCount($reviewCount);

        return $result;
    }
}
