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

namespace Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation;

use Attribute;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class MapEntity
{
    public function __construct(
        public ?string $field = null,
        /** @var array<string, mixed> */
        public array $criteria = [],
        public ?EntityManagerNameInterface $em = null,
    ) {
    }
}
