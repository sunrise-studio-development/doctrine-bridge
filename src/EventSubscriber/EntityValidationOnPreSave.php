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

namespace Sunrise\Bridge\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Sunrise\Bridge\Doctrine\Exception\EntityValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @since 3.3.0
 */
final readonly class EntityValidationOnPreSave implements EventSubscriber
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->validateEntity($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->validateEntity($args->getObject());
    }

    private function validateEntity(object $object): void
    {
        $violations = $this->validator->validate($object);
        if ($violations->count() > 0) {
            throw new EntityValidationFailedException($object, $violations);
        }
    }
}
