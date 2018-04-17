<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/bootstrap.php';

use Eole\Sandstone\Websocket\Server;
use Sample\Process\Events\EventSubscriber\ProcessListener;

$processListener = new ProcessListener(
    'elevated-permissions-process',
    $app['orm.em'],
    $app['serializer'],
    $app['monolog']
);

$app->topic('elevated-permissions-process', function () use ($processListener, $app) {
    return $processListener;
});

$app["cors-enabled"]($app);
// Encapsulate your application and start websocket server
$websocketServer = new Server($app);
$websocketServer->run();
