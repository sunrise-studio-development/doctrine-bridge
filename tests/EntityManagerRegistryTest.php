<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\TestCase;

class EntityManagerRegistryTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testInitialization() : void
    {
        $entityManagerRegistry = $this->getEntityManagerRegistry('some registry');

        $this->assertSame('some registry', $entityManagerRegistry->getName());

        $this->assertSame([
            'foo' => 'foo.conn',
            'bar' => 'bar.conn',
        ], $entityManagerRegistry->getConnectionNames());

        $this->assertSame('foo', $entityManagerRegistry->getDefaultConnectionName());

        $this->assertSame($entityManagerRegistry->getConnection(), $entityManagerRegistry->getConnection('foo'));

        $this->assertNotSame($entityManagerRegistry->getConnection('foo'), $entityManagerRegistry->getConnection('bar'));

        $this->assertSame([
            'foo' => 'foo',
            'bar' => 'bar',
        ], $entityManagerRegistry->getManagerNames());

        $this->assertSame('foo', $entityManagerRegistry->getDefaultManagerName());

        $this->assertSame($entityManagerRegistry->getManager(), $entityManagerRegistry->getManager('foo'));

        $this->assertNotSame($entityManagerRegistry->getManager('foo'), $entityManagerRegistry->getManager('bar'));
    }

    public function testCreationConnections() : void
    {
        $configuration = $this->getConfiguration();
        $fooConfiguration = $configuration['doctrine']['foo'];
        $barConfiguration = $configuration['doctrine']['bar'];

        $entityManagerRegistry = $this->getEntityManagerRegistry();

        $fooConnection = $entityManagerRegistry->getConnection('foo');
        $this->assertSame($fooConfiguration['dbal']['connection']['url'], $fooConnection->getParams()['url']);

        $this->assertNotNull($fooConnection->getConfiguration()->getResultCacheImpl());

        $barConnection = $entityManagerRegistry->getConnection('bar');
        $this->assertSame($barConfiguration['dbal']['connection']['url'], $barConnection->getParams()['url']);
    }
}
