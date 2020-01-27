<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Validator\Constraint;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\Tests\Fixture;
use Arus\Doctrine\Bridge\Validator\Constraint\UniqueEntity;
use Arus\Doctrine\Bridge\Validator\Constraint\UniqueEntityValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * UniqueEntityValidatorTest
 */
class UniqueEntityValidatorTest extends TestCase
{
    use Fixture\ContainerAwareTrait;
    use Fixture\DatabaseSchemaToolTrait;

    /**
     * @return void
     */
    public function testUnexpectedConstraint() : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(UnexpectedTypeException::class);

        $this->expectExceptionMessage(
            'Expected argument of type "' . UniqueEntity::class . '", "' . Valid::class . '" given'
        );

        $constraintValidator->validate(new Fixture\Entity\Bar(), new Valid());
    }

    /**
     * @param mixed $invalidValue
     * @param string $invalidValueType
     *
     * @return void
     *
     * @dataProvider invalidFieldsProvider
     */
    public function testInvalidFields($invalidValue, string $invalidValueType) : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "' . $invalidValueType . '" given');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => $invalidValue,
        ]));
    }

    /**
     * @return array
     */
    public function invalidFieldsProvider() : array
    {
        return [
            [null, 'NULL'],
            [true, 'boolean'],
            [false, 'boolean'],
            [0, 'integer'],
            [0.0, 'double'],
            ['', 'string'],
            [new \stdClass, 'stdClass'],
            [function () {
            }, 'Closure'],
            [\STDOUT, 'resource'],
        ];
    }

    /**
     * @param mixed $invalidValue
     * @param string $invalidValueType
     *
     * @return void
     *
     * @dataProvider invalidMessageProvider
     */
    public function testInvalidMessage($invalidValue, string $invalidValueType) : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "' . $invalidValueType . '" given');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => ['foo'],
            'message' => $invalidValue,
        ]));
    }

    /**
     * @return array
     */
    public function invalidMessageProvider() : array
    {
        return [
            [null, 'NULL'],
            [true, 'boolean'],
            [false, 'boolean'],
            [0, 'integer'],
            [0.0, 'double'],
            [[], 'array'],
            [new \stdClass, 'stdClass'],
            [function () {
            }, 'Closure'],
            [\STDOUT, 'resource'],
        ];
    }

    /**
     * @param mixed $invalidValue
     * @param string $invalidValueType
     *
     * @return void
     *
     * @dataProvider invalidAtPathProvider
     */
    public function testInvalidAtPath($invalidValue, string $invalidValueType) : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string or null", "' . $invalidValueType . '" given');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => ['foo'],
            'atPath' => $invalidValue,
        ]));
    }

    /**
     * @return array
     */
    public function invalidAtPathProvider() : array
    {
        return [
            [true, 'boolean'],
            [false, 'boolean'],
            [0, 'integer'],
            [0.0, 'double'],
            [[], 'array'],
            [new \stdClass, 'stdClass'],
            [function () {
            }, 'Closure'],
            [\STDOUT, 'resource'],
        ];
    }

    /**
     * @return void
     */
    public function testEmptyFields() : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The fields list is empty.');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => [],
        ]));
    }

    /**
     * @param mixed $invalidValue
     *
     * @return void
     *
     * @dataProvider invalidFieldProvider
     */
    public function testInvalidField($invalidValue) : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The fields list contains an invalid structure.');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => [$invalidValue],
        ]));
    }

    /**
     * @return array
     */
    public function invalidFieldProvider() : array
    {
        return [
            [null],
            [true],
            [false],
            [0],
            [0.0],
            [[]],
            [new \stdClass],
            [function () {
            }],
            [\STDOUT],
        ];
    }

    /**
     * @return void
     */
    public function testNonexistentField() : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The field "baz" is not mapped by Doctrine.');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => ['baz'],
        ]));
    }

    /**
     * @return void
     */
    public function testNotMappedField() : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('The field "bar" is not mapped by Doctrine.');

        $constraintValidator->validate(new Fixture\Entity\Bar(), new UniqueEntity([
            'fields' => ['bar'],
        ]));
    }

    /**
     * @param mixed $invalidValue
     *
     * @return void
     *
     * @dataProvider notObjectProvider
     */
    public function testValidateNotObject($invalidValue) : void
    {
        $container = $this->getContainer();
        $constraintValidator = new UniqueEntityValidator($container);

        $constraintValidator->validate($invalidValue, new UniqueEntity([
            'fields' => [],
        ]));

        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function notObjectProvider() : array
    {
        return [
            [null],
            [true],
            [false],
            [0],
            [0.0],
            [''],
            [[]],
            [\STDOUT],
        ];
    }

    /**
     * @return void
     */
    public function testValidate() : void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $validator = $container->get('validator');
        $manager = $doctrine->getManager('foo');

        $this->createDatabaseSchema($manager);

        $entry = new Fixture\Entity\Baz([
            'foo' => 'foo.value',
            'bar' => 'bar.value',
            'baz' => 'baz.value',
            'qux' => 'qux.value',
        ]);

        $manager->persist($entry);

        $manager->persist(new Fixture\Entity\Baz([
            'quux' => $entry,
        ]));

        $manager->persist(new Fixture\Entity\Baz([
            'bar' => 'bar.value',
        ]));

        $manager->persist(new Fixture\Entity\Baz([
            'baz' => 'baz.value',
        ]));

        $manager->persist(new Fixture\Entity\Baz([
            'qux' => 'qux.value',
        ]));

        $manager->flush();

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'bar' => 'bar.value',
            'baz' => 'baz.value',
        ]));

        $this->assertCount(1, $violations);
        $this->assertSame('The value "bar.value" is not unique.', $violations->get(0)->getMessage());
        $this->assertSame('bar', $violations->get(0)->getPropertyPath());
        $this->assertSame('bar.value', $violations->get(0)->getInvalidValue());

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'baz' => 'baz.value',
            'qux' => 'qux.value',
        ]));

        $this->assertCount(1, $violations);
        $this->assertSame('The value "baz.value" is not unique.', $violations->get(0)->getMessage());
        $this->assertSame('xxx', $violations->get(0)->getPropertyPath());
        $this->assertSame('baz.value', $violations->get(0)->getInvalidValue());

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'qux' => 'qux.value',
            'bar' => 'bar.value',
        ]));

        $this->assertCount(1, $violations);
        $this->assertSame('non-unique value: "bar.value"', $violations->get(0)->getMessage());
        $this->assertSame('bar', $violations->get(0)->getPropertyPath());
        $this->assertSame('bar.value', $violations->get(0)->getInvalidValue());

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'foo' => 'foo.value',
            'bar' => 'bar.value',
            'baz' => 'baz.value',
            'qux' => 'qux.value',
        ]));

        $this->assertCount(4, $violations);

        $this->assertSame('The value "foo.value" is not unique.', $violations->get(0)->getMessage());
        $this->assertSame('foo', $violations->get(0)->getPropertyPath());
        $this->assertSame('foo.value', $violations->get(0)->getInvalidValue());

        $this->assertSame('The value "bar.value" is not unique.', $violations->get(1)->getMessage());
        $this->assertSame('bar', $violations->get(1)->getPropertyPath());
        $this->assertSame('bar.value', $violations->get(1)->getInvalidValue());

        $this->assertSame('The value "baz.value" is not unique.', $violations->get(2)->getMessage());
        $this->assertSame('xxx', $violations->get(2)->getPropertyPath());
        $this->assertSame('baz.value', $violations->get(2)->getInvalidValue());

        $this->assertSame('non-unique value: "bar.value"', $violations->get(3)->getMessage());
        $this->assertSame('bar', $violations->get(3)->getPropertyPath());
        $this->assertSame('bar.value', $violations->get(3)->getInvalidValue());

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'foo' => 'unique.foo.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'bar' => 'unique.bar.value',
            'baz' => 'baz.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'bar' => 'bar.value',
            'baz' => 'unique.baz.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'baz' => 'unique.baz.value',
            'qux' => 'qux.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'baz' => 'baz.value',
            'qux' => 'unique.qux.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'qux' => 'unique.qux.value',
            'bar' => 'bar.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'qux' => 'qux.value',
            'bar' => 'unique.bar.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'foo' => 'unique.foo.value',
            'bar' => 'unique.bar.value',
            'baz' => 'unique.baz.value',
            'qux' => 'unique.qux.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'bar' => 'bar.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'baz' => 'baz.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'qux' => 'qux.value',
        ]));

        $this->assertCount(0, $violations);

        $violations = $validator->validate(new Fixture\Entity\Baz([
            'quux' => $entry,
        ]));

        $this->assertCount(1, $violations);
    }
}
