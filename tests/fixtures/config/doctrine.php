<?php

declare(strict_types=1);

$config = [];

//
// DBAL configuration of the foo instance
//
$config['foo']['dbal']['connection']['url'] = sprintf(
    'sqlite:///%s/746bd974-91f0-48af-aaaf-11a52ae4207a.sqlite',
    sys_get_temp_dir()
);

//
// ORM configuration of the foo instance
//
$config['foo']['orm']['entity_locations'] = realpath(__DIR__ . '/../Entity/Common');
$config['foo']['orm']['proxy_dir'] = sys_get_temp_dir();

//
// Migrations configuration of the foo instance
//
// https://www.doctrine-project.org/projects/doctrine-migrations/en/3.0/reference/configuration.html#configuration
//
// https://github.com/doctrine/migrations/blob/51e470344d6896aed99aa701e90ce85d4d71905b/lib/Doctrine/Migrations/Configuration/Migration/ConfigurationArray.php#L34-L70
//
$config['foo']['migrations'] = [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 1024,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],
    'migrations_paths' => [
        'App\Migrations' => './resources/migrations',
    ],
    'all_or_nothing' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
];

//
// DBAL configuration of the bar instance
//
$config['bar']['dbal']['connection']['url'] = sprintf(
    'sqlite:///%s/7fcc7f3d-3ff3-4325-8a1f-2693e0c30617.sqlite',
    sys_get_temp_dir()
);

//
// ORM configuration of the bar instance
//
$config['bar']['orm']['entity_locations'] = $config['foo']['orm']['entity_locations'];
$config['bar']['orm']['proxy_dir'] = $config['foo']['orm']['proxy_dir'];

return $config;
