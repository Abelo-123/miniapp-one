<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// websocket_server.php - WebSocket Server for Real-time Order Updates
// Run this with: php websocket_server.php

require_once 'db.php';

class OrderWebSocketServer {
    private $clients = [];
    private $server;
    private $conn;
    
    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->server = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (!$this->server) {
            die("Error creating server: $errstr ($errno)\n");
        }
        
        echo "WebSocket server started on $host:$port\n";
        $this->conn = $GLOBALS['conn'];
    }
    
    public function run() {
        while (true) {
            $read = array_merge([$this->server], $this->clients);
            $write = $except = null;
            
            if (stream_select($read, $write, $except, 0, 200000) < 1) {
                continue;
            }
            
            // New client connection
            if (in_array($this->server, $read)) {
                $client = stream_socket_accept($this->server);
                $this->clients[] = $client;
                echo "New client connected\n";
                
                $key = array_search($this->server, $read);
                unset($read[$key]);
            }
            
            // Handle client messages
            foreach ($read as $client) {
                $data = fread($client, 8192);
                
                if (!$data) {
                    $this->disconnect($client);
                    continue;
                }
                
                // WebSocket handshake
                if (!isset($this->clients[array_search($client, $this->clients)]['handshake'])) {
                    $this->handshake($client, $data);
                } else {
                    $message = $this->decode($data);
                    if ($message) {
                        $this->handleMessage($client, $message);
                    }
                }
            }
        }
    }
    
    private function handshake($client, $headers) {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match)) {
            $key = base64_encode(pack('H*', sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                       "Upgrade: websocket\r\n" .
                       "Connection: Upgrade\r\n" .
                       "Sec-WebSocket-Accept: $key\r\n\r\n";
            fwrite($client, $response);
            
            $index = array_search($client, $this->clients);
            $this->clients[$index] = ['socket' => $client, 'handshake' => true, 'user_id' => null];
            echo "Handshake completed\n";
        }
    }
    
    private function decode($data) {
        $length = ord($data[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $payload = substr($data, 6);
        }
        
        $text = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $text .= $payload[$i] ^ $masks[$i % 4];
        }
        
        return $text;
    }
    
    private function encode($message) {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($message);
        
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }
        
        return $header . $message;
    }
    
    private function handleMessage($client, $message) {
        $data = json_decode($message, true);
        
        if (!$data) return;
        
        $index = array_search($client, array_column($this->clients, 'socket'));
        
        switch ($data['type'] ?? '') {
            case 'auth':
                // Authenticate user
                $this->clients[$index]['user_id'] = $data['user_id'] ?? null;
                $this->send($client, json_encode(['type' => 'auth_success', 'user_id' => $data['user_id']]));
                echo "User {$data['user_id']} authenticated\n";
                break;
                
            case 'subscribe_orders':
                // User wants to subscribe to order updates
                $user_id = $this->clients[$index]['user_id'];
                if ($user_id) {
                    $this->sendOrderUpdate($client, $user_id);
                }
                break;
        }
    }
    
    private function sendOrderUpdate($client, $user_id) {
        // Fetch latest orders from DB
        $stmt = $this->conn->prepare("SELECT id, api_order_id, service_name, link, quantity, charge, status, remains, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        $this->send($client, json_encode([
            'type' => 'orders_update',
            'orders' => $orders
        ]));
    }
    
    public function broadcast($message, $user_id = null) {
        foreach ($this->clients as $client) {
            if (is_array($client) && isset($client['socket'])) {
                if ($user_id === null || $client['user_id'] == $user_id) {
                    $this->send($client['socket'], $message);
                }
            }
        }
    }
    
    private function send($client, $message) {
        fwrite($client, $this->encode($message));
    }
    
    private function disconnect($client) {
        $index = array_search($client, array_column($this->clients, 'socket'));
        if ($index !== false) {
            unset($this->clients[$index]);
        }
        fclose($client);
        echo "Client disconnected\n";
    }
}

// Start the server
$server = new OrderWebSocketServer('0.0.0.0', 8080);
$server->run();
?>
