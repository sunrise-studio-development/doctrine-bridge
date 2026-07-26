<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatolii Nekhai <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatolii Nekhai
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation;

use Attribute;
use Sunrise\Hydrator\Annotation\Subtype;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ValueType extends Subtype
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name, bool $allowsNull = false)
    {
        parent::__construct($name, $allowsNull);
    }
}
