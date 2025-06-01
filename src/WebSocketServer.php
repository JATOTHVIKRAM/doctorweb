<?php
namespace Hospital;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        $conn->resourceId = uniqid();
        
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        // Handle subscription to specific topics
        if (isset($data['action']) && $data['action'] === 'subscribe') {
            $topics = $data['topics'] ?? [];
            
            foreach ($topics as $topic) {
                if (!isset($this->subscriptions[$topic])) {
                    $this->subscriptions[$topic] = [];
                }
                
                // Add this connection to the topic subscription
                $this->subscriptions[$topic][$from->resourceId] = $from;
                echo "Client {$from->resourceId} subscribed to {$topic}\n";
            }
            
            // Confirm subscription
            $from->send(json_encode([
                'type' => 'subscription_confirmation',
                'topics' => $topics
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove connection from all subscriptions
        foreach ($this->subscriptions as $topic => &$subscribers) {
            if (isset($subscribers[$conn->resourceId])) {
                unset($subscribers[$conn->resourceId]);
            }
        }
        
        // Detach connection
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Method to broadcast updates to clients subscribed to a specific topic
    public function broadcastToTopic($topic, $data) {
        if (!isset($this->subscriptions[$topic])) {
            return;
        }
        
        $encodedData = json_encode($data);
        
        foreach ($this->subscriptions[$topic] as $conn) {
            $conn->send($encodedData);
        }
        
        echo "Broadcasted update to topic: {$topic}\n";
    }
} 