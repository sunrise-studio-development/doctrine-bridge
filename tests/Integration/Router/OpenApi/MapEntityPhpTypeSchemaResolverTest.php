<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Router\OpenApi;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Bridge\Doctrine\Integration\Router\OpenApi\MapEntityPhpTypeSchemaResolver;
use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Hydrator\Annotation\Subtype;

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
            'phpTypeHolder' => new ReflectionParameter(static fn(#[Subtype('mixed', true)] mixed $foo): mixed => $foo, 'foo'),
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
                #[Subtype('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => false,
        ];

        yield [
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity, Subtype('mixed', true)] private mixed $foo;
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
                return [$phpType->name, $phpType->allowsNull] === [$arg->name, $arg->allowsNull];
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
            'phpTypeHolder' => new ReflectionParameter(static fn(#[Subtype('mixed', true)] mixed $foo): mixed => $foo, 'foo'),
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
                #[Subtype('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => ['foo' => 'bar'],
        ];

        yield [
            'isSupportedPhpType' => true,
            'phpType' => new Type('mixed', true),
            'phpTypeHolder' => new ReflectionProperty(new class {
                #[MapEntity, Subtype('mixed', true)] private mixed $foo;
            }, 'foo'),
            'expectedResult' => ['foo' => 'bar'],
        ];
    }

    public function testGetWeight(): void
    {
        self::assertSame(-10, $this->mapEntityPhpTypeSchemaResolver->getWeight());
    }
}
