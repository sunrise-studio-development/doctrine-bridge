<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Router\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Router\Middleware\DoctrineRequestTerminationMiddleware;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Throwable;

final class DoctrineRequestTerminationMiddlewareTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $mockedEntityManagerRegistry;
    private EntityManagerInterface&MockObject $mockedEntityManager;

    /** @var array<array-key, EntityManagerNameInterface> */
    private array $mockedFlushableEntityManagerNames = [];
    /** @var array<array-key, EntityManagerNameInterface> */
    private array $mockedClearableEntityManagerNames = [];

    protected function setUp(): void
    {
        $this->mockedEntityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedFlushableEntityManagerNames = [];
        $this->mockedClearableEntityManagerNames = [];
    }

    private function createDoctrineRequestTerminationMiddleware(): DoctrineRequestTerminationMiddleware
    {
        return new DoctrineRequestTerminationMiddleware(
            entityManagerRegistry: $this->mockedEntityManagerRegistry,
            flushableEntityManagerNames: $this->mockedFlushableEntityManagerNames,
            clearableEntityManagerNames: $this->mockedClearableEntityManagerNames,
        );
    }

    public function testProcess(): void
    {
        $this->mockedFlushableEntityManagerNames[] = $this->mockEntityManagerName('foo');
        $this->mockedFlushableEntityManagerNames[] = $this->mockEntityManagerName('bar');
        $this->mockedClearableEntityManagerNames[] = $this->mockEntityManagerName('baz');
        $this->mockedClearableEntityManagerNames[] = $this->mockEntityManagerName('qux');

        $this->mockedEntityManagerRegistry->expects($this->exactly(4))->method('hasEntityManager')->withAnyParameters()->willReturnCallback(
            function (EntityManagerNameInterface $entityManagerName): bool {
                self::assertContains($entityManagerName, [...$this->mockedFlushableEntityManagerNames, ...$this->mockedClearableEntityManagerNames]);
                return true;
            }
        );

        $this->mockedEntityManagerRegistry->expects($this->exactly(4))->method('getEntityManager')->withAnyParameters()->willReturnCallback(
            function (EntityManagerNameInterface $entityManagerName): EntityManagerInterface {
                self::assertContains($entityManagerName, [...$this->mockedFlushableEntityManagerNames, ...$this->mockedClearableEntityManagerNames]);
                return $this->mockedEntityManager;
            }
        );

        $this->mockedEntityManager->expects($this->exactly(2))->method('flush');
        $this->mockedEntityManager->expects($this->exactly(2))->method('clear');

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler->expects($this->once())->method('handle')->with($request)->willReturn($response);

        self::assertSame($response, $this->createDoctrineRequestTerminationMiddleware()->process($request, $handler));
    }

    public function testRequestHandlerException(): void
    {
        $this->mockedFlushableEntityManagerNames[] = $this->mockEntityManagerName('foo');
        $this->mockedClearableEntityManagerNames[] = $this->mockEntityManagerName('bar');
        $this->mockedClearableEntityManagerNames[] = $this->mockEntityManagerName('baz');

        $this->mockedEntityManagerRegistry->expects($this->exactly(2))->method('hasEntityManager')->withAnyParameters()->willReturnCallback(
            function (EntityManagerNameInterface $entityManagerName): bool {
                self::assertContains($entityManagerName, $this->mockedClearableEntityManagerNames);
                return true;
            }
        );

        $this->mockedEntityManagerRegistry->expects($this->exactly(2))->method('getEntityManager')->withAnyParameters()->willReturnCallback(
            function (EntityManagerNameInterface $entityManagerName): EntityManagerInterface {
                self::assertContains($entityManagerName, $this->mockedClearableEntityManagerNames);
                return $this->mockedEntityManager;
            }
        );

        $this->mockedEntityManager->expects($this->never())->method('flush');
        $this->mockedEntityManager->expects($this->exactly(2))->method('clear');

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $exception = $this->createMock(Throwable::class);

        $handler->expects($this->once())->method('handle')->with($request)->willThrowException($exception);

        $this->expectException($exception::class);
        $this->createDoctrineRequestTerminationMiddleware()->process($request, $handler);
    }
}
