<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine;

/**
 * Import classes
 */
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * ConnectionFactory
 */
final class ConnectionFactory
{

    /**
     * Creates a new connection instance from the given parameters
     *
     * @param array $parameters
     *
     * @return Connection
     */
    public function createConnection(array $parameters) : Connection
    {
        $configuration = new Configuration();

        if (isset($parameters['sql_logger'])) {
            $configuration->setSQLLogger($parameters['sql_logger']);
        }

        if (isset($parameters['result_cache'])) {
            Helper::setCacheToConfiguration($configuration, $parameters['result_cache'], 'ResultCache');
        }

        if (isset($parameters['schema_assets_filter'])) {
            $configuration->setSchemaAssetsFilter($parameters['schema_assets_filter']);
        }

        if (isset($parameters['auto_commit'])) {
            $configuration->setAutoCommit($parameters['auto_commit']);
        }

        if (isset($parameters['middlewares'])) {
            $configuration->setMiddlewares($parameters['middlewares']);
        }

        return DriverManager::getConnection(
            $parameters['connection'] ?? [],
            $configuration,
            $parameters['event_manager'] ?? null
        );
    }
}
