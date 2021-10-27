<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Symfony\Component\Cache\Adapter\ArrayAdapter as ArrayCache;
use InvalidArgumentException;

class EntityManagerRegistryTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testContracts() : void
    {
        $registry = new EntityManagerRegistry([]);

        $this->assertInstanceOf(ManagerRegistry::class, $registry);
    }

    public function testEmptyRegistry() : void
    {
        $registry = new EntityManagerRegistry([]);

        $this->assertSame('ORM', $registry->getName());

        $this->assertSame([], $registry->getConnectionNames());
        $this->assertSame([], $registry->getManagerNames());

        $this->assertSame('default', $registry->getDefaultConnectionName());
        $this->assertSame('default', $registry->getDefaultManagerName());
    }

    public function testCustomName() : void
    {
        $name = '5ddf785d-04b7-4a53-aa99-9af5c269f528';

        $registry = $this->getEntityManagerRegistry($name);

        $this->assertSame($name, $registry->getName());
    }

    public function testInitConnections() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame([
            'foo' => 'foo.conn', // first always is default...
            'bar' => 'bar.conn',
        ], $registry->getConnectionNames());

        $this->assertSame('foo', $registry->getDefaultConnectionName());
        $this->assertSame($registry->getConnection(), $registry->getConnection('foo'));
        $this->assertNotSame($registry->getConnection('foo'), $registry->getConnection('bar'));
    }

    public function testInitManagers() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame([
            'foo' => 'foo', // first always is default...
            'bar' => 'bar',
        ], $registry->getManagerNames());

        $this->assertSame('foo', $registry->getDefaultManagerName());
        $this->assertSame($registry->getManager(), $registry->getManager('foo'));
        $this->assertNotSame($registry->getManager('foo'), $registry->getManager('bar'));
    }

    public function testRegisterTypes() : void
    {
        $types = [
            '96fdd525-fefa-46a6-b725-0ea8deacac75' => \Doctrine\DBAL\Types\BooleanType::class,
            'd6bdfd25-9380-472d-9a6c-e68588634aee' => \Doctrine\DBAL\Types\IntegerType::class,
            '10c84cd0-7f66-44d8-abd1-c6d2395390af' => \Doctrine\DBAL\Types\BooleanType::class,
            '4522afc8-b150-4741-87ca-cea8e45e3409' => \Doctrine\DBAL\Types\IntegerType::class,
        ];

        $this->getEntityManagerRegistry(null, [
            'foo' => ['types' => $types],
            'bar' => ['types' => $types],
        ]);

        foreach ($types as $name => $_) {
            $this->assertTrue(\Doctrine\DBAL\Types\Type::hasType($name));
        }
    }

    public function testDbalDefaultAutoCommit() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertTrue($registry->getConnection('foo')->getConfiguration()->getAutoCommit());
        $this->assertTrue($registry->getConnection('bar')->getConfiguration()->getAutoCommit());
    }

    public function testDbalSetAutoCommit() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['auto_commit' => false]],
            'bar' => ['dbal' => ['auto_commit' => false]],
        ]);

        // for auto_commit=false
        $registry->getConnection('foo')->commit();
        $registry->getConnection('bar')->commit();

        $this->assertFalse($registry->getConnection('foo')->getConfiguration()->getAutoCommit());
        $this->assertFalse($registry->getConnection('bar')->getConfiguration()->getAutoCommit());
    }

    public function testDbalSetSchemaAssetsFilter() : void
    {
        $foo = function () {
            return true;
        };

        $bar = function () {
            return true;
        };

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['schema_assets_filter' => $foo]],
            'bar' => ['dbal' => ['schema_assets_filter' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getConnection('foo')->getConfiguration()->getSchemaAssetsFilter());
        $this->assertSame($bar, $registry->getConnection('bar')->getConfiguration()->getSchemaAssetsFilter());
    }

    public function testDbalSetSqlLogger() : void
    {
        $foo = new \Doctrine\DBAL\Logging\DebugStack();
        $bar = new \Doctrine\DBAL\Logging\DebugStack();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['sql_logger' => $foo]],
            'bar' => ['dbal' => ['sql_logger' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getConnection('foo')->getConfiguration()->getSQLLogger());
        $this->assertSame($bar, $registry->getConnection('bar')->getConfiguration()->getSQLLogger());
    }

    public function testDbalSetResultCache() : void
    {
        $foo = new ArrayCache();
        $bar = new ArrayCache();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['result_cache' => $foo]],
            'bar' => ['dbal' => ['result_cache' => $bar]],
        ]);

        // we can't use assertSame because the current version of DBAL doesn't support PSR-16 and will wrap the cache...
        $this->assertNotNull($registry->getConnection('foo')->getConfiguration()->getResultCacheImpl());
        $this->assertNotNull($registry->getConnection('bar')->getConfiguration()->getResultCacheImpl());
    }

    public function testDbalSetConnectionParams() : void
    {
        $foo = '96a381e1-c385-4813-88a9-69f1a4f63425';
        $bar = '6540b565-95ce-49e1-bb3c-61658915c11c';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['connection' => ['@id' => $foo]]],
            'bar' => ['dbal' => ['connection' => ['@id' => $bar]]],
        ]);

        $this->assertArrayHasKey('@id', $registry->getConnection('foo')->getParams());
        $this->assertArrayHasKey('@id', $registry->getConnection('bar')->getParams());

        $this->assertSame($foo, $registry->getConnection('foo')->getParams()['@id']);
        $this->assertSame($bar, $registry->getConnection('bar')->getParams()['@id']);
    }

    public function testDbalSetEventManager() : void
    {
        $foo = new \Doctrine\Common\EventManager();
        $bar = new \Doctrine\Common\EventManager();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['dbal' => ['event_manager' => $foo]],
            'bar' => ['dbal' => ['event_manager' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getConnection('foo')->getEventManager());
        $this->assertSame($bar, $registry->getConnection('bar')->getEventManager());
    }

    public function testOrmDefaultAutoGenerateProxyClasses() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame(1, $registry->getManager('foo')->getConfiguration()->getAutoGenerateProxyClasses());
        $this->assertSame(1, $registry->getManager('bar')->getConfiguration()->getAutoGenerateProxyClasses());
    }

    public function testOrmDefaultMetadataDriver() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $driverName = PHP_MAJOR_VERSION < 8 ? AnnotationDriver::class : AttributeDriver::class;

        $this->assertInstanceOf($driverName, $registry->getManager('foo')->getConfiguration()->getMetadataDriverImpl());
        $this->assertInstanceOf($driverName, $registry->getManager('bar')->getConfiguration()->getMetadataDriverImpl());
    }

    public function testOrmDefaultProxyNamespace() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame('DoctrineProxies', $registry->getManager('foo')->getConfiguration()->getProxyNamespace());
        $this->assertSame('DoctrineProxies', $registry->getManager('bar')->getConfiguration()->getProxyNamespace());
    }

    public function testOrmSetAutoGenerateProxyCasses() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['auto_generate_proxy_classes' => false, 'proxy_auto_generate' => null]],
            'bar' => ['orm' => ['auto_generate_proxy_classes' => false, 'proxy_auto_generate' => null]],
        ]);

        $this->assertSame(0, $registry->getManager('foo')->getConfiguration()->getAutoGenerateProxyClasses());
        $this->assertSame(0, $registry->getManager('bar')->getConfiguration()->getAutoGenerateProxyClasses());
    }

    public function testOrmSetClassMetadataFactoryName() : void
    {
        $foo = Fixtures\ClassMetadataFactory::class;
        $bar = Fixtures\ClassMetadataFactory::class;

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['class_metadata_factory_name' => $foo]],
            'bar' => ['orm' => ['class_metadata_factory_name' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getClassMetadataFactoryName());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getClassMetadataFactoryName());
    }

    public function testOrmSetCustomDatetimeFunctions() : void
    {
        $foo = '16e53e92-d103-439a-a9de-7cd3f7712af4';
        $bar = 'dd60f0a7-4c78-4915-9b5f-01da4b54020a';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['custom_datetime_functions' => ['foo' => $foo]]],
            'bar' => ['orm' => ['custom_datetime_functions' => ['bar' => $bar]]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getCustomDatetimeFunction('foo'));
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getCustomDatetimeFunction('bar'));
    }

    public function testOrmSetCustomHydrationModes() : void
    {
        $foo = '25204f3b-7044-4565-a664-e05f6e94d766';
        $bar = '648c5ee3-70d7-4889-8883-66629165566a';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['custom_hydration_modes' => ['foo' => $foo]]],
            'bar' => ['orm' => ['custom_hydration_modes' => ['bar' => $bar]]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getCustomHydrationMode('foo'));
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getCustomHydrationMode('bar'));
    }

    public function testOrmSetCustomNumericFunctions() : void
    {
        $foo = '08f36747-c9b3-455b-990c-209440a1d545';
        $bar = '8b214c6a-7eb8-4c3f-a381-bee283e25f9a';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['custom_numeric_functions' => ['foo' => $foo]]],
            'bar' => ['orm' => ['custom_numeric_functions' => ['bar' => $bar]]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getCustomNumericFunction('foo'));
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getCustomNumericFunction('bar'));
    }

    public function testOrmSetCustomStringFunctions() : void
    {
        $foo = 'b04ff0fe-2d2a-4496-936f-8734f7dd5655';
        $bar = 'e0615655-1644-4ca2-bd74-97d713b6b937';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['custom_string_functions' => ['foo' => $foo]]],
            'bar' => ['orm' => ['custom_string_functions' => ['bar' => $bar]]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getCustomStringFunction('foo'));
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getCustomStringFunction('bar'));
    }

    public function testOrmSetDefaultQueryHints() : void
    {
        $foo = '67dad63c-8ae4-429a-8c1a-e56eb29c9c2b';
        $bar = 'da633314-4576-49c1-b466-58d78b3d2cf7';

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['default_query_hints' => ['foo' => $foo]]],
            'bar' => ['orm' => ['default_query_hints' => ['bar' => $bar]]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getDefaultQueryHint('foo'));
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getDefaultQueryHint('bar'));
    }

    public function testOrmSetDefaultRepositoryClassName() : void
    {
        $foo = Fixtures\EntityRepository::class;
        $bar = Fixtures\EntityRepository::class;

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['default_repository_class_name' => $foo]],
            'bar' => ['orm' => ['default_repository_class_name' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getDefaultRepositoryClassName());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getDefaultRepositoryClassName());
    }

    public function testOrmSetEntityListenerResolver() : void
    {
        $foo = new \Doctrine\ORM\Mapping\DefaultEntityListenerResolver();
        $bar = new \Doctrine\ORM\Mapping\DefaultEntityListenerResolver();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['entity_listener_resolver' => $foo]],
            'bar' => ['orm' => ['entity_listener_resolver' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getEntityListenerResolver());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getEntityListenerResolver());
    }

    public function testOrmSetEntityNamespaces() : void
    {
        $foo = ['Foo' => 'Acme\Foo'];
        $bar = ['Bar' => 'Acme\Bar'];

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['entity_namespaces' => $foo]],
            'bar' => ['orm' => ['entity_namespaces' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getEntityNamespaces());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getEntityNamespaces());
    }

    public function testOrmSetNamingStrategy() : void
    {
        $foo = new \Doctrine\ORM\Mapping\DefaultNamingStrategy();
        $bar = new \Doctrine\ORM\Mapping\DefaultNamingStrategy();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['naming_strategy' => $foo]],
            'bar' => ['orm' => ['naming_strategy' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getNamingStrategy());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getNamingStrategy());
    }

    public function testOrmSetProxyAutoGenerate() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['proxy_auto_generate' => false]],
            'bar' => ['orm' => ['proxy_auto_generate' => false]],
        ]);

        $this->assertSame(0, $registry->getManager('foo')->getConfiguration()->getAutoGenerateProxyClasses());
        $this->assertSame(0, $registry->getManager('bar')->getConfiguration()->getAutoGenerateProxyClasses());
    }

    public function testOrmSetProxyDir() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['proxy_dir' => '/foo']],
            'bar' => ['orm' => ['proxy_dir' => '/bar']],
        ]);

        $this->assertSame('/foo', $registry->getManager('foo')->getConfiguration()->getProxyDir());
        $this->assertSame('/bar', $registry->getManager('bar')->getConfiguration()->getProxyDir());
    }

    public function testOrmSetProxyNamespace() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['proxy_namespace' => 'Foo']],
            'bar' => ['orm' => ['proxy_namespace' => 'Bar']],
        ]);

        $this->assertSame('Foo', $registry->getManager('foo')->getConfiguration()->getProxyNamespace());
        $this->assertSame('Bar', $registry->getManager('bar')->getConfiguration()->getProxyNamespace());
    }

    public function testOrmSetQuoteStrategy() : void
    {
        $foo = new \Doctrine\ORM\Mapping\DefaultQuoteStrategy();
        $bar = new \Doctrine\ORM\Mapping\DefaultQuoteStrategy();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['quote_strategy' => $foo]],
            'bar' => ['orm' => ['quote_strategy' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getQuoteStrategy());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getQuoteStrategy());
    }

    public function testOrmSetRepositoryFactory() : void
    {
        $foo = $this->createMock(\Doctrine\ORM\Repository\RepositoryFactory::class);
        $bar = $this->createMock(\Doctrine\ORM\Repository\RepositoryFactory::class);

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['repository_factory' => $foo]],
            'bar' => ['orm' => ['repository_factory' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getRepositoryFactory());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getRepositoryFactory());
    }

    public function testOrmSetSecondLevelCache() : void
    {
        $cacheFactory = new \Doctrine\ORM\Cache\DefaultCacheFactory(
            new \Doctrine\ORM\Cache\RegionsConfiguration(),
            \Doctrine\Common\Cache\Psr6\DoctrineProvider::wrap(new ArrayCache())
        );

        $foo = new \Doctrine\ORM\Cache\CacheConfiguration();
        $foo->setCacheFactory($cacheFactory);

        $bar = new \Doctrine\ORM\Cache\CacheConfiguration();
        $bar->setCacheFactory($cacheFactory);

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => [
                'second_level_cache_enabled' => true,
                'second_level_cache_configuration' => $foo,
            ]],
            'bar' => ['orm' => [
                'second_level_cache_enabled' => true,
                'second_level_cache_configuration' => $bar,
            ]],
        ]);

        $this->assertTrue($registry->getManager('foo')->getConfiguration()->isSecondLevelCacheEnabled());
        $this->assertTrue($registry->getManager('bar')->getConfiguration()->isSecondLevelCacheEnabled());

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getSecondLevelCacheConfiguration());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getSecondLevelCacheConfiguration());
    }

    public function testOrmSetMetadataCache() : void
    {
        $foo = new ArrayCache();
        $bar = new ArrayCache();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['metadata_cache' => $foo]],
            'bar' => ['orm' => ['metadata_cache' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getMetadataCache());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getMetadataCache());
    }

    public function testOrmSetQueryCache() : void
    {
        $foo = new ArrayCache();
        $bar = new ArrayCache();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['query_cache' => $foo]],
            'bar' => ['orm' => ['query_cache' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getQueryCache());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getQueryCache());
    }

    public function testOrmSetResultCache() : void
    {
        $foo = new ArrayCache();
        $bar = new ArrayCache();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['result_cache' => $foo]],
            'bar' => ['orm' => ['result_cache' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getResultCache());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getResultCache());
    }

    public function testOrmSetHydrationCache() : void
    {
        $foo = new ArrayCache();
        $bar = new ArrayCache();

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['hydration_cache' => $foo]],
            'bar' => ['orm' => ['hydration_cache' => $bar]],
        ]);

        $this->assertSame($foo, $registry->getManager('foo')->getConfiguration()->getHydrationCache());
        $this->assertSame($bar, $registry->getManager('bar')->getConfiguration()->getHydrationCache());
    }

    public function testOrmSetMetadataDriverNameAnnotations() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['metadata_driver' => 'annotations']],
        ]);

        $this->assertInstanceOf(AnnotationDriver::class, $registry->getManager('foo')
            ->getConfiguration()
            ->getMetadataDriverImpl());
    }

    public function testOrmSetMetadataDriverNameAttributes() : void
    {
        if (8 > PHP_MAJOR_VERSION) {
            $this->markTestSkipped('PHP 8 is required...');
            return;
        }

        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['metadata_driver' => 'attributes']],
        ]);

        $this->assertInstanceOf(AttributeDriver::class, $registry->getManager('foo')
            ->getConfiguration()
            ->getMetadataDriverImpl());
    }

    public function testGetAliasNamespace() : void
    {
        $registry = $this->getEntityManagerRegistry(null, [
            'foo' => ['orm' => ['entity_namespaces' => ['Foo' => 'Bar']]],
            'bar' => ['orm' => ['entity_namespaces' => ['Bar' => 'Foo']]],
        ]);

        $this->assertSame('Bar', $registry->getAliasNamespace('Foo'));
        $this->assertSame('Foo', $registry->getAliasNamespace('Bar'));
    }

    public function testGetUndefinedAliasNamespace() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->expectException(ORMException::class);

        $registry->getAliasNamespace('Unknown');
    }

    public function testGetUndefinedConnection() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->expectException(InvalidArgumentException::class);

        $registry->getConnection('unknown');
    }

    public function testGetUndefinedManager() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->expectException(InvalidArgumentException::class);

        $registry->getManager('unknown');
    }

    public function testResetManager() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertTrue($registry->getManager('foo')->isOpen());

        $registry->getManager('foo')->close();

        $this->assertFalse($registry->getManager('foo')->isOpen());

        $registry->resetManager('foo');

        $this->assertTrue($registry->getManager('foo')->isOpen());
    }

    public function testGetCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertCount(31, $registry->getCommands());
    }
}
