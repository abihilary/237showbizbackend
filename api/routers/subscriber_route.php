<?php
// subscriber_route.php
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/dbConnect.php';
require_once __DIR__ . '/../pages/subscriber.php'; // Subscriber class

$db = new Database();
$subscriber = new Subscriber($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $data = $subscriber->read($id);
            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Subscriber not found']);
            }
        } else {
            // Get all subscribers
            $data = $subscriber->getAll();
            echo json_encode($data);
        }
        break;

    case 'POST':
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'update':
                    if (!isset($input['id'], $input['name'], $input['email'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing id, name, or email']);
                        break;
                    }
                    $success = $subscriber->update($input['id'], $input['name'], $input['email']);
                    if ($success) {
                        echo json_encode(['message' => 'Subscriber updated']);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Subscriber not found or no change']);
                    }
                    break;

                case 'toggleBan':
                    if (!isset($input['id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing id for toggleBan']);
                        break;
                    }
                    $result = $subscriber->toggleBan($input['id']);
                    if ($result) {
                        echo json_encode([
                            'message' => 'Ban status toggled',
                            'id' => $result['subscriber_id'],
                            'new_ban_status' => $result['new_ban_status']
                        ]);
                    } else {
                        error_log("Subscriber not found or update failed for ID: " . $input['id']);  // Add this
                        http_response_code(404);
                        echo json_encode(['error' => 'Subscriber not found']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
                    break;
            }
        }elseif (isset($input['name'], $input['email'])) {
    // Create new subscriber
    $result = $subscriber->create($input['name'], $input['email']);

    if (isset($result['error'])) {
        // Handle error: set appropriate status code
        // For example, if banned, use 403; if duplicate, 409, etc.
        $statusCode = ($result['error'] === 'Subscriber is banned and cannot be created.') ? 403 : 400;
        http_response_code($statusCode);
        echo json_encode(['error' => $result['error']]);
    } else {
        // Success
        http_response_code(201);
        echo json_encode([
            'message' => 'Subscriber created',
            'id' => [
                'subscriber_id' => $result['id']
            ]
        ]);
    }
} else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
        }
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing subscriber ID']);
            break;
        }
        $success = $subscriber->delete($id);
        if ($success) {
            echo json_encode(['message' => 'Subscriber deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subscriber not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
