<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\Workaround;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Invoke consumers to push data from Magento Monolith to storefront service
 */
class ReviewsConsumerInvoker
{
    /**
     * Batch size
     */
    private const BATCHSIZE = 1000;

    /**
     * List of consumers
     */
    private const CONSUMERS = [
        'export.product.reviews.consumer',
        'export.rating.metadata.consumer',
    ];

    /**
     * Invoke consumers
     *
     * @param array $consumersToProcess
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function invoke(array $consumersToProcess = []): void
    {
        /** @var ConsumerFactory $consumerFactory */
        $consumerFactory = Bootstrap::getObjectManager()->create(ConsumerFactory::class);
        $consumersToProcess = $consumersToProcess ?: self::CONSUMERS;

        foreach ($consumersToProcess as $consumerName) {
            $consumer = $consumerFactory->get($consumerName, self::BATCHSIZE);
            $consumer->process(self::BATCHSIZE);
        }
    }
}
