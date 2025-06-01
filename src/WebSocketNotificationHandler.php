<?php
namespace Hospital;

use React\EventLoop\LoopInterface;
use React\Datagram\Socket;
use React\Datagram\Factory;

class WebSocketNotificationHandler {
    private $webSocketServer;
    private $loop;
    private $port;

    public function __construct(WebSocketServer $webSocketServer, LoopInterface $loop, $port = 8081) {
        $this->webSocketServer = $webSocketServer;
        $this->loop = $loop;
        $this->port = $port;
    }
    
    public function start() {
        // Create UDP socket factory
        $factory = new Factory($this->loop);
        
        // Create the server
        $factory->createServer('0.0.0.0:' . $this->port)
            ->then(function (Socket $server) {
                echo "Notification handler listening on port {$this->port}\n";
                
                // Handle messages
                $server->on('message', function ($message, $address) {
                    $this->handleNotification($message);
                });
                
                // Handle errors
                $server->on('error', function ($error) {
                    echo "Notification handler error: {$error->getMessage()}\n";
                });
            });
    }
    
    private function handleNotification($message) {
        $data = json_decode($message, true);
        
        if (!$data || !isset($data['topic']) || !isset($data['data'])) {
            echo "Invalid notification format\n";
            return;
        }
        
        $topic = $data['topic'];
        $content = $data['data'];
        
        // Forward to WebSocket server
        $this->webSocketServer->broadcastToTopic($topic, $content);
        echo "Forwarded notification to topic: $topic\n";
    }
} 