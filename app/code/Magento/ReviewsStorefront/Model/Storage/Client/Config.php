<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\ReviewsStorefront\Model\Storage\Client;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\ReviewsStorefront\Model\Storage\Client\Config\EntityConfigInterface;
use Magento\ReviewsStorefront\Model\Storage\Client\Config\EntityConfigPool;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Config\File\ConfigFilePool;

/**
 * Client configuration.
 */
class Config
{
    /**
     * Default Application config.
     *
     * @var array
     */
    private static $DEFAULT_CONFIG = [
        'connections' => [
            'default' => [
                'protocol' => 'http',
                'hostname' => 'localhost',
                'port' => '9200',
                'username' => '',
                'password' => '',
                'timeout' => 3,
            ]
        ],
        'timeout' => 60,
        'alias_name' => 'catalog_storefront',
        'source_prefix' => 'catalog_storefront_v',
        'source_current_version' => 1,
    ];

    /**
     * @var array
     */
    private $connectionConfig;

    /**
     * @var array
     */
    private $config;

    /**
     * @var EntityConfigPool
     */
    private $entityConfigPool;

    /**
     * Initialize Elasticsearch Client
     *
     * @param Reader $configReader
     * @param EntityConfigPool $entityConfigPool
     *
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function __construct(Reader $configReader, EntityConfigPool $entityConfigPool)
    {
        $configData = $configReader->load(ConfigFilePool::APP_ENV);
        $this->config = isset($configData['catalog-store-front'])
            ? array_replace_recursive(self::$DEFAULT_CONFIG, $configData['catalog-store-front'])
            : self::$DEFAULT_CONFIG;
        $options = $this->config['connections']['default'];

        if (empty($options['hostname']) || ((!empty($options['enableAuth'])
                    && ($options['enableAuth'] == 1)) && (empty($options['username']) || empty($options['password'])))
        ) {
            throw new ConfigurationMismatchException(
                __('The search failed because of a search engine misconfiguration.')
            );
        }

        $this->connectionConfig = $options;
        $this->entityConfigPool = $entityConfigPool;
    }

    /**
     * Return connection config of the Client.
     *
     * @return array
     */
    public function getConnectionConfig(): array
    {
        return $this->connectionConfig;
    }

    /**
     * Get entity config instance.
     *
     * @param string $entityName
     *
     * @return EntityConfigInterface
     *
     * @throws NotFoundException
     */
    public function getEntityConfig(string $entityName): EntityConfigInterface
    {
        return $this->entityConfigPool->getConfig($entityName);
    }

    /**
     * Get alias name.
     *
     * @return string
     */
    public function getAliasName(): string
    {
        return $this->config['alias_name'];
    }

    /**
     * Get source prefix.
     *
     * @return string
     */
    public function getSourcePrefix(): string
    {
        return $this->config['source_prefix'];
    }

    /**
     * Get current source version.
     *
     * @return int
     */
    public function getCurrentSourceVersion(): int
    {
        return $this->config['source_current_version'];
    }

    /**
     * Build config.
     *
     * @return array
     */
    public function buildConfig(): array
    {
        $portString = '';
        if (!empty($this->connectionConfig['port'])) {
            $portString = ':' . $this->connectionConfig['port'];
        }

        $host = $this->connectionConfig['protocol'] . '://' . $this->connectionConfig['hostname'] . $portString;

        $result['hosts'] = [$host];

        return $result;
    }
}
