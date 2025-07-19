<?php
$host = '07.180.221.148';


// Create a TCP/IP stream socket
$socket = stream_socket_server("tcp://0.0.0.0:9090", $err, $errMsg);
if (!$socket) die("Could not create socket: $errMsg");

echo "Server started on $host:$port\n";

$clients = [];

while (true) {
    $read_sockets = array_merge([$socket], $clients);
    $write = $except = null;
    $ready = stream_select($read_sockets, $write, $except, null);
    if (in_array($socket, $read_sockets)) {
        $new_client = stream_socket_accept($socket);
        if ($new_client) {
            // Read handshake headers
            $headers = fread($new_client, 1024);
            // Perform WebSocket handshake
            $headers = explode("\r\n", $headers);
            foreach ($headers as $header) {
                if (preg_match('/Sec-WebSocket-Key: (.*)/i', $header, $matches)) {
                    $key = trim($matches[1]);
                }
            }
            $accept_key = base64_encode(openssl_digest($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', 'sha1', true));
            $response_headers = "HTTP/1.1 101 Switching Protocols\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Accept: " . $accept_key . "\r\n\r\n";
            fwrite($new_client, $response_headers);
            $clients[] = $new_client;
        }
        // Remove server socket from array
        $sockets = array_diff($read_sockets, [$socket]);
    } else {
        $sockets = $read_sockets;
    }

    // Check for data from clients
    foreach ($sockets as $client) {
        $data = fread($client, 2048);
        if (!$data) {
            // Client disconnected
            fclose($client);
            $clients = array_diff($clients, [$client]);
            continue;
        }

        // Decode WebSocket frame
        $decoded = decodeFrame($data);
        echo "Received: " . $decoded . "\n";

        // Send message back
        $message = "Server received: " . $decoded;
        $encoded = encodeFrame($message);
        fwrite($client, $encoded);
    }
}

function encodeFrame($payload) {
    $frame = [];
    $frame[] = 129; // 10000001, text frame
    $length = strlen($payload);

    if ($length <= 125) {
        $frame[] = $length;
    } elseif ($length <= 65535) {
        $frame[] = 126;
        $frame[] = ( $length >> 8 ) & 255;
        $frame[] = $length & 255;
    } else {
        // Larger than 65535
    }

    $encryptedPayload = '';
    for ($i = 0; $i < strlen($payload); $i++) {
        $encryptedPayload .= $payload[$i];
    }
    return chr(129) . chr($length) . $payload; // Simplified, not handling masking
}

function decodeFrame($data) {
    // Basic decoder (no masking handled)
    $length = ord($data[1]) & 127;
    if ($length === 126) {
        $mask_offset = 4;
        $payload_offset = 8;
    } else {
        $mask_offset = 2;
        $payload_offset = 2;
    }
    $payload = substr($data, $payload_offset);
    return $payload;
}
?>