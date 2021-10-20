<?php declare(strict_types=1);

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2020, Anatoly Fenric
 * @license https://github.com/sunrise-php/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-php/doctrine-bridge
 */

namespace Sunrise\Bridge\Doctrine\Validator\Constraint;

/**
 * Import classes
 */
use Doctrine\Persistence\ManagerRegistry as EntityManagerRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Import functions
 */
use function get_class;
use function is_object;
use function sprintf;

/**
 * UniqueEntityValidator
 */
class UniqueEntityValidator extends ConstraintValidator
{

    /**
     * @var EntityManagerRegistryInterface
     */
    private $entityManagerRegistry;

    /**
     * @param EntityManagerRegistryInterface $entityManagerRegistry
     */
    public function __construct(EntityManagerRegistryInterface $entityManagerRegistry)
    {
        $this->entityManagerRegistry = $entityManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        $this->validateEntity($entity);
        $this->validateConstraint($constraint);

        $entityName = get_class($entity);

        $entityManager = isset($constraint->em) ?
            $this->entityManagerRegistry->getManager($constraint->em) :
            $this->entityManagerRegistry->getManagerForClass($entityName);

        if (null === $entityManager) {
            throw new ConstraintDefinitionException(sprintf(
                'Unable to get Entity Manager for the entity "%s".',
                $entityName
            ));
        }

        $entityMetadata = $entityManager->getClassMetadata($entityName);

        $criteria = [];

        foreach ($constraint->fields as $fieldName) {
            if (!$entityMetadata->hasField($fieldName) &&
                !$entityMetadata->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf(
                    'The field "%s" is not mapped by Doctrine.',
                    $fieldName
                ));
            }

            $fieldValue = $entityMetadata->getFieldValue($entity, $fieldName);
            if (null === $fieldValue) {
                return;
            }

            $criteria[$fieldName] = $fieldValue;
        }

        /** @var iterable<object> */
        $result = $entityManager->getRepository($entityName)->findBy($criteria, null, 2);

        $foundEntities = [];
        foreach ($result as $foundEntity) {
            $foundEntities[] = $foundEntity;
        }

        if (!isset($foundEntities[0]) || (!isset($foundEntities[1]) && $entity === $foundEntities[0])) {
            return;
        }

        $atPath = $constraint->atPath ?? $constraint->fields[0];
        $invalidValue = $criteria[$atPath] ?? $criteria[$constraint->fields[0]];
        $formattedInvalidValue = $this->formatValue($invalidValue, self::PRETTY_DATE);

        $this->context->buildViolation($constraint->message)
            ->atPath($atPath)
            ->setParameter('{{ value }}', $formattedInvalidValue)
            ->setInvalidValue($invalidValue)
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->setCause($result)
            ->addViolation();
    }

    /**
     * Validates the given constraint
     *
     * @param mixed $constraint
     *
     * @return void
     *
     * @throws ConstraintDefinitionException
     * @throws UnexpectedTypeException
     */
    private function validateConstraint($constraint) : void
    {
        if (!($constraint instanceof UniqueEntity)) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        if (empty($constraint->fields)) {
            throw new ConstraintDefinitionException('No fields specified.');
        }
    }

    /**
     * Validates the given entity
     *
     * @param mixed $entity
     *
     * @return void
     *
     * @throws UnexpectedTypeException
     */
    private function validateEntity($entity) : void
    {
        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }
    }
}
