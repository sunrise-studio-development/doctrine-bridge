<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Nekhay <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatoly Nekhay
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Integration\Validator\Constraint;

use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function count;
use function current;
use function is_object;
use function reset;
use function sprintf;

final class UniqueEntityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerRegistryInterface $entityManagerRegistry,
        private readonly ?EntityManagerNameInterface $defaultEntityManagerName = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        if ($constraint->fields === []) {
            throw new ConstraintDefinitionException('The #[UniqueEntity] constraint requires at least one field.');
        }

        if ($value === null) {
            return;
        }

        if (!is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $entityManagerName = $constraint->em ?? $this->defaultEntityManagerName;
        $entityManager = $this->entityManagerRegistry->getEntityManager($entityManagerName);
        $entityMetadata = $entityManager->getClassMetadata($value::class);

        /** @var array<string, mixed> $criteria */
        $criteria = [];

        foreach ($constraint->fields as $fieldName) {
            if (
                !$entityMetadata->hasField($fieldName) &&
                !$entityMetadata->hasAssociation($fieldName)
            ) {
                throw new ConstraintDefinitionException(sprintf(
                    'The field "%s" is not mapped by Doctrine and cannot be used in #[UniqueEntity].',
                    $fieldName,
                ));
            }

            $fieldValue = $entityMetadata->getFieldValue($value, $fieldName);
            // https://www.postgresql.org/docs/current/ddl-constraints.html#DDL-CONSTRAINTS-UNIQUE-CONSTRAINTS
            if ($fieldValue === null) {
                return;
            }

            if ($entityMetadata->hasAssociation($fieldName)) {
                /** @var object $fieldValue */
                $entityManager->initializeObject($fieldValue);
            }

            $criteria[$fieldName] = $fieldValue;
        }

        $entities = $entityManager->getRepository($value::class)->findBy($criteria, limit: 2);

        if ($entities === [] || (count($entities) === 1 && reset($entities) === $value)) {
            return;
        }

        if (count($entities) > 1) {
            $this->logger?->warning('#[UniqueEntity] detected a uniqueness violation in the database.', [
                'entity' => $value::class,
                'fields' => $constraint->fields,
                'em' => $entityManagerName?->getValue(),
            ]);
        }

        /** @var string $errorPath */
        $errorPath = $constraint->errorPath ?? current($constraint->fields);
        $errorMessage = $constraint->errorMessage ?? UniqueEntity::DEFAULT_ERROR_MESSAGE;

        $this->context->buildViolation($errorMessage)
            ->atPath($errorPath)
            ->setCode(UniqueEntity::ERROR_CODE)
            ->addViolation();
    }
}
