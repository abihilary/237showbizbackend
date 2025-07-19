<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/dbConnect.php';
require_once __DIR__ . '/../pages/comment.php';

$db = new Database();
$commentModel = new Comment($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$pathParts = explode('/', $path);

// URL structure: /api/comments or /api/comments/{id}
$commentId = $pathParts[2] ?? null;
$post_id = $pathParts[2] ?? null;

switch ($method) {
    case 'GET':
        if ($post_id) {
            // Get comments by post ID
            $comments = $commentModel->getCommentsByPost($post_id);
            if ($comments) {
                echo json_encode($comments);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Comments not found']);
            }
        } else {
            // Get all comments
            $comments = $commentModel->getAll();
            echo json_encode($comments);
        }
        break;

    case 'POST':
        // Get the raw POST input
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        // Handle editing an existing comment
        if (isset($data['action']) && $data['action'] === 'edit') {
            $commentId = $_GET['comment_id'] ?? null;
            $subscriberId = $_GET['subscriber_id'] ?? null;

            if (!$commentId || !$subscriberId) {
                http_response_code(400);
                echo json_encode(['error' => 'comment_id and subscriber_id are required']);
                exit;
            }

            if (empty($data['text'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing field: text']);
                exit;
            }

            // Check if the comment exists
            $comment = $commentModel->getById($commentId);
            if (!$comment) {
                http_response_code(404);
                echo json_encode(['error' => 'Comment not found']);
                exit;
            }

            // Verify ownership
            if ($comment['subscriber_id'] != $subscriberId) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not allowed to edit this comment']);
                exit;
            }

            // Proceed with update
            $updated = $commentModel->update($commentId, $data['text']);
            if ($updated) {
                echo json_encode(['success' => 1, 'message' => 'Comment updated']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update comment']);
            }
            exit;
        }

        // Handle creating a new comment
        if (empty($data['text']) || empty($data['subscriber_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: text, subscriber_id']);
            exit;
        }

        $newId = $commentModel->create(
            $data['text'],
            $data['subscriber_id'],
            $data['post_id'] ?? null
        );

        if ($newId) {
            echo json_encode(['message' => 'Comment created', 'comment_id' => $newId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create comment']);
        }
        break;


    case 'DELETE':
        if (!$commentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Comment ID is required for deletion']);
            exit;
        }

        // Get subscriber_id securely; for demo, from query param or header


        $deleted = $commentModel->delete($commentId);

        if ($deleted) {
            echo json_encode(['message' => 'Comment deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Comment not found or you do not have permission to delete this comment']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
