<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\DBAL\Tools\Console\ConnectionProvider as ConnectionProviderInterface;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\ConnectionProvider;
use InvalidArgumentException;

class ConnectionProviderTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testContracts() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new ConnectionProvider($registry);

        $this->assertInstanceOf(ConnectionProviderInterface::class, $provider);
    }

    public function testGetDefaultConnection() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new ConnectionProvider($registry);

        $this->assertSame($registry->getConnection(), $provider->getDefaultConnection());
    }

    public function testGetConnection() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new ConnectionProvider($registry);

        $this->assertSame($registry->getConnection('foo'), $provider->getConnection('foo'));
        $this->assertSame($registry->getConnection('bar'), $provider->getConnection('bar'));
    }
}
