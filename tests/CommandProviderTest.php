<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\CommandProvider;

class CommandProviderTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testGetDbalCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new CommandProvider($registry);

        $this->assertCount(2, $provider->getDbalCommands());
    }

    public function testGetOrmCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new CommandProvider($registry);

        $this->assertCount(16, $provider->getOrmCommands());
    }

    public function testGetMigrationsCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $provider = new CommandProvider($registry);

        $this->assertCount(13, $provider->getMigrationsCommands([
            'logger' => $this->createMock(LoggerInterface::class),
        ]));
    }
}
