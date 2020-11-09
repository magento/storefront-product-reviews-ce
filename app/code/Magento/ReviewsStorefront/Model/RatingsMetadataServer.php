<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model;

use Magento\ReviewsStorefrontApi\Api\Data\DeleteRatingsMetadataRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\DeleteRatingsMetadataResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\DeleteRatingsMetadataResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\ImportRatingsMetadataRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ImportRatingsMetadataResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\ImportRatingsMetadataResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\Data\RatingMetadataArrayMapper;
use Magento\ReviewsStorefrontApi\Api\Data\RatingMetadataMapper;
use Magento\ReviewsStorefrontApi\Api\Data\RatingsMetadataRequestInterface;
use Magento\ReviewsStorefrontApi\Api\Data\RatingsMetadataResponseInterface;
use Magento\ReviewsStorefrontApi\Api\Data\RatingsMetadataResponseInterfaceFactory;
use Magento\ReviewsStorefrontApi\Api\RatingsMetadataServerInterface;
use Magento\ReviewsStorefront\DataProvider\RatingMetadataProvider;
use Psr\Log\LoggerInterface;

/**
 * Class for retrieving & importing rating metadata
 */
class RatingsMetadataServer implements RatingsMetadataServerInterface
{
    /**
     * @var RatingMetadataArrayMapper
     */
    private $ratingMetadataArrayMapper;

    /**
     * @var ImportRatingsMetadataResponseInterfaceFactory
     */
    private $importRatingsMetadataResponseInterfaceFactory;

    /**
     * @var DeleteRatingsMetadataResponseInterfaceFactory
     */
    private $deleteRatingsMetadataResponseInterfaceFactory;

    /**
     * @var RatingsMetadataResponseInterfaceFactory
     */
    private $ratingsMetadataResponseInterfaceFactory;

    /**
     * @var StorageRepository
     */
    private $storageRepository;

    /**
     * @var RatingMetadataProvider
     */
    private $ratingMetadataProvider;

    /**
     * @var RatingMetadataMapper
     */
    private $ratingMetadataMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RatingMetadataArrayMapper $ratingMetadataArrayMapper
     * @param ImportRatingsMetadataResponseInterfaceFactory $importRatingsMetadataResponseInterfaceFactory
     * @param DeleteRatingsMetadataResponseInterfaceFactory $deleteRatingsMetadataResponseInterfaceFactory
     * @param RatingsMetadataResponseInterfaceFactory $ratingsMetadataResponseInterfaceFactory
     * @param StorageRepository $storageRepository
     * @param RatingMetadataProvider $ratingMetadataProvider
     * @param RatingMetadataMapper $ratingMetadataMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        RatingMetadataArrayMapper $ratingMetadataArrayMapper,
        ImportRatingsMetadataResponseInterfaceFactory $importRatingsMetadataResponseInterfaceFactory,
        DeleteRatingsMetadataResponseInterfaceFactory $deleteRatingsMetadataResponseInterfaceFactory,
        RatingsMetadataResponseInterfaceFactory $ratingsMetadataResponseInterfaceFactory,
        StorageRepository $storageRepository,
        RatingMetadataProvider $ratingMetadataProvider,
        RatingMetadataMapper $ratingMetadataMapper,
        LoggerInterface $logger
    ) {
        $this->ratingMetadataArrayMapper = $ratingMetadataArrayMapper;
        $this->importRatingsMetadataResponseInterfaceFactory = $importRatingsMetadataResponseInterfaceFactory;
        $this->deleteRatingsMetadataResponseInterfaceFactory = $deleteRatingsMetadataResponseInterfaceFactory;
        $this->ratingsMetadataResponseInterfaceFactory = $ratingsMetadataResponseInterfaceFactory;
        $this->storageRepository = $storageRepository;
        $this->ratingMetadataProvider = $ratingMetadataProvider;
        $this->ratingMetadataMapper = $ratingMetadataMapper;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function importRatingsMetadata(
        ImportRatingsMetadataRequestInterface $request
    ): ImportRatingsMetadataResponseInterface {
        $response = $this->importRatingsMetadataResponseInterfaceFactory->create();

        try {
            $ratingsMetadataInElasticFormat = [];
            $storeCode = $request->getStore();

            foreach ($request->getMetadata() as $metadata) {
                $ratingMetadata = $this->ratingMetadataArrayMapper->convertToArray($metadata);
                $ratingsMetadataInElasticFormat['rating_metadata'][$storeCode]['save'][] = $ratingMetadata;
            }

            $this->storageRepository->saveToStorage($ratingsMetadataInElasticFormat);

            $response->setMessage('Records imported successfully');
            $response->setStatus(true);
        } catch (\Throwable $exception) {
            $response->setMessage(
                $message = \sprintf('Cannot process rating metadata import: %s', $exception->getMessage())
            );
            $response->setStatus(false);
            $this->logger->error($message, ['exception' => $exception]);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function deleteRatingsMetadata(
        DeleteRatingsMetadataRequestInterface $request
    ): DeleteRatingsMetadataResponseInterface {
        $response = $this->deleteRatingsMetadataResponseInterfaceFactory->create();

        try {
            $ratingsMetadataInElasticFormat = [
                'rating_metadata' => [
                    $request->getStore() => [
                        'delete' => $request->getRatingIds(),
                    ]
                ]
            ];

            $this->storageRepository->saveToStorage($ratingsMetadataInElasticFormat);

            $response->setMessage('Ratings metadata was removed successfully');
            $response->setStatus(true);
        } catch (\Throwable $exception) {
            $response->setMessage(
                $message = \sprintf('Cannot process rating metadata delete operation: %s', $exception->getMessage())
            );
            $response->setStatus(false);
            $this->logger->error($message, ['exception' => $exception]);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getRatingsMetadata(
        RatingsMetadataRequestInterface $request
    ): RatingsMetadataResponseInterface {
        $items = [];
        $metadata = $this->ratingMetadataProvider->fetch($request->getRatingIds(), $request->getStore());

        foreach ($metadata as $data) {
            $items[] = $this->ratingMetadataMapper->setData($data)->build();
        }

        $result = $this->ratingsMetadataResponseInterfaceFactory->create();
        $result->setItems($items);

        return $result;
    }
}
