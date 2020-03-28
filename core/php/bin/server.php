<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\OriginCheck;
use Ratchet\WebSocket\WsServer;
use JeedomNotifier\Notifier;

require_once dirname(__FILE__) . '/../../../3rparty/vendor/autoload.php';
require_once dirname(__FILE__) . '/../../../../../core/php/core.inc.php';

// Get configuration
$port = intval(config::byKey('port', 'Websocket', 8090));
$readDelay = intval(config::byKey('readDelay', 'Websocket', 5));
$authDelay = intval(config::byKey('authDelay', 'Websocket', 1));
$allowedHosts = explode(',', config::byKey('allowedHosts', 'Websocket', 'localhost'));

// Create application
$notifier = new Notifier($authDelay);

// Create socket server
$server = IoServer::factory(
    new HttpServer(
        new OriginCheck(
            new WsServer(
                $notifier
            ),
            $allowedHosts
        )
    ),
    $port
);

// Add the periodic processing (run each $readDelay seconds)
$server->loop->addPeriodicTimer(
    $readDelay,
    function () use ($notifier) {
        $notifier->process();
    }
);

// Run server
log::add('Websocket', 'info', "Listenning on port $port, hosts allowed are: " . implode(', ', $allowedHosts));
try {
    $server->run();
} catch (\Exception $e) {
    log::add('Websocket', 'error', 'Daemon crash with following error: ' . $e->getMessage());
}
