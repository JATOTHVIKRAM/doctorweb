<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Hospital\WebSocketServer;
use Hospital\DbChangeNotifier;
use Hospital\WebSocketNotificationHandler;
use React\EventLoop\Factory;
use React\Socket\SocketServer;

// Create event loop
$loop = Factory::create();

// Create WebSocket server on port 8080
$socket = new SocketServer('0.0.0.0:8080', [], $loop);
$webSocketServer = new WebSocketServer();

// Add the DB Notifier to check for database changes
$dbNotifier = new DbChangeNotifier($webSocketServer, $loop);
$dbNotifier->startMonitoring();

// Add notification handler for direct API notifications via UDP
$notificationHandler = new WebSocketNotificationHandler($webSocketServer, $loop, 8081);
$notificationHandler->start();

// Create IoServer with WebSocket protocol
$server = new IoServer(
    new HttpServer(
        new WsServer($webSocketServer)
    ),
    $socket,
    $loop
);

echo "WebSocket server running at 0.0.0.0:8080\n";
echo "Notification handler running at 0.0.0.0:8081 (UDP)\n";

// Run the server
$server->run(); 