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

use Attribute;
use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Symfony\Component\Validator\Constraint;

/**
 * @since 3.6.0
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class UniqueValue extends Constraint
{
    public const ERROR_CODE = '559fe835-1b05-4059-bbbd-70eb8c213725';
    public const DEFAULT_ERROR_MESSAGE = ErrorMessage::VALUE_NOT_UNIQUE;

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, string>|null $groups
     */
    public function __construct(
        /** @var class-string */
        public readonly ?string $entity = null,
        public readonly ?string $field = null,
        public readonly ?string $errorPath = null,
        public readonly ?string $errorMessage = null,
        public readonly ?EntityManagerNameInterface $em = null,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    /**
     * @inheritDoc
     *
     * @return self::PROPERTY_CONSTRAINT
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
