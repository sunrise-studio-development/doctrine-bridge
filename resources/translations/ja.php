<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;

return [
    ErrorMessage::ENTITY_NOT_FOUND => 'エンティティが見つかりませんでした。',
    ErrorMessage::VALIDATION_FAILED => 'バリデーションに失敗しました。',
    ErrorMessage::VALUE_NOT_UNIQUE => '値がユニークではありません。',
];
