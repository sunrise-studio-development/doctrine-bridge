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

        $this->assertCount(2, $provider->getDBALCommands());
    }

    public function testGetOrmCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new CommandProvider($registry);

        $this->assertCount(16, $provider->getORMCommands());
    }

    public function testGetMigrationCommands() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $provider = new CommandProvider($registry);

        $config = $this->getMigrationsConfig();
        $logger = $this->createMock(LoggerInterface::class);

        $this->assertCount(13, $provider->getMigrationCommands($config, $logger));
    }
}
