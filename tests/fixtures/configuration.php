<?php declare(strict_types=1);

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Sunrise\Bridge\Doctrine\Logger\SqlLogger;

$configuration = [];

$configuration['logger'] = $this->createMock(LoggerInterface::class);

$configuration['doctrine.default']['cache'] = $this->createMock(Cache::class);
$configuration['doctrine.default']['logger'] = new SqlLogger($configuration['logger']);
$configuration['doctrine.default']['proxy_dir'] = sys_get_temp_dir();
$configuration['doctrine.default']['psrCache'] = $this->createMock(CacheItemPoolInterface::class);
// $configuration['doctrine.default']['middlewares'][] =  $this->createMock(Middleware::class);

// types of the foo instance
$configuration['doctrine']['foo']['types']['custom.boolean'] = BooleanType::class;
$configuration['doctrine']['foo']['types']['custom.integer'] = IntegerType::class;

// dbal configuration of the foo instance
$configuration['doctrine']['foo']['dbal']['auto_commit'] = false; // true is default
$configuration['doctrine']['foo']['dbal']['connection']['url'] = 'sqlite:///' . __DIR__ . '/../db/foo.sqlite';
// $configuration['doctrine']['foo']['dbal']['middlewares'] = $configuration['doctrine.default']['middlewares'];
$configuration['doctrine']['foo']['dbal']['result_cache'] = $configuration['doctrine.default']['cache'];
$configuration['doctrine']['foo']['dbal']['schema_assets_filter'] = function () {
};
$configuration['doctrine']['foo']['dbal']['sql_logger'] = $configuration['doctrine.default']['logger'];

// orm configuration of the foo instance
$configuration['doctrine']['foo']['orm']['class_metadata_factory_name'] = ClassMetadataFactory::class;
$configuration['doctrine']['foo']['orm']['custom_datetime_functions'] = null;
$configuration['doctrine']['foo']['orm']['custom_hydration_modes'] = null;
$configuration['doctrine']['foo']['orm']['custom_numeric_functions'] = null;
$configuration['doctrine']['foo']['orm']['custom_string_functions'] = null;
$configuration['doctrine']['foo']['orm']['default_query_hints'] = null;
$configuration['doctrine']['foo']['orm']['default_repository_class_name'] = null;
$configuration['doctrine']['foo']['orm']['entity_listener_resolver'] = null;
$configuration['doctrine']['foo']['orm']['entity_namespaces'] = null;
$configuration['doctrine']['foo']['orm']['hydration_cache'] = $configuration['doctrine.default']['psrCache'];
$configuration['doctrine']['foo']['orm']['metadata_cache'] = $configuration['doctrine.default']['psrCache'];
$configuration['doctrine']['foo']['orm']['metadata_sources'] = __DIR__ . '/Entity';
$configuration['doctrine']['foo']['orm']['naming_strategy'] = null;
$configuration['doctrine']['foo']['orm']['proxy_auto_generate'] = false;
$configuration['doctrine']['foo']['orm']['proxy_dir'] = $configuration['doctrine.default']['proxy_dir'];
$configuration['doctrine']['foo']['orm']['proxy_namespace'] = 'Foo\DoctrineProxies';
$configuration['doctrine']['foo']['orm']['query_cache'] = $configuration['doctrine.default']['psrCache'];
$configuration['doctrine']['foo']['orm']['quote_strategy'] = null;
$configuration['doctrine']['foo']['orm']['repository_factory'] = null;
$configuration['doctrine']['foo']['orm']['second_level_cache_configuration'] = null;
$configuration['doctrine']['foo']['orm']['second_level_cache_enabled'] = null;

// dbal configuration of the bar instance
$configuration['doctrine']['bar']['dbal']['connection']['url'] = 'sqlite:///' . __DIR__ . '/../db/foo.sqlite';

// orm configuration of the bar instance
$configuration['doctrine']['bar']['orm']['metadata_sources'] = __DIR__ . '/Entity';
$configuration['doctrine']['bar']['orm']['proxy_dir'] = $configuration['doctrine.default']['proxy_dir'];
$configuration['doctrine']['bar']['orm']['proxy_namespace'] = 'DoctrineProxies';

return $configuration;
