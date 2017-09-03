<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/bootstrap.php';

$auditService = new Sample\Housing\Services\AuditService(
    'sub-dormatory',
    $app['orm.em'],
    $app['serializer']
);

$app->topic('sub-dormatory', function () use ($auditService) {
    return $auditService;
});

// Encapsulate your application and start websocket server
$websocketServer = new \Eole\Sandstone\Websocket\Server($app);

$websocketServer->run();
