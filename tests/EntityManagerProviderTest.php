<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\ORM\Tools\Console\EntityManagerProvider as EntityManagerProviderInterface;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\EntityManagerProvider;
use InvalidArgumentException;

class EntityManagerProviderTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testContracts() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new EntityManagerProvider($registry);

        $this->assertInstanceOf(EntityManagerProviderInterface::class, $provider);
    }

    public function testGetDefaultManager() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new EntityManagerProvider($registry);

        $this->assertSame($registry->getManager(), $provider->getDefaultManager());
    }

    public function testGetManager() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new EntityManagerProvider($registry);

        $this->assertSame($registry->getManager('foo'), $provider->getManager('foo'));
        $this->assertSame($registry->getManager('bar'), $provider->getManager('bar'));
    }
}
