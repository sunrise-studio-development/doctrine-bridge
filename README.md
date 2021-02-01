## Doctrine Bridge for PHP 7.2+ (incl. PHP 8) based on PHP-DI

[![Build Status](https://circleci.com/gh/autorusltd/doctrine-bridge.svg?style=shield)](https://circleci.com/gh/autorusltd/doctrine-bridge)
[![Code Coverage](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/autorusltd/doctrine-bridge/?branch=master)
[![Total Downloads](https://poser.pugx.org/arus/doctrine-bridge/downloads)](https://packagist.org/packages/arus/doctrine-bridge)
[![Latest Stable Version](https://poser.pugx.org/arus/doctrine-bridge/v/stable)](https://packagist.org/packages/arus/doctrine-bridge)
[![License](https://poser.pugx.org/arus/doctrine-bridge/license)](https://packagist.org/packages/arus/doctrine-bridge)

---

## Installation

```bash
composer require 'arus/doctrine-bridge:^1.10'
```

## Examples of using

The examples use [PHP-DI](http://php-di.org/)

### Doctrine Manager Registry

##### The DI definitions

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
            'connection' => get('doctrine.configuration.default.connection'),
            'metadata_sources' => get('doctrine.configuration.default.metadata_sources'),
            'metadata_cache' => get('doctrine.configuration.default.metadata_cache'),
            'query_cache' => get('doctrine.configuration.default.query_cache'),
            'result_cache' => get('doctrine.configuration.default.result_cache'),
            'proxy_dir' => get('doctrine.configuration.default.proxy_dir'),
            'proxy_namespace' => get('doctrine.configuration.default.proxy_namespace'),
            'proxy_auto_generate' => get('doctrine.configuration.default.proxy_auto_generate'),
            'sql_logger' => get('doctrine.configuration.default.sql_logger'),
        ],
    ],

    'doctrine.configuration.default.connection' => [
        'url' => env('DATABASE_URL', 'mysql://user:password@127.0.0.1:3306/acme'),
    ],

    'doctrine.configuration.default.metadata_sources' => [string('{app.root}/src/Entity')],
    'doctrine.configuration.default.metadata_cache' => get('doctrine.configuration.default.default_cache'),
    'doctrine.configuration.default.query_cache' => get('doctrine.configuration.default.default_cache'),
    'doctrine.configuration.default.result_cache' => get('doctrine.configuration.default.default_cache'),
    'doctrine.configuration.default.default_cache' => create(ArrayCache::class),
    'doctrine.configuration.default.proxy_dir' => string('{app.root}/database/proxies'),
    'doctrine.configuration.default.proxy_namespace' => 'DoctrineProxies',
    'doctrine.configuration.default.proxy_auto_generate' => true,
    'doctrine.configuration.default.sql_logger' => null,
];
```

### Doctrine Migrations

##### The DI definitions

```php
declare(strict_types=1);

use function DI\get;
use function DI\string;

return [
    'migrations.configuration' => [
        'name' => get('migrations.configuration.name'),
        'table_name' => get('migrations.configuration.table_name'),
        'column_name' => get('migrations.configuration.column_name'),
        'column_length' => get('migrations.configuration.column_length'),
        'executed_at_column_name' => get('migrations.configuration.executed_at_column_name'),
        'directory' => get('migrations.configuration.directory'),
        'namespace' => get('migrations.configuration.namespace'),
        'organize_by_year' => get('migrations.configuration.organize_by_year'),
        'organize_by_year_and_month' => get('migrations.configuration.organize_by_year_and_month'),
        'custom_template' => get('migrations.configuration.custom_template'),
        'is_dry_run' => get('migrations.configuration.is_dry_run'),
        'all_or_nothing' => get('migrations.configuration.all_or_nothing'),
        'check_database_platform' => get('migrations.configuration.check_database_platform'),
    ],

    'migrations.configuration.name' => null,
    'migrations.configuration.table_name' => null,
    'migrations.configuration.column_name' => null,
    'migrations.configuration.column_length' => null,
    'migrations.configuration.executed_at_column_name' => null,
    'migrations.configuration.directory' => string('{app.root}/database/migrations'),
    'migrations.configuration.namespace' => 'DoctrineMigrations',
    'migrations.configuration.organize_by_year' => null,
    'migrations.configuration.organize_by_year_and_month' => null,
    'migrations.configuration.custom_template' => null,
    'migrations.configuration.is_dry_run' => null,
    'migrations.configuration.all_or_nothing' => null,
    'migrations.configuration.check_database_platform' => null,
];
```

### Doctrine Commands Provider

##### The DI definitions

```php
declare(strict_types=1);

use Arus\Doctrine\Bridge\CommandsProvider;

use function DI\decorate;

return [
    'commands' => decorate(function ($previous, $container) {
        $provider = new CommandsProvider($container);

        return array_merge($previous, $provider->getCommands());
    }),
];
```

##### or you can get all the commands through the manager

```php
$application->addCommands(
    $container->get('doctrine')->getCommands()
);
```

### Unique Entity Validator

##### The DI definitions

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

##### Usage example

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
class Entry
{
    // some code...
}
```

### Doctrine Array Hydrator

```php
$hydrator = $container->get('doctrine')->getHydrator();

$hydrator->hydrate(Entity::class, [
    'name' => 'foo bar',
]);
```

* https://github.com/pmill/doctrine-array-hydrator
