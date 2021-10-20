<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\EntityManagerMaintainer;
use InvalidArgumentException;

class EntityManagerMaintainerTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testCloseAllConnections() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $maintainer = $registry->getMaintainer();

        $this->assertTrue($registry->getConnection('foo')->isConnected());
        $this->assertTrue($registry->getConnection('bar')->isConnected());

        $maintainer->closeAllConnections();

        $this->assertFalse($registry->getConnection('foo')->isConnected());
        $this->assertFalse($registry->getConnection('bar')->isConnected());
    }

    public function testClearAllManagers() : void
    {
        $onClearProto = new class
        {
            public $isCalled = false;

            public function onClear()
            {
                $this->isCalled = true;
            }
        };

        $registry = $this->getEntityManagerRegistry();
        $maintainer = $registry->getMaintainer();

        $fooOnClear = clone $onClearProto;
        $barOnClear = clone $onClearProto;

        $registry->getManager('foo')->getEventManager()->addEventListener(Events::onClear, $fooOnClear);
        $registry->getManager('bar')->getEventManager()->addEventListener(Events::onClear, $barOnClear);

        $this->assertFalse($fooOnClear->isCalled);
        $this->assertFalse($barOnClear->isCalled);

        $maintainer->clearAllManagers();

        $this->assertTrue($fooOnClear->isCalled);
        $this->assertTrue($barOnClear->isCalled);
    }

    public function testReopenAllManagers() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $maintainer = $registry->getMaintainer();

        $this->assertTrue($registry->getManager('foo')->isOpen());
        $this->assertTrue($registry->getManager('bar')->isOpen());

        $registry->getManager('foo')->close();
        $registry->getManager('bar')->close();

        $this->assertFalse($registry->getManager('foo')->isOpen());
        $this->assertFalse($registry->getManager('bar')->isOpen());

        $maintainer->reopenAllManagers();

        $this->assertTrue($registry->getManager('foo')->isOpen());
        $this->assertTrue($registry->getManager('bar')->isOpen());
    }
}
