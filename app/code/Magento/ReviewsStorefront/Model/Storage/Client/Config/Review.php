<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model\Storage\Client\Config;

/**
 * Review entity type config.
 */
class Review implements EntityConfigInterface
{
    /**
     * Entity name. Used to hold configuration for specific entity type and as a part of the storage name
     */
    public const ENTITY_NAME = 'review';

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        return [
            'dynamic_templates' => [
                [
                    'product_id_mapping' => [
                        'match' => 'product_id',
                        'mapping' => [
                            'index' => true,
                        ],
                    ],
                ],
                [
                    'customer_id_mapping' => [
                        'match' => 'customer_id',
                        'mapping' => [
                            'index' => true,
                        ],
                    ],
                ],
                [
                    'visibility_mapping' => [
                        'match' => 'visibility',
                        'mapping' => [
                            'index' => true,
                        ],
                    ],
                ],
                [
                    'default_mapping' => [
                        'match' => '*',
                        'match_mapping_type' => '*',
                        'mapping' => [
                            'index' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
