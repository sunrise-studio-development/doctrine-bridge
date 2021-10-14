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
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Configuration as DbalConfiguration;
use Doctrine\ORM\Configuration as OrmConfiguration;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Import functions
 */
use function method_exists;

/**
 * Helper
 */
final class Helper
{

    /**
     * @param Cache|CacheItemPoolInterface $cache
     * @param string $type
     * @param DbalConfiguration|OrmConfiguration $configuration
     *
     * @return void
     */
    public static function setCacheToConfiguration($cache, string $type, $configuration) : void
    {
        $newSetter = 'set' . $type;
        $oldSetter = 'set' . $type . 'Impl';

        if ($cache instanceof Cache) {
            if (method_exists($configuration, $newSetter)) {
                $configuration->{$newSetter}(CacheAdapter::wrap($cache));
            } else {
                $configuration->{$oldSetter}($cache);
            }
        } elseif ($cache instanceof CacheItemPoolInterface) {
            if (method_exists($configuration, $newSetter)) {
                $configuration->{$newSetter}($cache);
            } else {
                $configuration->{$oldSetter}(DoctrineProvider::wrap($cache));
            }
        }
    }
}
