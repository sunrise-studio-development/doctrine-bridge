<?php

declare(strict_types=1);

use Doctrine\DBAL\Logging\DebugStack as SqlLogger;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\IntegerType;
use Symfony\Component\Cache\Adapter\ArrayAdapter as ArrayCache;

$config = [];

//
// dbal types
//
$config['foo']['types']['custom.boolean'] = BooleanType::class;
$config['foo']['types']['custom.integer'] = IntegerType::class;

//
// optimal dbal configuration of the foo instance
//
$config['foo']['dbal']['auto_commit'] = false;
$config['foo']['dbal']['result_cache'] = new ArrayCache();
$config['foo']['dbal']['sql_logger'] = new SqlLogger();

$config['foo']['dbal']['connection']['url'] = sprintf(
    'sqlite:///%s/746bd974-91f0-48af-aaaf-11a52ae4207a.sqlite',
    sys_get_temp_dir()
);

$config['foo']['dbal']['connection']['@id']= '96a381e1-c385-4813-88a9-69f1a4f63425';

//
// optimal orm configuration of the foo instance
//
$config['foo']['orm']['entity_namespaces'] = [
    'App' => 'Sunrise\Bridge\Doctrine\Tests\Fixtures\Entity',
];

$config['foo']['orm']['entity_locations'] = realpath(__DIR__ . '/../Entity');
$config['foo']['orm']['metadata_cache'] = new ArrayCache();
$config['foo']['orm']['query_cache'] = new ArrayCache();
$config['foo']['orm']['hydration_cache'] = new ArrayCache();
$config['foo']['orm']['proxy_dir'] = sys_get_temp_dir();
$config['foo']['orm']['proxy_auto_generate'] = false;

//
// minimal dbal configuration of the bar instance
//
$config['bar']['dbal']['connection']['url'] = sprintf(
    'sqlite:///%s/7fcc7f3d-3ff3-4325-8a1f-2693e0c30617.sqlite',
    sys_get_temp_dir()
);

$config['bar']['dbal']['connection']['@id']= '6540b565-95ce-49e1-bb3c-61658915c11c';

//
// minimal orm configuration of the bar instance
//
$config['bar']['orm']['entity_namespaces'] = $config['foo']['orm']['entity_namespaces'];
$config['bar']['orm']['entity_locations'] = $config['foo']['orm']['entity_locations'];
$config['bar']['orm']['metadata_driver'] = 'attributes';
$config['bar']['orm']['proxy_dir'] = $config['foo']['orm']['proxy_dir'];

return $config;
