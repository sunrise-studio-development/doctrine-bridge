<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests;

/**
 * Import classes
 */
use PHPUnit\Framework\TestCase;

/**
 * ArrayHydratorTest
 */
class ArrayHydratorTest extends TestCase
{
    use Fixture\ContainerAwareTrait;

    /**
     * @return void
     */
    public function testHydrate() : void
    {
        $container = $this->getContainer();

        $doctrine = $container->get('doctrine');
        $hydrator = $doctrine->getHydrator();

        $brand = $hydrator->hydrate(Fixture\HydratableEntity\Brand::class, [
            'name' => 'foo',
            'aliases' => [
                ['name' => 'bar'],
                ['name' => 'baz'],
            ],
            'logotypes' => [
                ['name' => 'qux'],
                ['name' => 'quux'],
            ],
        ]);

        $this->assertSame('foo:setter', $brand->getName());

        $this->assertCount(2, $brand->getAliases());
        $this->assertSame('bar:setter:adder:setter', $brand->getAliases()->get(0)->getName());
        $this->assertSame('baz:setter:adder:setter', $brand->getAliases()->get(1)->getName());

        $this->assertCount(2, $brand->getLogotypes());
        $this->assertSame('qux:setter:adder:setter', $brand->getLogotypes()->get(0)->getName());
        $this->assertSame('quux:setter:adder:setter', $brand->getLogotypes()->get(1)->getName());
    }
}
