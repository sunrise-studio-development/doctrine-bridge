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

### Quick start

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

## Query Filter

```php
use App\Doctrine\QueryFilter;

// initializes the filter with the specified data,
// which can be, for example, the request query parameters.
$filter = new QueryFilter([
    // some user data...
]);

// ['disabled' => 'yes']
// WHERE post.isDisabled = :p0 (true)
// More details at: https://github.com/php/php-src/blob/b7d90f09d4a1688f2692f2fa9067d0a07f78cc7d/ext/filter/logical_filters.c#L273
$filter->allowFilterBy('disabled', 'post.isDisabled', $filter::TYPE_BOOL);

// ['hits' => '100']
// WHERE post.hits = :p0 (100)
$filter->allowFilterBy('hits', 'post.hits', $filter::TYPE_NUM);
// ['hits' => [min => '100']]
// WHERE post.hits >= :p0 (100)
$filter->allowFilterBy('hits', 'post.hits', $filter::TYPE_NUM);
// ['hits' => [max => '100']]
// WHERE post.hits <= :p0 (100)
$filter->allowFilterBy('hits', 'post.hits', $filter::TYPE_NUM);
// ['hits' => [min => '50', max => '150']]
// WHERE post.hits >= :p0 (50) AND post.hits <= :p1 (150)
$filter->allowFilterBy('hits', 'post.hits', $filter::TYPE_NUM);

// ['name' => 'Hello']
// WHERE post.id = :p0 ("Hello")
$filter->allowFilterBy('name', 'post.name');
// ['name' => 'Hello']
// WHERE post.id LIKE :p0 ("%Hello")
$filter->allowFilterBy('name', 'post.name', $filter::TYPE_STR, $filter::MODE_LIKE|$filter::STARTS_WITH);
// ['name' => 'Hello']
// WHERE post.id LIKE :p0 ("Hello%")
$filter->allowFilterBy('name', 'post.name', $filter::TYPE_STR, $filter::MODE_LIKE|$filter::ENDS_WITH);
// ['name' => 'Hello']
// WHERE post.id LIKE :p0 ("%Hello%")
$filter->allowFilterBy('name', 'post.name', $filter::TYPE_STR, $filter::MODE_LIKE|$filter::CONTAINS);
// ['name' => 'Hello*Something%Something']
// WHERE post.id LIKE :p0 ("%Hello%Something\%Something%")
// Note that asterisks will be converted to percentages...
$filter->allowFilterBy('name', 'post.name', $filter::TYPE_STR, $filter::MODE_LIKE|$filter::CONTAINS|$filter::WILDCARDS);

// ['created' => '2004-01-10']
// WHERE post.createdAt = :p0 (2004-01-10)
$filter->allowFilterBy('created', 'post.createdAt', $filter::TYPE_DATE);
// ['created' => [from => '1970-01-01']]
// WHERE post.createdAt >= :p0 (1970-01-01)
$filter->allowFilterBy('created', 'post.createdAt', $filter::TYPE_DATE);
// ['created' => [until => '2038-01-19']]
// WHERE post.createdAt <= :p0 (2038-01-19)
$filter->allowFilterBy('created', 'post.createdAt', $filter::TYPE_DATE);
// ['created' => [from => '1970-01-01', until => '2038-01-19']]
// WHERE post.createdAt >= :p0 (1970-01-01) AND post.createdAt <= :p1 (2038-01-19)
$filter->allowFilterBy('created', 'post.createdAt', $filter::TYPE_DATE);
// Note that the DATE type also work with time and can accept a timestamp.
// ['created' => '1073741824'] // work with the timestamp...
// WHERE post.createdAt = :p0 (2004-01-10)
$filter->allowFilterBy('created', 'post.createdAt', $filter::TYPE_DATE);
// Note that the DATE type also work with time and can accept a timestamp.
// ['created' => '12:00'] // work with the time...
// WHERE post.createdAt = :p0 (12:00)
$filter->allowFilterBy('opens', 'post.opensAt', $filter::TYPE_DATE);

// ['sort' => 'name']
// ORDER BY post.name ASC
$filter->allowSortBy('name', 'post.name' /* default is ascending direction */);
// ['sort' => 'created']
// ORDER BY post.createdAt DESC
$filter->allowSortBy('created', 'post.createdAt', $filter::SORT_DESC /* specified default sort direction */);
// ['sort' => ['name' => 'asc', 'created' => desc]]
// ORDER BY post.name ASC, post.createdAt DESC
$filter->allowSortBy('name', 'post.name' /* default is ascending direction */);
$filter->allowSortBy('created', 'post.createdAt', $filter::SORT_DESC /* specified default sort direction */);

// If an user data doesn't contain sort fields,
// then will be applied the default sort logic.
$filter->defaultSortBy('post.name', $filter::SORT_ASC);
$filter->defaultSortBy('post.createdAt', $filter::SORT_DESC);

// Sets the default limit:
$filter->defaultLimit(100);
// Sets the maximum limit value:
$filter->maxLimit(100);

// For rows limiting, you can pass the following:
// ['limit' => 100]
// ['offset' => 0]
// ... or:
// ['page' => 1]
// ['pagesize' => 100]

// Create your QueryBuilder instance...
$qb = $this->createQueryBuilder('post');
// ... and apply the filter to it:
$filter->apply($qb);
// ... and now you can run your query!
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

## Example for your CLI application

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

## PHP-DI definition examples

### Doctrine

```php
declare(strict_types=1);

use App\Bundle\Security\Password\Type\PasswordType;
use Doctrine\Persistence\ManagerRegistry;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Sunrise\Bridge\Doctrine\Logger\SqlLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function DI\create;
use function DI\env;
use function DI\get;
use function DI\string;

return [
    'doctrine' => create(EntityManagerRegistry::class)
        ->constructor(
            get('doctrine.configuration'),
        ),

    'doctrine.configuration' => [
        'master' => [
            'dbal' => [
                'connection' => get('doctrine.configuration.master.dbal.connection'),
                'sql_logger' => get('doctrine.configuration.master.dbal.sql_logger'),
            ],
            'orm' => [
                'entity_locations' => get('doctrine.configuration.master.orm.entity_locations'),
                'entity_namespaces' => get('doctrine.configuration.master.orm.entity_namespaces'),
                'metadata_driver' => get('doctrine.configuration.master.orm.metadata_driver'),
                'metadata_cache' => get('doctrine.configuration.master.orm.metadata_cache'),
                'query_cache' => get('doctrine.configuration.master.orm.query_cache'),
                'result_cache' => get('doctrine.configuration.master.orm.result_cache'),
                'proxy_dir' => get('doctrine.configuration.master.orm.proxy_dir'),
                'proxy_auto_generate' => get('doctrine.configuration.master.orm.proxy_auto_generate'),
            ],
            'migrations' => [
                'logger' => get('doctrine.configuration.default.migrations.logger'),
                'migrations_paths' => get('doctrine.configuration.default.migrations.migrations_paths'),
            ],
            'types' => get('doctrine.configuration.types'),
        ],
    ],

    'doctrine.configuration.master.dbal.connection' => ['url' => env('DATABASE_MASTER_URL')],
    'doctrine.configuration.master.dbal.sql_logger' => get('doctrine.configuration.default.dbal.sql_logger'),
    'doctrine.configuration.master.orm.entity_locations' => get('doctrine.configuration.default.orm.entity_locations'),
    'doctrine.configuration.master.orm.entity_namespaces' => get('doctrine.configuration.default.orm.entity_namespaces'),
    'doctrine.configuration.master.orm.metadata_driver' => get('doctrine.configuration.default.orm.metadata_driver'),
    'doctrine.configuration.master.orm.metadata_cache' => get('doctrine.configuration.default.orm.metadata_cache'),
    'doctrine.configuration.master.orm.query_cache' => get('doctrine.configuration.default.orm.query_cache'),
    'doctrine.configuration.master.orm.result_cache' => get('doctrine.configuration.default.orm.result_cache'),
    'doctrine.configuration.master.orm.proxy_dir' => get('doctrine.configuration.default.orm.proxy_dir'),
    'doctrine.configuration.master.orm.proxy_auto_generate' => get('doctrine.configuration.default.orm.proxy_auto_generate'),

    'doctrine.configuration.default.dbal.sql_logger' => create(SqlLogger::class)->constructor(get('logger')),
    'doctrine.configuration.default.orm.entity_locations' => [string('{app.root}/src/Entity')],
    'doctrine.configuration.default.orm.entity_namespaces' => ['App' => 'App\Entity'],
    'doctrine.configuration.default.orm.metadata_driver' => 'annotations',
    'doctrine.configuration.default.orm.metadata_cache' => create(ArrayAdapter::class),
    'doctrine.configuration.default.orm.query_cache' => create(ArrayAdapter::class),
    'doctrine.configuration.default.orm.result_cache' => create(ArrayAdapter::class),
    'doctrine.configuration.default.orm.proxy_dir' => string('{app.root}/var/cache/doctrine/proxies'),
    'doctrine.configuration.default.orm.proxy_auto_generate' => true,

    'doctrine.configuration.default.migrations.logger' => get('logger'),
    'doctrine.configuration.default.migrations.migrations_paths' => [
        'App\Migrations' => string('{app.root}/resources/migrations'),
    ],

    'doctrine.configuration.types' => [
        PasswordType::NAME => PasswordType::class,
    ],

    // autowiring...
    ManagerRegistry::class => get('doctrine'),
    EntityManagerRegistry::class => get('doctrine'),
];
```

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
