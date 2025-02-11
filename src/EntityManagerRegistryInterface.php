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

namespace Sunrise\Bridge\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Sunrise\Bridge\Doctrine\Exception\EntityManagerNotConfiguredException;

interface EntityManagerRegistryInterface
{
    public function hasEntityManager(?EntityManagerNameInterface $entityManagerName = null): bool;

    /**
     * @throws EntityManagerNotConfiguredException
     */
    public function getEntityManager(?EntityManagerNameInterface $entityManagerName = null): EntityManagerInterface;
}
