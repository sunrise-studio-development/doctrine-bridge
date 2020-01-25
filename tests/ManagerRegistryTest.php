<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\RepositoryFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager as Manager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\AbstractManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * ManagerRegistryTest
 */
class ManagerRegistryTest extends TestCase
{
    use Fixture\ContainerAwareTrait;

    /**
     * @return void
     */
    public function testConstructor() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertInstanceOf(AbstractManagerRegistry::class, $doctrine);

        $this->assertSame('ORM', $doctrine->getName());

        $this->assertSame('default', $doctrine->getDefaultConnectionName());
        $this->assertSame('default', $doctrine->getDefaultManagerName());
    }

    /**
     * @return void
     */
    public function testRepositoryFactory() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertInstanceOf(
            RepositoryFactory::class,
            $doctrine->getManager('foo')->getConfiguration()->getRepositoryFactory()
        );

        $this->assertInstanceOf(
            RepositoryFactory::class,
            $doctrine->getManager('bar')->getConfiguration()->getRepositoryFactory()
        );
    }

    /**
     * @return void
     */
    public function testCreatedManagersAndConnections() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertTrue($container->has('doctrine.connection.foo'));
        $this->assertTrue($container->has('doctrine.connection.bar'));

        $this->assertTrue($container->has('doctrine.manager.foo'));
        $this->assertTrue($container->has('doctrine.manager.bar'));

        $this->assertSame($container->get('doctrine.connection.foo'), $doctrine->getConnection('foo'));
        $this->assertSame($container->get('doctrine.connection.bar'), $doctrine->getConnection('bar'));

        $this->assertSame($container->get('doctrine.manager.foo'), $doctrine->getManager('foo'));
        $this->assertSame($container->get('doctrine.manager.bar'), $doctrine->getManager('bar'));

        $this->assertInstanceOf(Connection::class, $doctrine->getConnection('foo'));
        $this->assertInstanceOf(Connection::class, $doctrine->getConnection('bar'));

        $this->assertInstanceOf(Manager::class, $doctrine->getManager('foo'));
        $this->assertInstanceOf(Manager::class, $doctrine->getManager('bar'));
    }

    /**
     * @return void
     */
    public function testReopenManagers() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertTrue($doctrine->getManager('foo')->isOpen());
        $this->assertTrue($doctrine->getManager('bar')->isOpen());

        $doctrine->getManager('foo')->close();
        $doctrine->getManager('bar')->close();

        $this->assertFalse($doctrine->getManager('foo')->isOpen());
        $this->assertFalse($doctrine->getManager('bar')->isOpen());

        $doctrine->reopenManagers();

        $this->assertTrue($doctrine->getManager('foo')->isOpen());
        $this->assertTrue($doctrine->getManager('bar')->isOpen());
    }

    /**
     * @return void
     */
    public function testClearManagers() : void
    {
        $onClear = new class ()
        {
            public $isCleared = false;

            public function onClear() : void
            {
                $this->isCleared = true;
            }
        };

        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $doctrine->getManager('foo')->getEventManager()->addEventListener(Events::onClear, $onClear);
        $doctrine->getManager('bar')->getEventManager()->addEventListener(Events::onClear, $onClear);

        $this->assertFalse(current(
            $doctrine->getManager('foo')->getEventManager()->getListeners(Events::onClear)
        )->isCleared);

        $this->assertFalse(current(
            $doctrine->getManager('bar')->getEventManager()->getListeners(Events::onClear)
        )->isCleared);

        $doctrine->clearManagers();

        $this->assertTrue(current(
            $doctrine->getManager('foo')->getEventManager()->getListeners(Events::onClear)
        )->isCleared);

        $this->assertTrue(current(
            $doctrine->getManager('bar')->getEventManager()->getListeners(Events::onClear)
        )->isCleared);
    }

    /**
     * @return void
     */
    public function testCloseConnections() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertFalse($doctrine->getConnection('foo')->isConnected());
        $this->assertFalse($doctrine->getConnection('bar')->isConnected());

        $doctrine->getConnection('foo')->connect();
        $doctrine->getConnection('bar')->connect();

        $this->assertTrue($doctrine->getConnection('foo')->isConnected());
        $this->assertTrue($doctrine->getConnection('bar')->isConnected());

        $doctrine->closeConnections();

        $this->assertFalse($doctrine->getConnection('foo')->isConnected());
        $this->assertFalse($doctrine->getConnection('bar')->isConnected());
    }

    /**
     * @return void
     */
    public function testAliasNamespace() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $doctrine->getManager('foo')->getConfiguration()->addEntityNamespace('foo', 'Alias\Foo');
        $doctrine->getManager('bar')->getConfiguration()->addEntityNamespace('bar', 'Alias\Bar');

        $this->assertSame('Alias\Foo', $doctrine->getAliasNamespace('foo'));
        $this->assertSame('Alias\Bar', $doctrine->getAliasNamespace('bar'));

        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('Unknown Entity namespace alias \'baz\'.');

        $doctrine->getAliasNamespace('baz');
    }
}
