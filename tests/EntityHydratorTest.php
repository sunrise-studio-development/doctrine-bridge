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

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, []);

        $this->assertInstanceOf(Fixtures\Entity\Common\Post::class, $object);
    }

    public function testInitAlreadyInitedObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $object = new Fixtures\Entity\Common\Post();

        $this->assertSame($object, $hydrator->hydrate($object, []));
    }

    public function testInitNonexistentObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->expectException(InvalidArgumentException::class);

        $hydrator->hydrate('NonexistentObject', []);
    }

    public function testInitUnhydrableObject() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $this->expectException(InvalidArgumentException::class);

        $hydrator->hydrate(Fixtures\Entity\Common\UnhydrableEntity::class, []);
    }

    public function testHydrateFields() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'name' => 'cfe0ecb0-d856-48b3-8541-7b89159782d9',
            'summary' => 'bf031afd-387c-4172-92fb-139268d8b6bf',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($data['name'], $object->getName());
        $this->assertSame($data['summary'], $object->getSummary());
    }

    public function testHydrateId() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'id' => 'a3c6bccf-a8a8-45ed-9c07-2e50c0519021',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotSame($data['id'], $object->getId());
    }

    public function testHydrateFieldWithoutSetter() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'createdAt' => '1970-01-01 00:00:00',
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotSame($data['createdAt'], $object->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testHydrateFieldWithInvalidValue() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'name' => ['value'],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame('', $object->getName());
    }

    public function testHydrateOneAssociation() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
            'category' => [
                'name' => 'ca4962ba-4faf-425c-b436-f3abd6fdfe89',
                'summary' => 'e223c8d9-99de-49d9-adfd-e5ffd0677873',
            ],
        ];

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertNotNull($object->getCategory());

        $this->assertSame($data['category']['name'], $object->getCategory()->getName());
        $this->assertSame($data['category']['summary'], $object->getCategory()->getSummary());
    }

    public function testHydrateManyAssociations() : void
    {
        $registry = $this->getEntityManagerRegistry();
        $hydrator = $registry->getHydrator();

        $data = [
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

        $object = $hydrator->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertCount(2, $object->getTags());

        $this->assertSame($data['tags'][0]['name'], $object->getTags()->offsetGet(0)->getName());
        $this->assertSame($data['tags'][0]['summary'], $object->getTags()->offsetGet(0)->getSummary());

        $this->assertSame($data['tags'][1]['name'], $object->getTags()->offsetGet(1)->getName());
        $this->assertSame($data['tags'][1]['summary'], $object->getTags()->offsetGet(1)->getSummary());
    }

    public function testHydrateOneAssociationUsingId() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $category = new Fixtures\Entity\Common\Category();
        $category->setName('foo');
        $category->setSummary('Lorem ipsum');

        $registry->getManager()->persist($category);
        $registry->getManager()->flush();

        $data = [
            'category' => $category->getid(),
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame($category, $object->getCategory());
    }

    public function testHydrateManyAssociationsUsingId() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $tag = new Fixtures\Entity\Common\Tag();
        $tag->setName('foo');
        $tag->setSummary('Lorem ipsum');

        $registry->getManager()->persist($tag);
        $registry->getManager()->flush();

        $data = [
            'tags' => $tag->getId(),
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame([$tag], $object->getTags()->getValues());
    }

    public function testHydrateManyAssociationsUsingIds() : void
    {
        $registry = $this->getEntityManagerRegistry();

        $tag1 = new Fixtures\Entity\Common\Tag();
        $tag1->setName('foo');
        $tag1->setSummary('Lorem ipsum');

        $tag2 = new Fixtures\Entity\Common\Tag();
        $tag2->setName('bar');
        $tag2->setSummary('Lorem ipsum');

        $registry->getManager()->persist($tag1);
        $registry->getManager()->persist($tag2);
        $registry->getManager()->flush();

        $data = [
            'tags' => [
                $tag1->getId(),
                $tag2->getId(),
            ],
        ];

        $object = $registry->getHydrator()->hydrate(Fixtures\Entity\Common\Post::class, $data);

        $this->assertSame([$tag1, $tag2], $object->getTags()->getValues());
    }
}
