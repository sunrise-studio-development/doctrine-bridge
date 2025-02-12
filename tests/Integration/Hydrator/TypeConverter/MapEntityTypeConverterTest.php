<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Hydrator\TypeConverter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Stringable;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\TypeConverter\MapEntityTypeConverter;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Sunrise\Hydrator\AnnotationReaderInterface;
use Sunrise\Hydrator\Annotation\Subtype;
use Sunrise\Hydrator\Dictionary\ErrorMessage;
use Sunrise\Hydrator\Exception\InvalidObjectException;
use Sunrise\Hydrator\Exception\InvalidValueException;
use Sunrise\Hydrator\HydratorInterface;
use Sunrise\Hydrator\Type;

final class MapEntityTypeConverterTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $mockedEntityManagerRegistry;
    private EntityManagerInterface&MockObject $mockedEntityManager;
    private ClassMetadata&MockObject $mockedEntityMetadata;
    private EntityRepository&MockObject $mockedEntityRepository;
    private AnnotationReaderInterface&MockObject $mockedAnnotationReader;
    private HydratorInterface&MockObject $mockedHydrator;

    protected function setUp(): void
    {
        $this->mockedEntityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedEntityMetadata = $this->createMock(ClassMetadata::class);
        $this->mockedEntityRepository = $this->createMock(EntityRepository::class);
        $this->mockedAnnotationReader = $this->createMock(AnnotationReaderInterface::class);
        $this->mockedHydrator = $this->createMock(HydratorInterface::class);
    }

    public function testUnannotatedProperty(): void
    {
        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), 'mixed', true);

        $this->mockedAnnotationReader->expects($this->once())->method('getAnnotations')->with(MapEntity::class, $type->getHolder())->willReturnCallback(static fn(): Generator => yield from []);
        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        self::assertFalse($converter->castValue(null, $type, [], [])->valid());
    }

    #[DataProvider('unsupportedTypeDataProvider')]
    public function testUnsupportedType(mixed $typeName): void
    {
        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $typeName, false);

        $this->mockedAnnotationReader->expects($this->once())->method('getAnnotations')->with(MapEntity::class, $type->getHolder())->willReturnCallback(static fn(): Generator => yield new MapEntity());
        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $this->expectException(InvalidObjectException::class);
        $converter->castValue(null, $type, [], [])->valid();
    }

    public static function unsupportedTypeDataProvider(): Generator
    {
        yield ['mixed'];
        yield ['null'];
        yield ['bool'];
        yield ['false'];
        yield ['true'];
        yield ['int'];
        yield ['float'];
        yield ['string'];
        yield ['array'];
        yield ['object'];
        yield ['iterable'];
        yield ['UnknownClass'];
        yield [Stringable::class];
        yield [TestKit::class];
    }

    public function testSubtype(): void
    {
        $expectedEntity = new class {
        };

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield new Subtype('int', true),
                };
            }
        );

        $this->mockedHydrator->expects($this->once())->method('castValue')->withAnyParameters()->willReturnCallback(
            static function (mixed $value, Type $type, array $path = [], array $context = []): int {
                self::assertSame('1', $value);
                self::assertNull($type->getHolder());
                self::assertSame('int', $type->getName());
                self::assertTrue($type->allowsNull());
                self::assertSame(['id'], $path);
                self::assertSame(['foo' => 'bar'], $context);

                return 1;
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue('1', $type, ['id'], ['foo' => 'bar'])->current());
    }

    #[DataProvider('emptyStringDataProvider')]
    public function testPassesOnAllowedEmptyString(string $emptyString): void
    {
        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), stdClass::class, true);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        self::assertNull($converter->castValue($emptyString, $type, [], [])->current());
    }

    #[DataProvider('emptyStringDataProvider')]
    public function testFailsOnDisallowedEmptyString(string $emptyString): void
    {
        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), stdClass::class, false);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(ErrorMessage::MUST_NOT_BE_EMPTY);
        $converter->castValue($emptyString, $type, [], [])->valid();
    }

    public static function emptyStringDataProvider(): Generator
    {
        yield from [[''], [' ']];
    }

    public function testTrimsWhitespace(): void
    {
        $expectedEntity = new class {
        };

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => '6399806c-527c-43d1-9ca9-868f3cc43fe5'])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue(' 6399806c-527c-43d1-9ca9-868f3cc43fe5 ', $type, [], [])->current());
    }

    public function testDefaultEntityManagerName(): void
    {
        $expectedEntity = new class {
        };

        $defaultEntityManagerName = $this->mockEntityManagerName('default');

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with($defaultEntityManagerName)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry, defaultEntityManagerName: $defaultEntityManagerName);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue(1, $type, [], [])->current());
    }

    public function testEntityManagerNameFromAnnotation(): void
    {
        $expectedEntity = new class {
        };

        $expectedEntityManagerName = $this->mockEntityManagerName('foo');
        $defaultEntityManagerName = $this->mockEntityManagerName('default');

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with($expectedEntityManagerName)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type, $expectedEntityManagerName): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(em: $expectedEntityManagerName),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry, defaultEntityManagerName: $defaultEntityManagerName);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue(1, $type, [], [])->current());
    }

    public function testEntityFieldNameFromAnnotation(): void
    {
        $expectedEntity = new class {
        };

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->never())->method('getClassMetadata');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['publicId' => 1])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(field: 'publicId'),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue(1, $type, [], [])->current());
    }

    public function testAdditionalCriteriaFromAnnotation(): void
    {
        $expectedEntity = new class {
        };

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1, 'isDeleted' => false])->willReturn($expectedEntity);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(criteria: ['isDeleted' => false]),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);

        self::assertSame($expectedEntity, $converter->castValue(1, $type, [], [])->current());
    }

    public function testEntityNotFound(): void
    {
        $expectedEntity = new class {
        };

        $type = new Type(new ReflectionProperty(new class {
            public readonly mixed $foo;
        }, 'foo'), $expectedEntity::class, false);

        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with($expectedEntity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($expectedEntity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn(null);

        $this->mockedAnnotationReader->expects($this->exactly(2))->method('getAnnotations')->withAnyParameters()->willReturnCallback(
            static function (string $name, mixed $holder) use ($type): Generator {
                self::assertContains($name, [MapEntity::class, Subtype::class]);
                self::assertSame($type->getHolder(), $holder);

                match ($name) {
                    MapEntity::class => yield new MapEntity(),
                    Subtype::class => yield from [],
                };
            }
        );

        $converter = new MapEntityTypeConverter($this->mockedEntityManagerRegistry);
        $converter->setAnnotationReader($this->mockedAnnotationReader);
        $converter->setHydrator($this->mockedHydrator);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage(MapEntityTypeConverter::ERROR_MESSAGE);
        $converter->castValue(1, $type, [], [])->valid();
    }

    public function testWeight(): void
    {
        self::assertSame(-10, (new MapEntityTypeConverter($this->mockedEntityManagerRegistry))->getWeight());
    }
}
