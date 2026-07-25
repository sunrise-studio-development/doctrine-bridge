<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Router\OpenApi;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\ValueType;
use Sunrise\Bridge\Doctrine\Integration\Router\OpenApi\MapEntityPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;

final class MapEntityPhpTypeSchemaResolverTest extends TestCase
{
    private MapEntityPhpTypeSchemaResolver $mapEntityPhpTypeSchemaResolver;
    private OpenApiPhpTypeSchemaResolverManagerInterface&MockObject $mockedOpenApiPhpTypeSchemaResolverManager;

    protected function setUp(): void
    {
        $this->mockedOpenApiPhpTypeSchemaResolverManager = $this->createMock(OpenApiPhpTypeSchemaResolverManagerInterface::class);
        $this->mapEntityPhpTypeSchemaResolver = new MapEntityPhpTypeSchemaResolver();
        $this->mapEntityPhpTypeSchemaResolver->setOpenApiPhpTypeSchemaResolverManager($this->mockedOpenApiPhpTypeSchemaResolverManager);
    }

    #[DataProvider('supportsPhpTypeDataProvider')]
    public function testSupportsPhpType(Type $phpType, Reflector $phpTypeHolder, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->mapEntityPhpTypeSchemaResolver->supportsPhpType($phpType, $phpTypeHolder));
    }

    public static function supportsPhpTypeDataProvider(): Generator
    {
        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionParameter(static fn(mixed $foo): mixed => $foo, 'foo'),
            'expectedResult' => false,
        ];

        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                private mixed $foo;
            }, 'foo'),
            'expectedResult' => false,
        ];

        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity] private mixed $foo;
            }, 'foo'),
            'expectedResult' => false,
        ];

        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[ValueType('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => false,
        ];

        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity, ValueType('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => true,
        ];
    }

    #[DataProvider('resolvePhpTypeSchemaDataProvider')]
    public function testResolvePhpTypeSchema(bool $isSupportedPhpType, Type $phpType, Reflector $phpTypeHolder, array $expectedResult): void
    {
        $this->mockedOpenApiPhpTypeSchemaResolverManager
            ->expects($isSupportedPhpType ? $this->atLeastOnce() : $this->never())
            ->method('resolvePhpTypeSchema')
            ->with(self::callback(static function (Type $arg) use ($phpType): bool {
                return [$phpType->getName(), $phpType->allowsNull()] === [$arg->getName(), $arg->allowsNull()];
            }), $phpTypeHolder)
            ->willReturn($expectedResult);

        self::assertSame($expectedResult, $this->mapEntityPhpTypeSchemaResolver->resolvePhpTypeSchema($phpType, $phpTypeHolder));
    }

    public static function resolvePhpTypeSchemaDataProvider(): Generator
    {
        yield [
            'isSupportedPhpType' => false,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionParameter(static fn(mixed $foo): mixed => $foo, 'foo'),
            'expectedResult' => [],
        ];

        yield [
            'isSupportedPhpType' => false,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                private mixed $foo;
            }, 'foo'),
            'expectedResult' => [],
        ];

        yield [
            'isSupportedPhpType' => false,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity] private mixed $foo;
            }, 'foo'),
            'expectedResult' => [],
        ];

        yield [
            'isSupportedPhpType' => true,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[ValueType('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => ['foo' => 'bar'],
        ];

        yield [
            'isSupportedPhpType' => true,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity, ValueType('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => ['foo' => 'bar'],
        ];
    }

    public function testGetWeight(): void
    {
        self::assertSame(-10, $this->mapEntityPhpTypeSchemaResolver->getWeight());
    }
}
