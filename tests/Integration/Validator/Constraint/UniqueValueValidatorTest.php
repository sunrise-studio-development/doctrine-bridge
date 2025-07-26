<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValueValidator;
use PHPUnit\Framework\TestCase;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueValueValidatorTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $mockedEntityManagerRegistry;
    private EntityManagerInterface&MockObject $mockedEntityManager;
    private ClassMetadata&MockObject $mockedEntityMetadata;
    private EntityRepository&MockObject $mockedEntityRepository;
    private ExecutionContextInterface&MockObject $mockedExecutionContext;
    private ConstraintViolationBuilderInterface&MockObject $mockedConstraintViolationBuilder;
    private LoggerInterface&MockObject $mockedLogger;

    protected function setUp(): void
    {
        $this->mockedEntityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedEntityMetadata = $this->createMock(ClassMetadata::class);
        $this->mockedEntityRepository = $this->createMock(EntityRepository::class);
        $this->mockedExecutionContext = $this->createMock(ExecutionContextInterface::class);
        $this->mockedConstraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->mockedLogger = $this->createMock(LoggerInterface::class);
    }

    public function testUnexpectedConstraint(): void
    {
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $unexpectedConstraint = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $constraintValidator->validate(null, $unexpectedConstraint);
    }

    public function testNullValue(): void
    {
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $constraintValidator->validate(null, new UniqueValue());
    }

    public function testEmptyString(): void
    {
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $constraintValidator->validate('', new UniqueValue());
    }

    public function testUnknownField(): void
    {
        $constraint = new UniqueValue(entity: stdClass::class, field: 'foo');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(false);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->expectException(ConstraintDefinitionException::class);
        $constraintValidator->validate('test', $constraint);
    }

    public function testAssociatedField(): void
    {
        $value = self::createAnonymousClass();
        $constraint = new UniqueValue(entity: stdClass::class, field: 'foo');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(false);
        $this->mockedEntityMetadata->expects($this->exactly(2))->method('hasAssociation')->with('foo')->willReturn(true);
        $this->mockedEntityManager->expects($this->once())->method('initializeObject')->with($value);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => $value])->willReturn([]);
        $constraintValidator->validate($value, $constraint);
    }

    public function testEmptyResult(): void
    {
        $constraint = new UniqueValue(entity: stdClass::class, field: 'foo');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(true);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => 'test'], null, 2)->willReturn([]);
        $constraintValidator->validate('test', $constraint);
    }

    public function testResultWithSelf(): void
    {
        $entity = self::createAnonymousClass();
        $constraint = new UniqueValue(entity: $entity::class, field: 'foo');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->exactly(2))->method('getClassMetadata')->with($entity::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(true);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with($entity::class)->willReturn($this->mockedEntityRepository);
        $this->mockedExecutionContext->expects($this->once())->method('getObject')->willReturn($entity);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => 'test'], null, 2)->willReturn([$entity]);
        $constraintValidator->initialize($this->mockedExecutionContext);
        $constraintValidator->validate('test', $constraint);
    }

    public function testUniquenessViolation(): void
    {
        $constraint = new UniqueValue(entity: stdClass::class, field: 'foo');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry, logger: $this->mockedLogger);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(true);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => 'test'], null, 2)->willReturn([1, 2]);
        $this->mockedExecutionContext->expects($this->once())->method('buildViolation')->with(UniqueValue::DEFAULT_ERROR_MESSAGE)->willReturn($this->mockedConstraintViolationBuilder);
        $this->mockedConstraintViolationBuilder->expects($this->once())->method('setCode')->with(UniqueValue::ERROR_CODE)->willReturnSelf();
        $this->mockedConstraintViolationBuilder->expects($this->once())->method('addViolation');
        $this->mockedLogger->expects($this->once())->method('warning')->with('#[UniqueValue] detected a uniqueness violation in the database.', ['entity' => stdClass::class, 'field' => 'foo', 'em' => null]);
        $constraintValidator->initialize($this->mockedExecutionContext);
        $constraintValidator->validate('test', $constraint);
    }

    public function testCustomErrorMessage(): void
    {
        $constraint = new UniqueValue(entity: stdClass::class, field: 'foo', message: 'blah');
        $constraintValidator = new UniqueValueValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(true);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => 'test'], null, 2)->willReturn([new stdClass(), new stdClass()]);
        $this->mockedExecutionContext->expects($this->once())->method('buildViolation')->with('blah')->willReturn($this->mockedConstraintViolationBuilder);
        $this->mockedConstraintViolationBuilder->expects($this->once())->method('setCode')->with(UniqueValue::ERROR_CODE)->willReturnSelf();
        $this->mockedConstraintViolationBuilder->expects($this->once())->method('addViolation');
        $constraintValidator->initialize($this->mockedExecutionContext);
        $constraintValidator->validate('test', $constraint);
    }
}
