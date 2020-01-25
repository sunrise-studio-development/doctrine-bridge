<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\RepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory as BaseRepositoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * RepositoryFactoryTest
 */
class RepositoryFactoryTest extends TestCase
{
    use Fixture\ContainerAwareTrait;

    /**
     * @return void
     */
    public function testConstructor() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');

        $this->assertInstanceOf(
            RepositoryFactory::class,
            $doctrine->getManager('foo')->getConfiguration()->getRepositoryFactory()
        );

        $this->assertInstanceOf(
            BaseRepositoryFactory::class,
            $doctrine->getManager('foo')->getConfiguration()->getRepositoryFactory()
        );

        $this->assertInstanceOf(
            RepositoryFactory::class,
            $doctrine->getManager('bar')->getConfiguration()->getRepositoryFactory()
        );

        $this->assertInstanceOf(
            BaseRepositoryFactory::class,
            $doctrine->getManager('bar')->getConfiguration()->getRepositoryFactory()
        );
    }

    /**
     * @return void
     */
    public function testInject() : void
    {
        $container = $this->getContainer();

        $container->set('foo', 'foo');
        $container->set('bar', 'bar');

        $doctrine = $container->get('doctrine');

        $repository = $doctrine->getRepository(Fixture\Entity\Entry::class);

        $this->assertSame($repository, $doctrine->getRepository(Fixture\Entity\Entry::class));

        $this->assertSame('foo', $repository->foo);
        $this->assertSame('bar', $repository->bar);
    }
}
