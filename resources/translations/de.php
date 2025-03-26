<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;

return [
    ErrorMessage::ENTITY_NOT_FOUND => 'Die Entität wurde nicht gefunden.',
    ErrorMessage::VALIDATION_FAILED => 'Die Validierung ist fehlgeschlagen.',
    ErrorMessage::VALUE_NOT_UNIQUE => 'Der Wert ist nicht eindeutig.',
];
