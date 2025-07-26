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

/**
 * @since 3.6.0
 */
final class UniqueValueValidator extends ConstraintValidator
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
        if (! $constraint instanceof UniqueValue) {
            throw new UnexpectedTypeException($constraint, UniqueValue::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $entityManagerName = $constraint->em ?? $this->defaultEntityManagerName;
        $entityManager = $this->entityManagerRegistry->getEntityManager($entityManagerName);

        /** @var class-string $entityName */
        $entityName = $constraint->entity ?? $this->context->getClassName();
        $entityMetadata = $entityManager->getClassMetadata($entityName);

        /** @var string $fieldName */
        $fieldName = $constraint->field ?? $this->context->getPropertyName();

        if (!$entityMetadata->hasField($fieldName) && !$entityMetadata->hasAssociation($fieldName)) {
            throw new ConstraintDefinitionException(\sprintf(
                'The field "%s" is not mapped by Doctrine and cannot be used in #[UniqueValue].',
                $fieldName,
            ));
        }

        if ($entityMetadata->hasAssociation($fieldName) && \is_object($value)) {
            $entityManager->initializeObject($value);
        }

        $result = $entityManager->getRepository($entityName)->findBy([$fieldName => $value], limit: 2);

        if ($result === []) {
            return;
        }

        if (\count($result) > 1) {
            $this->logger?->warning('#[UniqueValue] detected a uniqueness violation in the database.', [
                'entity' => $entityName,
                'field' => $fieldName,
                'em' => $entityManagerName?->getValue(),
            ]);
        }

        $this->context->buildViolation($constraint->message ?? UniqueValue::DEFAULT_ERROR_MESSAGE)
            ->setCode(UniqueValue::ERROR_CODE)
            ->addViolation();
    }
}
