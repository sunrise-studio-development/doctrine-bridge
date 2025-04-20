<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunrise\Bridge\Doctrine\EventSubscriber\EntityValidationOnPreSave;
use Sunrise\Bridge\Doctrine\Exception\EntityValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EntityValidationOnPreSaveTest extends TestCase
{
    private ValidatorInterface&MockObject $validatorMock;

    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $eventSubscriber = new EntityValidationOnPreSave($this->validatorMock);
        self::assertSame([Events::prePersist, Events::preUpdate], $eventSubscriber->getSubscribedEvents());
    }

    public function testPrePersist(): void
    {
        $entity = new stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $eventArgs = new PrePersistEventArgs($entity, $entityManager);
        $this->validatorMock->expects($this->once())->method('validate')->with($entity);
        $eventSubscriber = new EntityValidationOnPreSave($this->validatorMock);
        $eventSubscriber->prePersist($eventArgs);
    }

    public function testPrePersistInvalidEntity(): void
    {
        $entity = new stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $eventArgs = new PrePersistEventArgs($entity, $entityManager);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects($this->once())->method('count')->willReturn(1);
        $this->validatorMock->expects($this->once())->method('validate')->with($entity)->willReturn($violations);
        $this->expectException(EntityValidationFailedException::class);

        $eventSubscriber = new EntityValidationOnPreSave($this->validatorMock);

        try {
            $eventSubscriber->prePersist($eventArgs);
        } catch (EntityValidationFailedException $e) {
            self::assertSame($violations, $e->getConstraintViolations());
            throw $e;
        }
    }

    public function testPreUpdate(): void
    {
        $entity = new stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
        $this->validatorMock->expects($this->once())->method('validate')->with($entity);
        $eventSubscriber = new EntityValidationOnPreSave($this->validatorMock);
        $eventSubscriber->preUpdate($eventArgs);
    }

    public function testPreUpdateInvalidEntity(): void
    {
        $entity = new stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects($this->once())->method('count')->willReturn(1);
        $this->validatorMock->expects($this->once())->method('validate')->with($entity)->willReturn($violations);
        $this->expectException(EntityValidationFailedException::class);

        $eventSubscriber = new EntityValidationOnPreSave($this->validatorMock);

        try {
            $eventSubscriber->preUpdate($eventArgs);
        } catch (EntityValidationFailedException $e) {
            self::assertSame($violations, $e->getConstraintViolations());
            throw $e;
        }
    }
}
