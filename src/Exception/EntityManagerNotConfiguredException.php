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

namespace Sunrise\Bridge\Doctrine\Exception;

use RuntimeException;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;

final class EntityManagerNotConfiguredException extends RuntimeException implements ExceptionInterface
{
    public function __construct(EntityManagerNameInterface $entityManagerName)
    {
        parent::__construct(\sprintf('The entity manager "%s" is not configured.', $entityManagerName->getValue()));
    }
}
