# Doctrine Bridge

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

🇬🇧 **English version** | [🇷🇺 Русская версия](README-ru.md)

---

Doctrine Bridge provides a **production-ready** [Doctrine ORM](https://github.com/doctrine/orm) integration for projects outside Symfony: it creates and stores named `EntityManager` instances, helps manage their state in long-running processes, and provides integrations for repositories, HTTP requests, hydration, and validation. The package is configured directly with PHP code, and ready-made container definitions are available for projects using [php-di/php-di](https://github.com/PHP-DI/PHP-DI).

## Installation

```bash
composer require sunrise-studio/doctrine-bridge
```

For production, pass separate PSR-6 cache pools for metadata, queries, and results. [symfony/cache](https://github.com/symfony/cache) provides ready-to-use implementations:

```bash
composer require symfony/cache
```

## Quick Start

```php
<?php

declare(strict_types=1);

use App\Entity\User;
use Sunrise\Bridge\Doctrine\Dictionary\EntityManagerName;
use Sunrise\Bridge\Doctrine\EntityManagerFactory;
use Sunrise\Bridge\Doctrine\EntityManagerParameters;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;

$registry = new EntityManagerRegistry(
    entityManagerFactory: new EntityManagerFactory(),
    entityManagerParametersList: [
        new EntityManagerParameters(
            name: EntityManagerName::Default,
            dsn: $_ENV['DATABASE_DSN'],
            entityDirectories: [__DIR__ . '/src/Entity'],
        ),
    ],
    defaultEntityManagerName: EntityManagerName::Default,
);

$user = $registry->getEntityManager()->find(User::class, 1);
```

`EntityManagerParameters` describes one named manager. `EntityManagerFactory` creates it, and `EntityManagerRegistry` lazily resolves and stores managers by name.

The DSN is handled by [doctrine/dbal](https://github.com/doctrine/dbal). Supported connection parameters are described in its documentation.

## PHP-DI

For projects using [php-di/php-di](https://github.com/PHP-DI/PHP-DI), the package provides ready-made definitions. The main definition file creates the `default` manager, reads the DSN from `DATABASE_DSN`, and expects a `CacheItemPoolInterface` service:

```php
<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function DI\create;

$root = __DIR__ . '/..';

$builder = new ContainerBuilder();
$builder->addDefinitions(
    $root . '/vendor/sunrise-studio/doctrine-bridge/resources/definitions/doctrine.php',
    [
        CacheItemPoolInterface::class => create(ArrayAdapter::class),
        'doctrine.entity_manager_parameters.*.entity_directories' => [
            $root . '/src/Entity',
        ],
    ],
);
```

Common values are configured through `doctrine.entity_manager_parameters.*.*`; values for the default manager are configured through `doctrine.entity_manager_parameters.default.*`. To configure several managers, override `doctrine.entity_manager_parameters_list`.

## Configuration Behavior

Most parameters map directly to [doctrine/orm](https://github.com/doctrine/orm) settings. Package-specific behavior:

- `logger` appends DBAL logging middleware after user-defined `middlewares`;
- every `schemaAssetFilters` callback must return `true` for a schema asset to remain visible;
- `configurators` change `Configuration` before the manager is created;
- after the manager is created, `enabledFilters` are enabled, `eventSubscribers` are registered, and then `entityManagerConfigurators` are executed;
- on PHP 8.4 and newer, native lazy objects are used; proxy class settings apply only to earlier PHP versions.

## Multiple Managers

Manager names implement `EntityManagerNameInterface`. An application enum is a convenient choice.

```php
enum EntityManagerName: string implements EntityManagerNameInterface
{
    case Read = 'read';
    case Write = 'write';

    public function getValue(): string
    {
        return $this->value;
    }
}
```

Pass one `EntityManagerParameters` object for each connection and request managers by name:

```php
$readEntityManager = $registry->getEntityManager(EntityManagerName::Read);
$writeEntityManager = $registry->getEntityManager(EntityManagerName::Write);
```

A call without a name returns the default manager configured when the registry was created.

`$registry->hasEntityManager(EntityManagerName::Read)` returns `true` only for an already created manager. The method does not check whether the name is configured and does not create a manager.

## Repositories as Services

`AbstractEntityRepository` lets repositories be regular application services. Such a repository:

- receives its own dependencies through the container;
- does not store a concrete `EntityManager`.

Each operation resolves the current manager through `EntityManagerRegistryInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Sunrise\Bridge\Doctrine\AbstractEntityRepository;

/**
 * @extends AbstractEntityRepository<User>
 */
final class UserRepository extends AbstractEntityRepository
{
    public function getClassName(): string
    {
        return User::class;
    }

    public function findOneActiveByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => $email,
            'active' => true,
        ]);
    }
}
```

The inherited constructor accepts the registry and an optional manager name. If needed, the repository can declare its own constructor, accept additional dependencies, and pass the registry to `parent::__construct()`.

## Long-Running Processes

`EntityManager` stores the Unit of Work and identity map. In a worker or application server, this state must not leak between requests or jobs.

In long-running services:

- inject `EntityManagerRegistryInterface`, not a concrete `EntityManagerInterface`;
- use `AbstractEntityRepository` for repository services;
- call `clear()` after each unit of work;
- manage transactions in application code.

On subsequent access, the registry replaces a closed manager. If an already open connection fails the platform health query, the registry closes it so DBAL can reconnect on the next use.

The registry does not clear the Unit of Work automatically and does not manage transactions.

## Integrations

### `sunrise/http-router`

#### Doctrine Lifecycle in an HTTP Request

The [sunrise/http-router](https://github.com/sunrise-php/http-router) integration provides [`RequestTerminationMiddleware`](src/Integration/Router/Middleware/RequestTerminationMiddleware.php), which finalizes Doctrine work after an HTTP request is handled.

Middleware:

- calls `flush()` only after the handler completes successfully;
- always calls `clear()` in `finally`;
- works only with managers that have already been created;
- does not start, commit, or roll back transactions;
- converts `EntityValidationFailedException` to the router's `HttpException` with validation errors.

Definition: [`resources/definitions/integration/router/middlewares/request_termination_middleware.php`](resources/definitions/integration/router/middlewares/request_termination_middleware.php)

By default, the `default` manager is included in both the `flush()` and `clear()` lists. Configure the lists with:

- `router.request_termination_middleware.flushable_entity_manager_names`;
- `router.request_termination_middleware.clearable_entity_manager_names`.

The validation HTTP error status and message can also be overridden in PHP-DI.

#### Route Attribute to Entity Conversion

`#[RequestedEntity]` loads an entity for a handler parameter:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use Sunrise\Bridge\Doctrine\Integration\Router\Annotation\RequestedEntity;
use Sunrise\Http\Router\Annotation\GetApiRoute;

final readonly class PostController
{
    #[GetApiRoute('getPostById', '/posts/{id}')]
    public function getById(#[RequestedEntity] Post $post): Post
    {
        return $post;
    }
}
```

By default, the resolver uses the entity's single identifier and the route attribute with the same name. `field` changes the lookup field, `variable` changes the route attribute name, `criteria` adds conditions, and `em` selects the manager.

If the entity is not found, a required parameter produces HTTP 404; a nullable parameter receives `null`.

Definition: [`resources/definitions/integration/router/parameter_resolvers/requested_entity_parameter_resolver.php`](resources/definitions/integration/router/parameter_resolvers/requested_entity_parameter_resolver.php)

### `sunrise/hydrator`

#### Value to Entity Conversion

The [sunrise/hydrator](https://github.com/sunrise-php/hydrator) integration converts an input property value to an entity through `#[MapEntity]`:

```php
<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\User;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\MapEntity;
use Sunrise\Bridge\Doctrine\Integration\Hydrator\Annotation\ValueType;

final readonly class CreateOrderRequest
{
    public function __construct(
        #[MapEntity]
        #[ValueType('int')]
        public User $user,
    ) {
    }
}
```

By default, lookup uses the entity's single identifier. `field` selects another lookup field, `criteria` adds conditions, and `em` selects the manager. `#[ValueType]` casts the input value through Hydrator first.

Definition: [`resources/definitions/integration/hydrator/type_converters/map_entity_type_converter.php`](resources/definitions/integration/hydrator/type_converters/map_entity_type_converter.php)

#### OpenAPI Schema for `#[MapEntity]`

The Doctrine Bridge OpenAPI resolver builds the schema for a property with `#[MapEntity]` from the `#[ValueType]` type, not from the entity class.

Definition: [`resources/definitions/integration/router/openapi_php_type_schema_resolvers/map_entity_php_type_schema_resolver.php`](resources/definitions/integration/router/openapi_php_type_schema_resolvers/map_entity_php_type_schema_resolver.php)

### `symfony/validator`

#### Entity Validation Before Saving

[`EntityValidationOnPreSave`](src/EventSubscriber/EntityValidationOnPreSave.php) runs [symfony/validator](https://github.com/symfony/validator) on Doctrine `prePersist` and `preUpdate` events. If violations are found, it throws `EntityValidationFailedException`.

The subscriber does not replace database constraints and does not manage transactions.

#### Uniqueness Validation

`#[UniqueEntity]` is placed on a class and is useful for composite uniqueness or when all entity-level constraints should be visible in one place:

```php
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntity;

#[UniqueEntity(fields: ['tenant', 'email'], errorPath: 'email')]
final class User
{
    // ...
}
```

`#[UniqueValue]` is placed on a property and validates one field. On an entity property, the entity class and field are detected from the validation context:

```php
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;

final class User
{
    #[UniqueValue]
    public string $email;
}
```

In DTOs, usually specify `entity`; `field` is needed only when the property name does not match the entity field:

```php
use App\Entity\User;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;

final class UserSignUpRequest
{
    #[UniqueValue(entity: User::class)]
    public string $email;
}
```

Both validators do not treat the current entity as a conflict when the found row has the same Doctrine identifier. `UniqueEntity` stops validation when one of the selected values is `null`, matching SQL unique constraint behavior with `NULL` in [PostgreSQL](https://www.postgresql.org/docs/current/ddl-constraints.html#DDL-CONSTRAINTS-UNIQUE-CONSTRAINTS). `UniqueValue` ignores `null` and an empty string.

Validation runs at application level. Unique database constraints are still required to protect against concurrent writes.

Definitions:

- [`resources/definitions/event_subscribers/entity_validation_on_pre_save.php`](resources/definitions/event_subscribers/entity_validation_on_pre_save.php)
- [`resources/definitions/integration/validator/unique_entity_validator.php`](resources/definitions/integration/validator/unique_entity_validator.php)
- [`resources/definitions/integration/validator/unique_value_validator.php`](resources/definitions/integration/validator/unique_value_validator.php)

### `sunrise/translator`

Package error messages are defined in [`ErrorMessage`](src/Dictionary/ErrorMessage.php). The [sunrise/translator](https://github.com/sunrise-php/translator) integration adds translations for them.

Definition: [`resources/definitions/translators.php`](resources/definitions/translators.php)
