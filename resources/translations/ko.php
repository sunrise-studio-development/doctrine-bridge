<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;

return [
    ErrorMessage::ENTITY_NOT_FOUND => '엔티티를 찾을 수 없습니다.',
    ErrorMessage::VALIDATION_FAILED => '유효성 검사에 실패했습니다.',
    ErrorMessage::VALUE_NOT_UNIQUE => '값이 고유하지 않습니다.',
];
