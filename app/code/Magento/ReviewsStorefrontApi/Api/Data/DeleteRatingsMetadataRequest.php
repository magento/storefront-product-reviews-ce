<?php
# Generated by the Magento PHP proto generator.  DO NOT EDIT!

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefrontApi\Api\Data;

/**
 * Autogenerated description for DeleteRatingsMetadataRequest class
 *
 * phpcs:disable Magento2.PHP.FinalImplementation
 * @SuppressWarnings(PHPMD)
 * @SuppressWarnings(PHPCPD)
 */
final class DeleteRatingsMetadataRequest implements DeleteRatingsMetadataRequestInterface
{

    /**
     * @var array
     */
    private $ratingIds;

    /**
     * @var string
     */
    private $store;
    
    /**
     * @inheritdoc
     *
     * @return string[]
     */
    public function getRatingIds(): array
    {
        return (array) $this->ratingIds;
    }
    
    /**
     * @inheritdoc
     *
     * @param string[] $value
     * @return void
     */
    public function setRatingIds(array $value): void
    {
        $this->ratingIds = $value;
    }
    
    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getStore(): string
    {
        return (string) $this->store;
    }
    
    /**
     * @inheritdoc
     *
     * @param string $value
     * @return void
     */
    public function setStore(string $value): void
    {
        $this->store = $value;
    }
}
