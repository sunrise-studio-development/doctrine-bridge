# Doctrine Bridge

[🇬🇧 English version](README.md) | 🇷🇺 **Русская версия**

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/doctrine-bridge/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Doctrine Bridge предоставляет **production-ready** интеграцию [Doctrine ORM](https://github.com/doctrine/orm) для проектов вне Symfony: создаёт и хранит именованные `EntityManager`, помогает управлять их состоянием в долгоживущих процессах и добавляет готовые интеграции для репозиториев, HTTP-запросов, гидрации и валидации. Пакет настраивается напрямую через PHP-код, а для проектов на [php-di/php-di](https://github.com/PHP-DI/PHP-DI) доступны готовые определения контейнера.

## Установка

```bash
composer require sunrise-studio/doctrine-bridge
```

Для production рекомендуется передавать отдельные PSR-6 пулы кеша для метаданных, запросов и результатов. Готовые реализации предоставляет [symfony/cache](https://github.com/symfony/cache):

```bash
composer require symfony/cache
```

## Быстрый старт

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

`EntityManagerParameters` описывает один именованный менеджер. `EntityManagerFactory` создаёт его, а `EntityManagerRegistry` лениво получает и хранит менеджеры по именам.

DSN обрабатывается [doctrine/dbal](https://github.com/doctrine/dbal). Поддерживаемые параметры подключения описаны в его документации.

## PHP-DI

Для проектов на [php-di/php-di](https://github.com/PHP-DI/PHP-DI) пакет содержит готовые определения. Основной файл создаёт менеджер `default`, читает DSN из `DATABASE_DSN` и ожидает сервис `CacheItemPoolInterface`:

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

Общие значения задаются через `doctrine.entity_manager_parameters.*.*`, значения менеджера по умолчанию — через `doctrine.entity_manager_parameters.default.*`. Для нескольких менеджеров переопределите `doctrine.entity_manager_parameters_list`.

## Поведение конфигурации

Большинство параметров напрямую соответствуют настройкам [doctrine/orm](https://github.com/doctrine/orm). Важная семантика пакета:

- `logger` добавляет DBAL middleware логирования после пользовательских `middlewares`;
- все `schemaAssetFilters` должны вернуть `true`, чтобы объект схемы оставался видимым;
- `configurators` изменяют `Configuration` до создания менеджера;
- после создания менеджера включаются `enabledFilters`, регистрируются `eventSubscribers`, затем выполняются `entityManagerConfigurators`;
- на PHP 8.4 и выше используются native lazy objects; настройки proxy-классов применяются только на более ранних версиях PHP.

## Несколько менеджеров

Имя менеджера реализует `EntityManagerNameInterface`. Для имён удобно использовать enum приложения.

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

Передайте по одному `EntityManagerParameters` для каждого соединения и обращайтесь к менеджеру по имени:

```php
$readEntityManager = $registry->getEntityManager(EntityManagerName::Read);
$writeEntityManager = $registry->getEntityManager(EntityManagerName::Write);
```

Вызов без имени возвращает менеджер по умолчанию, заданный при создании registry.

`$registry->hasEntityManager(EntityManagerName::Read)` возвращает `true` только для уже созданного менеджера. Метод не проверяет наличие конфигурации и не создаёт менеджер.

## Репозитории как сервисы

`AbstractEntityRepository` позволяет оформлять репозитории как сервисы приложения. Такой репозиторий:

- получает собственные зависимости через контейнер;
- не хранит конкретный `EntityManager`.

Каждая операция получает актуальный менеджер через `EntityManagerRegistryInterface`:

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

Унаследованный конструктор принимает registry и необязательное имя менеджера. При необходимости репозиторий может объявить собственный конструктор, принять дополнительные зависимости и передать registry в `parent::__construct()`.

## Долгоживущие процессы

`EntityManager` хранит Unit of Work и identity map. В worker или application server это состояние нельзя переносить между запросами или задачами.

В долгоживущих сервисах:

- внедряйте `EntityManagerRegistryInterface`, а не конкретный `EntityManagerInterface`;
- используйте `AbstractEntityRepository` для repository-сервисов;
- вызывайте `clear()` после каждой единицы работы;
- управляйте транзакциями в коде приложения.

При повторном обращении registry заменяет закрытый менеджер. Если уже открытое соединение не проходит проверочный запрос платформы, registry закрывает его, чтобы DBAL переподключился при следующем использовании.

Registry не очищает Unit of Work автоматически и не управляет транзакциями.

## Интеграции

### `sunrise/http-router`

#### Жизненный цикл Doctrine в HTTP-запросе

Интеграция с [sunrise/http-router](https://github.com/sunrise-php/http-router) предоставляет [`RequestTerminationMiddleware`](src/Integration/Router/Middleware/RequestTerminationMiddleware.php), который завершает работу Doctrine после обработки HTTP-запроса.

Middleware:

- вызывает `flush()` только после успешного завершения обработчика;
- всегда вызывает `clear()` в `finally`;
- работает только с менеджерами, которые уже были созданы;
- не начинает, не подтверждает и не откатывает транзакции;
- преобразует `EntityValidationFailedException` в `HttpException` роутера с ошибками валидации.

Определение: [`resources/definitions/integration/router/middlewares/request_termination_middleware.php`](resources/definitions/integration/router/middlewares/request_termination_middleware.php)

По умолчанию менеджер `default` включён и в список для `flush()`, и в список для `clear()`. Списки настраиваются через:

- `router.request_termination_middleware.flushable_entity_manager_names`;
- `router.request_termination_middleware.clearable_entity_manager_names`.

Статус и сообщение HTTP-ошибки валидации также можно переопределить в PHP-DI.

#### Преобразование атрибута маршрута в сущность

`#[RequestedEntity]` загружает сущность для параметра обработчика:

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

По умолчанию resolver использует единственный идентификатор сущности и атрибут маршрута с тем же именем. `field` меняет поле поиска, `variable` — имя атрибута маршрута, `criteria` добавляет условия, `em` выбирает менеджер.

Если сущность не найдена, обязательный параметр приводит к HTTP 404, nullable-параметр получает `null`.

Определение: [`resources/definitions/integration/router/parameter_resolvers/requested_entity_parameter_resolver.php`](resources/definitions/integration/router/parameter_resolvers/requested_entity_parameter_resolver.php)

### `sunrise/hydrator`

#### Преобразование значения в сущность

Интеграция с [sunrise/hydrator](https://github.com/sunrise-php/hydrator) позволяет преобразовать входное значение свойства в сущность через `#[MapEntity]`:

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

По умолчанию поиск выполняется по единственному идентификатору сущности. `field` задаёт другое поле, `criteria` — дополнительные условия, `em` — менеджер. `#[ValueType]` сначала приводит входное значение через Hydrator.

Определение: [`resources/definitions/integration/hydrator/type_converters/map_entity_type_converter.php`](resources/definitions/integration/hydrator/type_converters/map_entity_type_converter.php)

#### OpenAPI-схема для `#[MapEntity]`

OpenAPI resolver из Doctrine Bridge строит схему свойства с `#[MapEntity]` по типу из `#[ValueType]`, а не по классу сущности.

Определение: [`resources/definitions/integration/router/openapi_php_type_schema_resolvers/map_entity_php_type_schema_resolver.php`](resources/definitions/integration/router/openapi_php_type_schema_resolvers/map_entity_php_type_schema_resolver.php)

### `symfony/validator`

#### Валидация сущностей перед сохранением

[`EntityValidationOnPreSave`](src/EventSubscriber/EntityValidationOnPreSave.php) запускает [symfony/validator](https://github.com/symfony/validator) на событиях Doctrine `prePersist` и `preUpdate`. При наличии нарушений выбрасывается `EntityValidationFailedException`.

Подписчик не заменяет ограничения базы данных и не управляет транзакциями.

#### Проверка уникальности

`#[UniqueEntity]` размещается на классе и удобен для составной уникальности или когда все ограничения сущности нужно видеть в одном месте:

```php
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntity;

#[UniqueEntity(fields: ['tenant', 'email'], errorPath: 'email')]
final class User
{
    // ...
}
```

`#[UniqueValue]` размещается на свойстве и проверяет одно поле. На свойстве сущности класс сущности и поле определяются автоматически из контекста:

```php
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;

final class User
{
    #[UniqueValue]
    public string $email;
}
```

В DTO обычно указывают `entity`; `field` нужен только если имя свойства не совпадает с полем сущности:

```php
use App\Entity\User;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueValue;

final class UserSignUpRequest
{
    #[UniqueValue(entity: User::class)]
    public string $email;
}
```

Оба валидатора не считают текущую сущность конфликтом, если найденная строка имеет тот же идентификатор Doctrine. `UniqueEntity` прекращает проверку, если одно из выбранных значений равно `null`, что соответствует поведению SQL unique constraints с `NULL` в [PostgreSQL](https://www.postgresql.org/docs/current/ddl-constraints.html#DDL-CONSTRAINTS-UNIQUE-CONSTRAINTS). `UniqueValue` игнорирует `null` и пустую строку.

Проверки выполняются на уровне приложения. Для защиты от конкурентных записей всё равно нужны уникальные ограничения базы данных.

Определения:

- [`resources/definitions/event_subscribers/entity_validation_on_pre_save.php`](resources/definitions/event_subscribers/entity_validation_on_pre_save.php)
- [`resources/definitions/integration/validator/unique_entity_validator.php`](resources/definitions/integration/validator/unique_entity_validator.php)
- [`resources/definitions/integration/validator/unique_value_validator.php`](resources/definitions/integration/validator/unique_value_validator.php)

### `sunrise/translator`

Сообщения ошибок пакета определены в [`ErrorMessage`](src/Dictionary/ErrorMessage.php). Интеграция с [sunrise/translator](https://github.com/sunrise-php/translator) добавляет их переводы.

Определение: [`resources/definitions/translators.php`](resources/definitions/translators.php)
