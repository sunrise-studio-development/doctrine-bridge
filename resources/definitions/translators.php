<?php

declare(strict_types=1);

use Sunrise\Bridge\Doctrine\Dictionary\TranslationDomain;
use Sunrise\Translator\Translator\DirectoryTranslator;

use function DI\add;
use function DI\create;

return [
    'translator.translators' => add([
        create(DirectoryTranslator::class)
            ->constructor(
                domain: TranslationDomain::DOCTRINE_BRIDGE,
                directory: __DIR__ . '/../translations',
            ),
    ]),
];
