<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class EntityHydratorTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    public function testInitObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $object = $hydrator->hydrate(Fixtures\Entity\Post::class, []);

        $this->assertInstanceOf(Fixtures\Entity\Post::class, $object);
    }

    public function testInitAlreadyInitedObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $object = new Fixtures\Entity\Post();

        $this->assertSame($object, $hydrator->hydrate($object, []));
    }

    public function testInitUnhydrableObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->expectException(InvalidArgumentException::class);

        $hydrator->hydrate(Fixtures\Entity\UnhydrableEntity::class, []);
    }

    public function testInitNonexistentObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->expectException(InvalidArgumentException::class);

        $hydrator->hydrate('SomeEntity', []);
    }

    public function testHydrateFields() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'id' => 'a3c6bccf-a8a8-45ed-9c07-2e50c0519021', // will be ignored because it's ID
            'name' => 'cfe0ecb0-d856-48b3-8541-7b89159782d9',
            'summary' => 'bf031afd-387c-4172-92fb-139268d8b6bf',
            'createdAt' => '1970-01-01 00:00:00', // will be ignored because it has no setter
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Post::class, $data);

        $this->assertNotSame($data['id'], $object->getId());
        $this->assertSame($data['name'], $object->getName());
        $this->assertSame($data['summary'], $object->getSummary());
        $this->assertNotSame($data['createdAt'], $object->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testHydrateAssociations() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'category' => [
                'name' => 'ca4962ba-4faf-425c-b436-f3abd6fdfe89',
                'summary' => 'e223c8d9-99de-49d9-adfd-e5ffd0677873',
            ],
            'tags' => [
                [
                    'name' => '37735afe-30e7-45ba-bb1a-306d872669e9',
                    'summary' => 'f1e34d6d-44a2-40f5-a50e-864755fb3d82',
                ],
                [
                    'name' => 'e52e2226-7187-4d9b-930a-ddd09291caac',
                    'summary' => '56be736b-12a4-4cd3-bfac-971a2e3e7fea',
                ],
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Post::class, $data);

        $this->assertNotNull($object->getCategory());
        $this->assertCount(2, $object->getTags());

        $this->assertSame($data['category']['name'], $object->getCategory()->getName());
        $this->assertSame($data['category']['summary'], $object->getCategory()->getSummary());

        $this->assertSame($data['tags'][0]['name'], $object->getTags()->offsetGet(0)->getName());
        $this->assertSame($data['tags'][0]['summary'], $object->getTags()->offsetGet(0)->getSummary());

        $this->assertSame($data['tags'][1]['name'], $object->getTags()->offsetGet(1)->getName());
        $this->assertSame($data['tags'][1]['summary'], $object->getTags()->offsetGet(1)->getSummary());
    }
}
