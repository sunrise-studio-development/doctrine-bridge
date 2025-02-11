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

namespace Sunrise\Bridge\Doctrine\Integration\Router\Annotation;

use Attribute;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class RequestedEntity
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $criteria = [],
        public ?EntityManagerNameInterface $em = null,
    ) {
    }
}
