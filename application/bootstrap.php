<?php

$env = 'dev';
$debug = true;
$rootDir = __DIR__;
$varDir = __DIR__;
$entityPath = __DIR__.'/src/Sample/Housing/Entities';

$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

$console = new \Symfony\Component\Console\Application();

$app = new Sample\Housing\HousingApp(array(
    'env' => $env,
    'root_dir' => $rootDir,
    'cache_dir' => $varDir.'/cache/'.$env,
    'log_dir' => $varDir.'/logs/'.$env,
    'debug' => $debug,
), $console);
