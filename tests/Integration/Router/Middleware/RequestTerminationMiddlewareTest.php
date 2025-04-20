<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Router\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Bridge\Doctrine\Dictionary\TranslationDomain;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Exception\EntityValidationFailedException;
use Sunrise\Bridge\Doctrine\Integration\Router\Middleware\RequestTerminationMiddleware;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Sunrise\Http\Router\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Throwable;

final class RequestTerminationMiddlewareTest extends TestCase
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

    private function createRequestTerminationMiddleware(
        ?int $validationFailedErrorStatusCode = null,
        ?string $validationFailedErrorMessage = null,
    ): RequestTerminationMiddleware {
        return new RequestTerminationMiddleware(
            entityManagerRegistry: $this->mockedEntityManagerRegistry,
            flushableEntityManagerNames: $this->mockedFlushableEntityManagerNames,
            clearableEntityManagerNames: $this->mockedClearableEntityManagerNames,
            validationFailedErrorStatusCode: $validationFailedErrorStatusCode,
            validationFailedErrorMessage: $validationFailedErrorMessage,
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

        self::assertSame($response, $this->createRequestTerminationMiddleware()->process($request, $handler));
    }

    public function testException(): void
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
        $this->createRequestTerminationMiddleware()->process($request, $handler);
    }

    public function testEntityValidationFailedException(): void
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
        $violations = ConstraintViolationList::createFromMessage('foo');
        $exception = new EntityValidationFailedException($violations);

        $handler->expects($this->once())->method('handle')->with($request)->willThrowException($exception);

        $this->expectException(HttpException::class);

        try {
            $this->createRequestTerminationMiddleware()->process($request, $handler);
        } catch (HttpException $e) {
            self::assertSame($exception, $e->getPrevious());
            self::assertSame(400, $e->getCode());
            self::assertSame(TranslationDomain::DOCTRINE_BRIDGE, $e->getTranslationDomain());
            $actualViolations = $e->getConstraintViolations();
            self::assertArrayHasKey(0, $actualViolations);
            self::assertSame('foo', $actualViolations[0]->getMessage());

            throw $e;
        }
    }

    public function testEntityValidationFailedExceptionWithCustomStatusCodeAndMessage(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $violations = ConstraintViolationList::createFromMessage('foo');
        $exception = new EntityValidationFailedException($violations);

        $handler->expects($this->once())->method('handle')->with($request)->willThrowException($exception);
        $this->expectException(HttpException::class);

        try {
            $this->createRequestTerminationMiddleware(
                validationFailedErrorStatusCode: 422,
                validationFailedErrorMessage: 'foo',
            )->process($request, $handler);
        } catch (HttpException $e) {
            self::assertSame(422, $e->getCode());
            self::assertSame('foo', $e->getMessage());
            throw $e;
        }
    }
}
