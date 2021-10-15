<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
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

    public function testInitialization() : void
    {
        $registry = $this->getEntityManagerRegistry('some registry');

        $this->assertSame('some registry', $registry->getName());
    }

    public function testInitializationConnections() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame([
            'foo' => 'foo.conn',
            'bar' => 'bar.conn',
        ], $registry->getConnectionNames());

        $this->assertSame('foo', $registry->getDefaultConnectionName());
        $this->assertSame($registry->getConnection(), $registry->getConnection('foo'));
        $this->assertNotSame($registry->getConnection('foo'), $registry->getConnection('bar'));
    }

    public function testInitializationManagers() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame([
            'foo' => 'foo',
            'bar' => 'bar',
        ], $registry->getManagerNames());

        $this->assertSame('foo', $registry->getDefaultManagerName());
        $this->assertSame($registry->getManager(), $registry->getManager('foo'));
        $this->assertNotSame($registry->getManager('foo'), $registry->getManager('bar'));
    }

    public function testRegisterTypes() : void
    {
        $this->getEntityManagerRegistry();

        $this->assertTrue(Type::hasType('custom.boolean'));
        $this->assertTrue(Type::hasType('custom.integer'));
    }

    public function testDbalSetConnectionParams() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertArrayHasKey('@id', $registry->getConnection('foo')->getParams());
        $this->assertSame('96a381e1-c385-4813-88a9-69f1a4f63425', $registry->getConnection('foo')->getParams()['@id']);

        $this->assertArrayHasKey('@id', $registry->getConnection('bar')->getParams());
        $this->assertSame('6540b565-95ce-49e1-bb3c-61658915c11c', $registry->getConnection('bar')->getParams()['@id']);
    }

    public function testDbalSetAutoCommit() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertFalse($registry->getConnection('foo')->getConfiguration()->getAutoCommit());
        $this->assertTrue($registry->getConnection('bar')->getConfiguration()->getAutoCommit());
    }

    public function testDbalSetResultCache() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertNotNull($registry->getConnection('foo')->getConfiguration()->getResultCacheImpl());
        $this->assertNull($registry->getConnection('bar')->getConfiguration()->getResultCacheImpl());
    }

    public function testDbalSetSqlLogger() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertNotNull($registry->getConnection('foo')->getConfiguration()->getSQLLogger());
        $this->assertNull($registry->getConnection('bar')->getConfiguration()->getSQLLogger());
    }

    public function testOrmSetProxyDir() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame(sys_get_temp_dir(), $registry->getManager('foo')->getConfiguration()->getProxyDir());
        $this->assertSame(sys_get_temp_dir(), $registry->getManager('bar')->getConfiguration()->getProxyDir());
    }

    public function testOrmSetProxyAutoGenerate() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame(0, $registry->getManager('foo')->getConfiguration()->getAutoGenerateProxyClasses());
        $this->assertSame(1, $registry->getManager('bar')->getConfiguration()->getAutoGenerateProxyClasses());
    }

    public function testOrmSetMetadataCache() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertNotNull($registry->getManager('foo')->getConfiguration()->getMetadataCache());
        $this->assertNull($registry->getManager('bar')->getConfiguration()->getMetadataCache());
    }

    public function testOrmSetQueryCache() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertNotNull($registry->getManager('foo')->getConfiguration()->getQueryCache());
        $this->assertNull($registry->getManager('bar')->getConfiguration()->getQueryCache());
    }

    public function testOrmSetHydrationCache() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertNotNull($registry->getManager('foo')->getConfiguration()->getHydrationCache());
        $this->assertNull($registry->getManager('bar')->getConfiguration()->getHydrationCache());
    }

    public function testGetAliasNamespace() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $this->assertSame(
            'Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity',
            $registry->getAliasNamespace('App')
        );
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
}
