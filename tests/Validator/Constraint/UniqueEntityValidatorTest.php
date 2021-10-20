<?php

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Validator\Constraint;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sunrise\Bridge\Doctrine\Tests\Fixtures;
use Sunrise\Bridge\Doctrine\Validator\Constraint\UniqueEntity;
use Sunrise\Bridge\Doctrine\Validator\Constraint\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;

class UniqueEntityValidatorTest extends TestCase
{
    use Fixtures\EntityManagerRegistryAwareTrait;

    private function createContainer() : ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->storage[ManagerRegistry::class] = $this->getEntityManagerRegistry(null, [
            'foo' => [
                'orm' => [
                    'metadata_driver' => 'annotations',
                ],
            ],
        ]);

        $container->storage[UniqueEntityValidator::class] = new UniqueEntityValidator(
            $container->storage[ManagerRegistry::class]
        );

        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->enableAnnotationMapping();
        $validatorBuilder->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($container));

        $container->storage[ValidatorInterface::class] = $validatorBuilder->getValidator();

        $container->method('has')->will($this->returnCallback(function ($name) use ($container) {
            return isset($container->storage[$name]);
        }));

        $container->method('get')->will($this->returnCallback(function ($name) use ($container) {
            return $container->storage[$name] ?? null;
        }));

        return $container;
    }

    public function testUnexpectedEntity()
    {
        $registry = $this->getEntityManagerRegistry();
        $constraintValidator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(['foo']);

        $this->expectException(UnexpectedTypeException::class);

        $constraintValidator->validate(null, $constraint);
    }

    public function testUnmanagedEntity() : void
    {
        $registry = $this->getEntityManagerRegistry(null, ['foo' => null, 'bar' => null]);
        $constraintValidator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(['foo']);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('Unable to get Entity Manager for the entity "stdClass".');

        $constraintValidator->validate(new \stdClass, $constraint);
    }

    public function testUnexpectedConstraint()
    {
        $registry = $this->getEntityManagerRegistry();
        $constraintValidator = new UniqueEntityValidator($registry);

        $this->expectException(UnexpectedTypeException::class);

        $constraintValidator->validate(new \stdClass, $this->createMock(Constraint::class));
    }

    public function testEmptyFields()
    {
        $registry = $this->getEntityManagerRegistry();
        $constraintValidator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity([]);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('No fields specified.');

        $constraintValidator->validate(new \stdClass, $constraint);
    }

    public function testUnknownField()
    {
        $registry = $this->getEntityManagerRegistry();
        $constraintValidator = new UniqueEntityValidator($registry);
        $constraint = new UniqueEntity(['undefined']);
        $entity = new Fixtures\Entity\Common\Uniqueable();

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The field "undefined" is not mapped by Doctrine.');

        $constraintValidator->validate($entity, $constraint);
    }

    public function testValidationWithOneField() : void
    {
        $container = $this->createContainer();
        $registry = $container->get(ManagerRegistry::class);
        $validator = $container->get(ValidatorInterface::class);

        $registry->getConnection()->query('INSERT INTO Uniqueable (id, foo) VALUES (
            "B5FF0CA7-AC5E-43D1-98EB-DFCCCC754B51",
            "3AE5A862-79BF-487D-9117-B8ECB9EB388A"
        )');

        $violations = $validator->validate(new Fixtures\Entity\Common\Uniqueable([
            'foo' => '3AE5A862-79BF-487D-9117-B8ECB9EB388A',
        ]));

        $this->assertCount(1, $violations);
    }

    public function testValidationWithSeveralFields() : void
    {
        $container = $this->createContainer();
        $registry = $container->get(ManagerRegistry::class);
        $validator = $container->get(ValidatorInterface::class);

        $registry->getConnection()->query('INSERT INTO Uniqueable (id, bar, baz) VALUES (
            "48067072-36A9-4FC7-9791-808B0AC9618C",
            "3C2E5599-2EEE-4ED5-878F-07F1F2F8ABBC",
            "9D2D6AB5-292C-4AD3-A0F4-6685F6D271EC"
        )');

        $violations = $validator->validate(new Fixtures\Entity\Common\Uniqueable([
            'bar' => '3C2E5599-2EEE-4ED5-878F-07F1F2F8ABBC',
            'baz' => '9D2D6AB5-292C-4AD3-A0F4-6685F6D271EC',
        ]));

        $this->assertCount(1, $violations);
    }

    public function testValidationWithAssociation() : void
    {
        $container = $this->createContainer();
        $registry = $container->get(ManagerRegistry::class);
        $validator = $container->get(ValidatorInterface::class);

        $registry->getConnection()->query('INSERT INTO Uniqueable (id) VALUES (
            "4374EDCA-6760-4E2F-B86E-A0190DFD61CA"
        )');

        $registry->getConnection()->query('INSERT INTO Uniqueable (id, qux_id) VALUES (
            "39DCF53A-FBFB-4B77-9CC3-A16ED7160D1C",
            "4374EDCA-6760-4E2F-B86E-A0190DFD61CA"
        )');

        $violations = $validator->validate(new Fixtures\Entity\Common\Uniqueable([
            'qux' => new Fixtures\Entity\Common\Uniqueable([
                'id' => '4374EDCA-6760-4E2F-B86E-A0190DFD61CA',
            ]),
        ]));

        $this->assertCount(1, $violations);
    }

    public function testSuccessfulValidation() : void
    {
        $container = $this->createContainer();
        $validator = $container->get(ValidatorInterface::class);

        $violations = $validator->validate(new Fixtures\Entity\Common\Uniqueable([
            'foo' => 'A1F4CB83-54A0-4975-88F3-D2F0F4D0CE26',
        ]));

        $this->assertCount(0, $violations);
    }
}
