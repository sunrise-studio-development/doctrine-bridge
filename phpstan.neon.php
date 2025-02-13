<?php

declare(strict_types=1);

$config = [
    'includes' => [
        __DIR__ . '/phpstan.neon',
    ],
    'parameters' => [
        'phpVersion' => PHP_VERSION_ID,
    ],
];

return $config;
