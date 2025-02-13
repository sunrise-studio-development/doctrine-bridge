<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Router\ParameterResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;
use JsonSerializable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionParameter;
use stdClass;
use Sunrise\Bridge\Doctrine\Dictionary\EntityManagerName;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Annotation\RequestedEntity;
use Sunrise\Bridge\Doctrine\Integration\Router\ParameterResolver\RequestedEntityParameterResolver;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Sunrise\Http\Router\Dictionary\ErrorMessage;
use Sunrise\Http\Router\Exception\HttpException;
use Sunrise\Http\Router\RouteInterface;

final class RequestedEntityParameterResolverTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $mockedEntityManagerRegistry;
    private EntityManagerInterface&MockObject $mockedEntityManager;
    private ClassMetadata&MockObject $mockedEntityMetadata;
    private EntityRepository&MockObject $mockedEntityRepository;
    private ServerRequestInterface&MockObject $mockedServerRequest;
    private RouteInterface&MockObject $mockedRoute;

    protected function setUp(): void
    {
        $this->mockedEntityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedEntityMetadata = $this->createMock(ClassMetadata::class);
        $this->mockedEntityRepository = $this->createMock(EntityRepository::class);
        $this->mockedServerRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockedRoute = $this->createMock(RouteInterface::class);
    }

    public function testResolveParameter(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testUnsupportedContext(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, null);
        self::assertFalse($arguments->valid());
    }

    public function testNonAnnotatedParameter(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $parameter = new ReflectionParameter(fn(stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertFalse($arguments->valid());
    }

    public function testNonNamedParameterType(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] stdClass&JsonSerializable $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must be typed with an entity/');
        $arguments->valid();
    }

    public function testBuiltInParameterType(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] object $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must be typed with an entity/');
        $arguments->valid();
    }

    public function testUnknownClass(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] \UnknownClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is typed with a non-existent entity/');
        $arguments->valid();
    }

    public function testEntityNotFound(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn(null);
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(ErrorMessage::RESOURCE_NOT_FOUND);
        $arguments->valid();
    }

    public function testNullableParameter(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn(null);
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] ?stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertNull($arguments->current());
    }

    public function testDefaultEntityManagerName(): void
    {
        $defaultEntityManagerName = $this->mockEntityManagerName('default');
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with($defaultEntityManagerName)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry, $defaultEntityManagerName))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testEntityManagerNameFromAnnotation(): void
    {
        $defaultEntityManagerName = $this->mockEntityManagerName('default');
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(EntityManagerName::Default)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity(em: EntityManagerName::Default)] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry, $defaultEntityManagerName))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testFieldNameFromAnnotation(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->never())->method('getClassMetadata');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('publicId')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['publicId' => 1])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity(field: 'publicId')] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testVariableNameFromAnnotation(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('entityId')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity(variable: 'entityId')] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testAdditionalCriteriaFromAnnotation(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('id')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['id' => 1, 'foo' => 'bar'])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity(criteria: ['foo' => 'bar'])] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testFullyPopulatedAnnotation(): void
    {
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->never())->method('getClassMetadata');
        $this->mockedServerRequest->expects($this->once())->method('getAttribute')->with(RouteInterface::class)->willReturn($this->mockedRoute);
        $this->mockedRoute->expects($this->once())->method('getAttribute')->with('entityId')->willReturn(1);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findOneBy')->with(['publicId' => 1, 'foo' => 'bar'])->willReturn($expectedEntity = new stdClass());
        $parameter = new ReflectionParameter(fn(#[RequestedEntity(field: 'publicId', variable: 'entityId', criteria: ['foo' => 'bar'])] stdClass $p) => null, 'p');
        $arguments = (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->resolveParameter($parameter, $this->mockedServerRequest);
        self::assertSame($expectedEntity, $arguments->current());
    }

    public function testWeight(): void
    {
        self::assertSame(-10, (new RequestedEntityParameterResolver($this->mockedEntityManagerRegistry))->getWeight());
    }
}
