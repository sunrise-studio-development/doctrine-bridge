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
     * @var array<string, mixed>
     */
    private const DEFAULT_PARAMS = [
        'auto_commit' => true,
    ];

    /**
     * @var array<string, string>
     */
    private const CONFIG_SETTER_MAP = [
        'auto_commit' => 'setAutoCommit',
        'schema_assets_filter' => 'setSchemaAssetsFilter',
        'middlewares' => 'setMiddlewares',
        'sql_logger' => 'setSQLLogger',
    ];

    /**
     * @var array<string, string>
     */
    private const CACHE_TYPE_MAP = [
        'result_cache' => 'ResultCache',
    ];

    /**
     * Creates a new connection instance from the given parameters
     *
     * @param array $parameters
     *
     * @return Connection
     */
    public function createConnection(array $parameters) : Connection
    {
        $parameters += self::DEFAULT_PARAMS;

        $configuration = new Configuration();

        foreach (self::CONFIG_SETTER_MAP as $parameter => $setter) {
            if (isset($parameters[$parameter])) {
                $configuration->{$setter}($parameters[$parameter]);
            }
        }

        foreach (self::CACHE_TYPE_MAP as $parameter => $type) {
            if (isset($parameters[$parameter])) {
                Helper::setCacheToConfiguration($configuration, $parameters[$parameter], $type);
            }
        }

        return DriverManager::getConnection(
            $parameters['connection'] ?? [],
            $configuration,
            $parameters['event_manager'] ?? null
        );
    }
}
