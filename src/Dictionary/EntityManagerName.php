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

namespace Sunrise\Bridge\Doctrine\Dictionary;

use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;

enum EntityManagerName: string implements EntityManagerNameInterface
{
    case Default = 'default';

    public function getValue(): string
    {
        return $this->value;
    }
}
