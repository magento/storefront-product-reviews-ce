<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model\Storage\Client;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\RuntimeException;

/**
 * Connection pull class.
 */
class ConnectionPull
{
    /**
     * @var Client[]
     */
    private $connectionPull;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get Elasticsearch connection.
     *
     * @return Client
     *
     * @throws RuntimeException
     */
    public function getConnection()
    {
        $pid = getmypid();
        if (!isset($this->client[$pid])) {
            $config = $this->config->buildConfig();
            $this->connectionPull[$pid] = ClientBuilder::fromConfig($config, true);
        }

        return $this->connectionPull[$pid];
    }
}
