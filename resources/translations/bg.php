<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;

return [
    ErrorMessage::ENTITY_NOT_FOUND => 'Обектът не е намерен.',
    ErrorMessage::VALIDATION_FAILED => 'Валидирането не бе успешно.',
    ErrorMessage::VALUE_NOT_UNIQUE => 'Стойността не е уникална.',
];
