## Arus // Doctrine Bridge

[![Build Status](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/badges/build.png?b=master)](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/arus/doctrine-bridge/v/stable)](https://packagist.org/packages/arus/doctrine-bridge)
[![Total Downloads](https://poser.pugx.org/arus/doctrine-bridge/downloads)](https://packagist.org/packages/arus/doctrine-bridge)
[![License](https://poser.pugx.org/arus/doctrine-bridge/license)](https://packagist.org/packages/arus/doctrine-bridge)

## Installation (via composer)

```bash
composer require 'arus/doctrine-bridge:^1.0'
```

## Examples of using

The examples use [PHP-DI](http://php-di.org/)

### Doctrine Manager Registry

The DI definitions:

```php
declare(strict_types=1);

use Arus\Doctrine\Bridge\ManagerRegistry;
use Doctrine\Common\Cache\ArrayCache;

use function DI\autowire;
use function DI\create;
use function DI\env;
use function DI\get;
use function DI\string;

return [
    'doctrine' => autowire(ManagerRegistry::class),

    'doctrine.configuration' => [
        'default' => [
            'metadata' => [
                'sources' => get('doctrine.configuration.default.metadata.sources'),
            ],
            'connection' => get('doctrine.configuration.default.connection'),
            'proxyDir' => get('doctrine.configuration.default.proxyDir'),
            'cache' => get('doctrine.configuration.default.cache'),
        ],
    ],

    'doctrine.configuration.default.metadata.sources' => [
        string('{app.root}/src/Entity'),
    ],

    'doctrine.configuration.default.connection' => [
        'url' => env('DATABASE_URL', 'mysql://user:password@127.0.0.1:3306/acme'),
    ],

    'doctrine.configuration.default.proxyDir' => null,

    'doctrine.configuration.default.cache' => create(ArrayCache::class),
];
```

### Unique Entity Validator

The DI definitions:

```php
declare(strict_types=1);

use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

use function DI\factory;

return [
    'validator' => factory(function ($container) {
        return Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->setConstraintValidatorFactory(
                new ContainerConstraintValidatorFactory($container)
            )
        ->getValidator();
    }),
];
```

Usage example:

```php
declare(strict_types=1);

namespace App\Entity;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\Validator\Constraint\UniqueEntity;

/**
 * @UniqueEntity({"foo"})
 * 
 * @UniqueEntity({"bar", "baz"})
 * 
 * @UniqueEntity({"qux"}, atPath="customPropertyPath")
 * 
 * @UniqueEntity({"quux"}, message="The value {{ value }} already exists!")
 */
final class Entry
{
    // some code...
}
```
