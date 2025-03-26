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

namespace Sunrise\Bridge\Doctrine\Exception;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @since 3.3.0
 */
final class EntityValidationFailedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        private readonly object $invalidEntity,
        private readonly ConstraintViolationListInterface $constraintViolations,
    ) {
        parent::__construct('Entity validation failed.');
    }

    public function getInvalidEntity(): object
    {
        return $this->invalidEntity;
    }

    public function getConstraintViolations(): ConstraintViolationListInterface
    {
        return $this->constraintViolations;
    }
}
