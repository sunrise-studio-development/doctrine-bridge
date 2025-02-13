<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Integration\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntity;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntityValidator;
use Sunrise\Bridge\Doctrine\Tests\TestKit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueEntityValidatorTest extends TestCase
{
    use TestKit;

    private EntityManagerRegistryInterface&MockObject $mockedEntityManagerRegistry;
    private EntityManagerInterface&MockObject $mockedEntityManager;
    private ClassMetadata&MockObject $mockedEntityMetadata;
    private EntityRepository&MockObject $mockedEntityRepository;

    protected function setUp(): void
    {
        $this->mockedEntityManagerRegistry = $this->createMock(EntityManagerRegistryInterface::class);
        $this->mockedEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockedEntityMetadata = $this->createMock(ClassMetadata::class);
        $this->mockedEntityRepository = $this->createMock(EntityRepository::class);
    }

    public function testUnexpectedConstraint(): void
    {
        $unexpectedConstraint = $this->createMock(Constraint::class);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $this->expectException(UnexpectedTypeException::class);
        $constraintValidator->validate(null, $unexpectedConstraint);
    }

    public function testConstraintWithoutFields(): void
    {
        $constraint = new UniqueEntity(fields: []);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $this->expectException(ConstraintDefinitionException::class);
        $constraintValidator->validate(null, $constraint);
    }

    public function testNullValue(): void
    {
        $constraint = new UniqueEntity(fields: ['foo']);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $constraintValidator->validate(null, $constraint);
    }

    public function testUnexpectedValue(): void
    {
        $constraint = new UniqueEntity(fields: ['foo']);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->never())->method('getEntityManager');
        $this->expectException(UnexpectedValueException::class);
        $constraintValidator->validate(false, $constraint);
    }

    public function testUnknownField(): void
    {
        $constraint = new UniqueEntity(fields: ['foo']);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(false);
        $this->mockedEntityMetadata->expects($this->once())->method('hasAssociation')->with('foo')->willReturn(false);
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The field "foo" is not mapped by Doctrine and cannot be used in #[UniqueEntity].');
        $constraintValidator->validate(new stdClass(), $constraint);
    }

    public function testInitializeAssociation(): void
    {
        $value = new stdClass();
        $foo = new stdClass();
        $constraint = new UniqueEntity(fields: ['foo']);
        $constraintValidator = new UniqueEntityValidator($this->mockedEntityManagerRegistry);
        $this->mockedEntityManagerRegistry->expects($this->once())->method('getEntityManager')->with(null)->willReturn($this->mockedEntityManager);
        $this->mockedEntityManager->expects($this->once())->method('getClassMetadata')->with(stdClass::class)->willReturn($this->mockedEntityMetadata);
        $this->mockedEntityMetadata->expects($this->once())->method('hasField')->with('foo')->willReturn(false);
        $this->mockedEntityMetadata->expects($this->exactly(2))->method('hasAssociation')->with('foo')->willReturn(true);
        $this->mockedEntityMetadata->expects($this->once())->method('getFieldValue')->with($value, 'foo')->willReturn($foo);
        $this->mockedEntityManager->expects($this->once())->method('initializeObject')->with($foo);
        $this->mockedEntityManager->expects($this->once())->method('getRepository')->with(stdClass::class)->willReturn($this->mockedEntityRepository);
        $this->mockedEntityRepository->expects($this->once())->method('findBy')->with(['foo' => $foo])->willReturn([]);
        $constraintValidator->validate($value, $constraint);
    }
}
