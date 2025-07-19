<?php

require_once __DIR__ . '/../helpers/dbConnect.php';
require_once __DIR__ . '/../pages/artist.php'; // Adjust path if needed

$db = new Database();
$artist = new Artist($db);

$action = $_POST['action'] ?? '';
header('Content-Type: application/json');

switch ($action) {
    case 'fetch':
        echo json_encode($artist->getAll());
        break;

    case 'add':
        if (!isset($_POST['name'], $_POST['subscriber_id'], $_FILES['image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing name, image or subscriber_id']);
            exit;
        }
        $response = $artist->create($_POST['name'], $_FILES['image'], $_POST['subscriber_id']);
        echo json_encode($response);
        break;

    case 'edit':
        if (!isset($_POST['id'], $_POST['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing artist ID or name']);
            exit;
        }
        $response = $artist->update($_POST['id'], $_POST['name'], $_FILES['image'] ?? null);
        echo json_encode($response);
        break;

    case 'delete':
        if (!isset($_POST['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing artist ID']);
            exit;
        }
        $response = $artist->delete($_POST['id']);
        echo json_encode($response);
        break;

    case 'vote':
        if (!isset($_POST['artist_id'], $_POST['subscriber_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing artist_id or subscriber_id']);
            exit;
        }
        $response = $artist->vote($_POST['artist_id'], $_POST['subscriber_id']);
        echo json_encode($response);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
