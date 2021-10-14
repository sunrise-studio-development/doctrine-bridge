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
use function is_array;
use function is_string;
use function reset;
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
     * Validates the given entity
     *
     * @param object $entity
     * @param Constraint $constraint
     *
     * @throws ConstraintDefinitionException
     * @throws UnexpectedTypeException
     */
    public function validate(object $entity, Constraint $constraint)
    {
        $this->validateConstraint($constraint);

        $entityName = get_class($entity);

        $entityManager = isset($constraint->em) ?
            $this->entityManagerRegistry->getManager($constraint->em) :
            $this->entityManagerRegistry->getManagerForClass($entityName);

        // The "getManagerForClass" method may return null,
        // but the "getManager" method should throw an exception...
        if (null === $entityManager) {
            throw new ConstraintDefinitionException(sprintf(
                'Unable to get Entity Manager for %s.',
                $entityName
            ));
        }

        $entityMetadata = $entityManager->getClassMetadata($entityName);

        $criteria = [];

        foreach ($constraint->fields as $fieldName) {
            if (!$entityMetadata->hasField($fieldName) &&
                !$entityMetadata->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf(
                    'The "%s" field is not mapped by Doctrine.',
                    $fieldName
                ));
            }

            $fieldValue = $entityMetadata->getFieldValue($entity, $fieldName);
            if (null === $fieldValue) {
                return;
            }

            if ($entityMetadata->hasAssociation($fieldName)) {
                $entityManager->initializeObject($fieldValue);
            }

            $criteria[$fieldName] = $fieldValue;
        }

        /** @var iterable<object> */
        $result = $entityManager->getRepository($entityName)->findBy($criteria, null, 2);

        $foundEntities = [];
        foreach ($result as $foundEntity) {
            $foundEntities[] = $foundEntity;
        }

        if (empty($foundEntities)) {
            return;
        }

        if (1 === count($foundEntities) && $entity === $foundEntities[0]) {
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
     * Validates the given constraint
     *
     * @param Constraint $constraint
     *
     * @return void
     *
     * @throws ConstraintDefinitionException
     * @throws UnexpectedTypeException
     */
    private function validateConstraint(Constraint $constraint) : void
    {
        if (!($constraint instanceof UniqueEntity)) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        if (null !== $constraint->em && !is_string($constraint->em)) {
            throw new UnexpectedTypeException($constraint->em, 'string');
        }

        if (!is_array($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (!is_string($constraint->message)) {
            throw new UnexpectedTypeException($constraint->message, 'string');
        }

        if (null !== $constraint->atPath && !is_string($constraint->atPath)) {
            throw new UnexpectedTypeException($constraint->atPath, 'string');
        }

        if (empty($constraint->fields)) {
            throw new ConstraintDefinitionException('No fields specified.');
        }
    }
}
