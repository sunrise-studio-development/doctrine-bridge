<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Validator\Constraint;

/**
 * Import classes
 */
use DI\Container;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Import functions
 */
use function get_class;
use function is_array;
use function is_null;
use function is_object;
use function is_string;
use function reset;
use function sprintf;

/**
 * UniqueEntityValidator
 */
class UniqueEntityValidator extends ConstraintValidator
{

    /**
     * The application container
     *
     * @var Container
     */
    private $container;

    /**
     * Constructor of the class
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!is_object($entity)) {
            return;
        }

        $this->assertValidConstraintProperties($constraint);

        $doctrine = $this->container->get('doctrine');
        $entityName = get_class($entity);
        $entityManager = $doctrine->getManagerForClass($entityName);
        $entityMetadata = $entityManager->getClassMetadata($entityName);
        $repository = $entityManager->getRepository($entityName);
        $criteria = [];

        $this->assertValidConstraintPropertyValues($constraint, $entityMetadata);

        foreach ($constraint->fields as $fieldName) {
            $fieldValue = $entityMetadata->getFieldValue($entity, $fieldName);

            // the validation process stops if the value of one of the fields is NULL
            if (null === $fieldValue) {
                return;
            }

            if ($entityMetadata->hasAssociation($fieldName)) {
                $entityManager->initializeObject($fieldValue);
            }

            $criteria[$fieldName] = $fieldValue;
        }

        $result = $repository->findBy($criteria);

        if (empty($result)) {
            return;
        }

        $atPath = $constraint->atPath ?? reset($constraint->fields);
        $invalidValue = $criteria[$atPath] ?? reset($criteria);

        $this->context->buildViolation($constraint->message)
            ->atPath($atPath)
            ->setParameter('{{ value }}', $this->formatValue($invalidValue, self::PRETTY_DATE))
            ->setInvalidValue($invalidValue)
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->setCause($result)
            ->addViolation();
    }

    /**
     * Checks the validity of the given constraint properties
     *
     * @param Constraint $constraint
     *
     * @return void
     *
     * @throws UnexpectedTypeException
     */
    private function assertValidConstraintProperties(Constraint $constraint) : void
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        if (!is_array($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (!is_string($constraint->message)) {
            throw new UnexpectedTypeException($constraint->message, 'string');
        }

        if (!is_string($constraint->atPath) && !is_null($constraint->atPath)) {
            throw new UnexpectedTypeException($constraint->atPath, 'string or null');
        }
    }

    /**
     * Checks the validity of the given constraint property values
     *
     * @param Constraint $constraint
     * @param ClassMetadata $entityMetadata
     *
     * @return void
     *
     * @throws ConstraintDefinitionException
     */
    private function assertValidConstraintPropertyValues(Constraint $constraint, ClassMetadata $entityMetadata) : void
    {
        if ([] === $constraint->fields) {
            throw new ConstraintDefinitionException(
                'The fields list is empty.'
            );
        }

        foreach ($constraint->fields as $fieldName) {
            if (!is_string($fieldName)) {
                throw new ConstraintDefinitionException(
                    'The fields list contains an invalid structure.'
                );
            }

            if (!$entityMetadata->hasField($fieldName) && !$entityMetadata->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(
                    sprintf('The field "%s" is not mapped by Doctrine.', $fieldName)
                );
            }
        }
    }
}
