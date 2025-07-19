<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/dbConnect.php';
require_once __DIR__ . '/../pages/CommentInteraction.php';

$db = new Database();
$commentInteraction = new CommentInteraction($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$comment_id = $_GET['comment_id'] ?? null;
$subscriber_id = $_GET['subscriber_id'] ?? null;

switch ($method) {

    // Get replies or like count
    case 'GET':
        if (!$comment_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing comment_id']);
            break;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'replies') {
            $replies = $commentInteraction->getReplies($comment_id);
            echo json_encode($replies);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'like_count') {
            $count = $commentInteraction->getLikeCount($comment_id);
            echo json_encode(['like_count' => $count]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid GET action']);
        }
        break;

    // Like/unlike, reply, or delete
    case 'POST':
        if (!$comment_id || !$subscriber_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing comment_id or subscriber_id']);
            break;
        }

        if (isset($input['action'])) {
            if ($input['action'] === 'toggle_like') {
                if (!isset($input['like'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing like parameter']);
                    break;
                }
                $success = $commentInteraction->toggleLike($comment_id, $subscriber_id, (bool)$input['like']);
                echo json_encode(['success' => $success]);

            } elseif ($input['action'] === 'reply') {
                if (empty($input['text'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing reply text']);
                    break;
                }
                if (empty($input['post_id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing post_id']);
                    break;
                }
                $success = $commentInteraction->replyToComment($comment_id, $subscriber_id, $input['text'], $input['post_id']);
                echo json_encode(['success' => $success]);

            } elseif ($input['action'] === 'delete') {
                // Optional: add subscriber ownership check here
                $success = $commentInteraction->delete($comment_id, $subscriber_id);
                echo json_encode(['success' => $success]);

            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid POST action']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action parameter']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
