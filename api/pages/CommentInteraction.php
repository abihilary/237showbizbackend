<?php

class CommentInteraction {
    private $db;

    public function __construct(Database $database) {
        $this->db = $database;
    }

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

    // Toggle like/unlike and update like count accordingly
    public function toggleLike($comment_id, $subscriber_id, $like) {
        if ($like) {
            // Insert like only if not exists, then increment like_count
            $insertSql = "INSERT IGNORE INTO comment_likes (comment_id, subscriber_id) VALUES (:comment_id, :subscriber_id)";
            $this->db->runQuery($insertSql, [
                'comment_id' => $comment_id,
                'subscriber_id' => $subscriber_id
            ]);

            // Increment like count
            $updateSql = "UPDATE comments SET like_count = like_count + 1
                          WHERE comment_id = :comment_id
                          AND NOT EXISTS (
                              SELECT 1 FROM comment_likes
                              WHERE comment_id = :comment_id AND subscriber_id = :subscriber_id
                          )";
            $this->db->runQuery($updateSql, [
                'comment_id' => $comment_id,
                'subscriber_id' => $subscriber_id
            ]);
        } else {
            // Delete like and decrement like_count
            $deleteSql = "DELETE FROM comment_likes WHERE comment_id = :comment_id AND subscriber_id = :subscriber_id";
            $this->db->runQuery($deleteSql, [
                'comment_id' => $comment_id,
                'subscriber_id' => $subscriber_id
            ]);

            $decrementSql = "UPDATE comments SET like_count = GREATEST(like_count - 1, 0) WHERE comment_id = :comment_id";
            $this->db->runQuery($decrementSql, [
                'comment_id' => $comment_id
            ]);
        }

        return true;
    }

    // Reply to a comment
    public function replyToComment($parent_comment_id, $subscriber_id, $text, $post_id) {
    $comment_id = $this->generateUUID();
    $sql = "INSERT INTO comments (comment_id, parent_id, subscriber_id, text, post_id, timestamp)
            VALUES (:comment_id, :parent_id, :subscriber_id, :text, :post_id, :timestamp)";

    return $this->db->runQuery($sql, [
        'comment_id' => $comment_id,
        'parent_id' => $parent_comment_id,
        'subscriber_id' => $subscriber_id,
        'text' => $text,
        'post_id' => $post_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}


    // Get replies for a comment
    public function getReplies($comment_id) {
        $sql = "SELECT * FROM comment_replies WHERE comment_id = :comment_id ORDER BY timestamp ASC";
        return $this->db->runQuery($sql, ['comment_id' => $comment_id]);
    }

    // Get like count for a comment
    public function getLikeCount($comment_id) {
        $sql = "SELECT COUNT(*) as like_count FROM comment_likes WHERE comment_id = :comment_id";
        $result = $this->db->runQuery($sql, ['comment_id' => $comment_id]);
        return $result[0]['like_count'] ?? 0;
    }
    public function delete($comment_id, $subscriber_id) {
    // OPTIONAL: check if the subscriber owns the comment before deleting
    $sql = "DELETE FROM comments WHERE comment_id = :comment_id";
    return $this->db->runQuery($sql, ['comment_id' => $comment_id]);
}
}
