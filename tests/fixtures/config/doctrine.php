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
// DBAL configuration of the bar instance
//
$config['bar']['dbal']['connection']['url'] = sprintf(
    'sqlite:///%s/7fcc7f3d-3ff3-4325-8a1f-2693e0c30617.sqlite',
    sys_get_temp_dir()
);

//
// ORM configuration of the bar instance
//
$config['bar']['orm']['entity_locations'] = realpath(__DIR__ . '/../Entity/PHP8');
$config['bar']['orm']['proxy_dir'] = sys_get_temp_dir();

return $config;
