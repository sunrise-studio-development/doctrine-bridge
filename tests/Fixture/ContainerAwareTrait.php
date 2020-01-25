<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\ManagerRegistry;
use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Import functions
 */
use function DI\autowire;
use function DI\create;

/**
 * ContainerAwareTrait
 */
trait ContainerAwareTrait
{

    /**
     * Gets a container
     *
     * @return Container
     */
    private function getContainer() : Container
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->useAutowiring(false);

        $container = $builder->build();

        $container->set('doctrine', autowire(ManagerRegistry::class));

        $container->set('doctrine.configuration', [
            'foo' => [
                'metadata' => [
                    'sources' => [
                        __DIR__ . '/Entity',
                    ],
                ],
                'connection' => [
                    'url' => 'sqlite:///' . __DIR__ . '/../db/foo.sqlite',
                ],
                'proxyDir' => null,
                'cache' => create(ArrayCache::class),
            ],
            'bar' => [
                'metadata' => [
                    'sources' => [
                        __DIR__ . '/Entity',
                    ],
                ],
                'connection' => [
                    'url' => 'sqlite:///' . __DIR__ . '/../db/bar.sqlite',
                ],
                'proxyDir' => null,
                'cache' => create(ArrayCache::class),
            ],
        ]);

        return $container;
    }
}
