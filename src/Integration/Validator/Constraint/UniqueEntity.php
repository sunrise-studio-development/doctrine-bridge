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
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class UniqueEntity extends Constraint
{
    public const ERROR_CODE = '1353312c-956e-44fa-81cb-15028a449136';
    public const DEFAULT_ERROR_MESSAGE = 'The value is not unique.';

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, string>|null $groups
     */
    public function __construct(
        /** @var array<array-key, string> */
        public readonly array $fields,
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
     * @return self::CLASS_CONSTRAINT
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
