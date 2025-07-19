<?php

class Comment {
    private $db;

    public function __construct(Database $database) {
        $this->db = $database;
    }

    // UUID Generator
    private function generateUUID(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

public function create($text, $subscriber_id, $post_id = null, $comment_count = 0, $timestamp = null, $parent_id = null) {
    try {


        // Check ban status


        $banCheckSql = "SELECT banned FROM subscribers WHERE id = :subscriber_id";
        $stmt = $this->db->prepare($banCheckSql);
        $stmt->execute(['subscriber_id' => $subscriber_id]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscriber) {
            error_log('Subscriber not found: ' . $subscriber_id);
            http_response_code(404);
            echo json_encode(['error' => 'Subscriber not found',"id"=>(string) $subscriber_id]);
            exit;
        }

        if ($subscriber['banned'] == 1) {
            error_log('Subscriber is banned: ' . $subscriber_id);
            http_response_code(403);
            echo json_encode(['error' => 'Subscriber is banned']);
            exit;
        }

        // Proceed to create comment
        $comment_id = $this->generateUUID();
        $sql = "INSERT INTO comments (comment_id, text, subscriber_id, post_id, comment_count, timestamp, parent_id)
                VALUES (:comment_id, :text, :subscriber_id, :post_id, :comment_count, :timestamp, :parent_id)";
        $success = $this->db->runQuery($sql, [
            'comment_id' => $comment_id,
            'text' => $text,
            'subscriber_id' => $subscriber_id,
            'post_id' => $post_id,
            'comment_count' => $comment_count,
            'timestamp' => $timestamp ?? date('Y-m-d H:i:s'),
            'parent_id' => $parent_id
        ]);

        if (!$success) {
            error_log('Failed to insert comment: ' . json_encode($this->db->errorInfo()));
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'details' => $this->db->errorInfo()]);
            exit;
        }

        http_response_code(201); // Created
         return $comment_id;

    } catch (Exception $e) {
        error_log('Create comment exception: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Exception occurred', 'details' => $e->getMessage()]);
    }
}


    // Read comment by ID
    public function read($post_id) {
        $sql = "SELECT * FROM comments WHERE post_id = :post_id";
        $result = $this->db->runQuery($sql, ['post_id' => $post_id]);
        return $result[0] ?? null;
    }

    // Update comment text and count
    public function update($comment_id, $text, $comment_count = null) {
        $sql = "UPDATE comments SET text = :text, comment_count = :comment_count WHERE comment_id = :comment_id";
        return $this->db->runQuery($sql, [
            'comment_id' => $comment_id,
            'text' => $text,
            'comment_count' => $comment_count
        ]);
    }

public function getAll() {
    $sql = "
        SELECT
            comments.comment_id,
            comments.parent_id,
            comments.text,
            comments.subscriber_id,
            subscribers.name AS subscriber_name,
            comments.comment_count,
            comments.timestamp,
            comments.post_id,
            comments.like_count
        FROM comments
        JOIN subscribers ON comments.subscriber_id = subscribers.id
        ORDER BY comments.timestamp DESC
    ";
    return $this->db->runQuery($sql);
}



    // Delete a comment
    public function delete($comment_id) {
    $sql = "DELETE FROM comments WHERE comment_id = :comment_id";
    return $this->db->runQuery($sql, [
        'comment_id' => $comment_id

    ]);
    }


    // Get all comments for a post (optional helper)
    public function getCommentsByPost($post_id) {
        $sql = "SELECT * FROM comments WHERE post_id = :post_id ORDER BY timestamp DESC";
        return $this->db->runQuery($sql, ['post_id' => $post_id]);
    }
         public function getById($comment_id) {
            $sql = "SELECT * FROM comments WHERE comment_id = :comment_id";
            $result = $this->db->runQuery($sql, ['comment_id' => $comment_id]);
            // runQuery returns an array of results for SELECT, so get first element or null if empty
            return $result ? $result[0] ?? null : null;
        }


}
