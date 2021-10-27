# Doctrine Bridge for PHP 7.2+ (incl. PHP 8)

[![Build Status](https://circleci.com/gh/sunrise-php/doctrine-bridge.svg?style=shield)](https://circleci.com/gh/sunrise-php/doctrine-bridge)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-php/doctrine-bridge/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-php/doctrine-bridge/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-php/doctrine-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-php/doctrine-bridge/?branch=master)
[![Total Downloads](https://poser.pugx.org/sunrise/doctrine-bridge/downloads)](https://packagist.org/packages/sunrise/doctrine-bridge)
[![Latest Stable Version](https://poser.pugx.org/sunrise/doctrine-bridge/v/stable)](https://packagist.org/packages/sunrise/doctrine-bridge)
[![License](https://poser.pugx.org/sunrise/doctrine-bridge/license)](https://packagist.org/packages/sunrise/doctrine-bridge)

---

## Installation

```bash
composer require 'sunrise/doctrine-bridge:^2.0'
```

## Entity Manager Registry

```php
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;

// Minimal configuration (see below for details)
$configuration = [
    'master' => [
        'dbal' => [
            'connection' => [
                'url' => 'sqlite:///{app.root}/master.sqlite',
            ],
        ],
        'orm' => [
            'entity_locations' => [
                '{app.root}/Entity',
            ],
            'metadata_driver' => 'annotations',
            'proxy_dir' => '{app.root}/var/cache/doctrine',
        ],
        'migrations' => [
            'migrations_paths' => [
                'App\Migrations' => '{app.root}/resources/migrations',
            ],
        ],
        'types' => [
        ],
    ],
];

$doctrine = new EntityManagerRegistry($configuration, $registryName = 'ORM');
```

### Configuration

#### Minimal configuration

```php

```

#### DBAL configuration

| **Key**              | **Required** | **Data type**                           | **Default value** | **Description** |
|----------------------|--------------|-----------------------------------------|-------------------|-----------------|
| connection           | Yes          | array                                   | true              |                 |
| auto_commit          | No           | bool                                    |                   |                 |
| event_manager        | No           | \Doctrine\Common\EventManager           |                   |                 |
| middlewares          | No           | array<\Doctrine\DBAL\Driver\Middleware> |                   |                 |
| result_cache         | No           | \Psr\Cache\CacheItemPoolInterface       |                   |                 |
| schema_assets_filter | No           | callable                                |                   |                 |
| sql_logger           | No           | \Doctrine\DBAL\Logging\SQLLogger        |                   |                 |

#### ORM configuration

| **Key**                          | **Required** | **Data type**                                              | **Default value**                           | **Description** |
|----------------------------------|--------------|------------------------------------------------------------|---------------------------------------------|-----------------|
| entity_locations                 | Yes          | array                                                      |                                             |                 |
| metadata_driver                  | Yes          | string\|\Doctrine\Persistence\Mapping\Driver\MappingDriver | PHP 7: "annotations"; PHP 8: "attributes"   |                 |
| proxy_dir                        | Yes          | string                                                     |                                             |                 |
| class_metadata_factory_name      |              | class-string                                               |                                             |                 |
| custom_datetime_functions        |              | array                                                      |                                             |                 |
| custom_hydration_modes           |              | array                                                      |                                             |                 |
| custom_numeric_functions         |              | array                                                      |                                             |                 |
| custom_string_functions          |              | array                                                      |                                             |                 |
| default_query_hints              |              | array                                                      |                                             |                 |
| default_repository_class_name    |              | class-string                                               |                                             |                 |
| entity_listener_resolver         |              | \Doctrine\ORM\Mapping\EntityListenerResolver               |                                             |                 |
| entity_namespaces                |              | array                                                      |                                             |                 |
| naming_strategy                  |              | \Doctrine\ORM\Mapping\NamingStrategy                       |                                             |                 |
| proxy_auto_generate              |              | bool                                                       | true                                        |                 |
| proxy_namespace                  |              | string                                                     |                                             |                 |
| quote_strategy                   |              | \Doctrine\ORM\Mapping\QuoteStrategy                        |                                             |                 |
| repository_factory               |              | \Doctrine\ORM\Repository\RepositoryFactory                 |                                             |                 |
| second_level_cache_configuration |              | \Doctrine\ORM\Cache\CacheConfiguration                     |                                             |                 |
| second_level_cache_enabled       |              | bool                                                       |                                             |                 |
| hydration_cache                  |              | \Psr\Cache\CacheItemPoolInterface                          |                                             |                 |
| metadata_cache                   |              | \Psr\Cache\CacheItemPoolInterface                          |                                             |                 |
| query_cache                      |              | \Psr\Cache\CacheItemPoolInterface                          |                                             |                 |
| result_cache                     |              | \Psr\Cache\CacheItemPoolInterface                          |                                             |                 |

#### Migrations configuration

More details at: [Official Documentation](https://www.doctrine-project.org/projects/doctrine-migrations/en/3.2/reference/configuration.html)

## Hydrator

The hydrator NEVER affects properties, it only calls setters and adders.

```php
use Doctrine\ORM\Mapping as ORM;
use Sunrise\Bridge\Doctrine\Annotation\Unhydrable;

#[Entity]
class Post {

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $name;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    private $category;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private $tags;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Unhydrable]
    private $updatedBy

    // set + name (field name)
    public function setName(string $value) {
        // some code
    }

    // set + category (field name)
    public function setCategory(Category $value) {
        // some code...
    }

    // add + tag (singular field name)
    public function addTag(Tag $tag) {
        // some code...
    }

    // the setter will not be called because its property was marked as unhydrable
    public function setUpdatedBy(User $user) {
        // some code
    }
}
```

```php
$data = [
    'name' => 'New post',
    'category' => [
        'name' => 'New category'
    ],
    'tags' => [
        [
            'name' => 'New tag 1',
        ],
        [
            'name' => 'New tag 2',
        ],
    ],
];

// or

$data = [
    'name' => 'New post',
    'category' => 1, // existing category ID
    'tags' => [1, 2], // existing tag IDs
];

$hydrator = $doctrine->getHydrator($managerName = null);

$someEntity = $hydrator->hydrate(Post::class, $data);
```

## Maintainer

```php
$maintainer = $doctrine->getMaintainer();

// closes all active connections
$maintainer->closeAllConnections();

// clears all managers
$maintainer->clearAllManagers();

// reopens all closed managers
$maintainer->reopenAllManagers();

// recreates all schemas
$maintainer->recreateAllSchemas();

// recreates the specified schema
$maintainer->recreateSchema($managerName = null);
```

## CLI commands

```php
$application->addCommands(
    $doctrine->getCommands()
);
```

## PSR-3 logger

```php
use Sunrise\Bridge\Doctrine\Logger\SqlLogger;

$sqlLogger = new SqlLogger($psrLogger);
```

## Unique Entity Validator

```php
use Sunrise\Bridge\Doctrine\Validator\Constraint\UniqueEntity;

#[UniqueEntity(['field'])]
class SomeEntity {
}
```

## CLI

```php
declare(strict_types=1);

use Symfony\Component\Console\Application;

require __DIR__ . '/../config/bootstrap.php';

$container = require __DIR__ . '/../config/container.php';

$application = new Application(
    $container->get('app.name'),
    $container->get('app.version')
);

$application->addCommands(
    $container->get('commands')
);

$application->addCommands(
    $container->get('doctrine')->getCommands()
);

$application->run();
```

## PHP-DI definitions

### Validator

```php
declare(strict_types=1);

use DI\Container;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

return [

    /**
     * The application validator
     *
     * @link https://symfony.com/doc/current/validation.html
     *
     * @var ValidatorInterface
     */
    ValidatorInterface::class => function (Container $container) : ValidatorInterface {
        return Validation::createValidatorBuilder()
            ->enableAnnotationMapping(true)
            ->addDefaultDoctrineAnnotationReader()
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($container))
            ->getValidator();
    },
];
```
