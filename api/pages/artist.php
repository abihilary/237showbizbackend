<?php

class Artist {
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

    private function subscriberExists($subscriber_id) {
        $result = $this->db->runQuery("SELECT id, banned FROM subscribers WHERE id = ?", [$subscriber_id]);
        return $result ? $result[0] : null;
    }

    public function create($name, $image, $subscriber_id) {
        $subscriber = $this->subscriberExists($subscriber_id);

        if (!$subscriber) {
            http_response_code(404);
            return ['error' => 'Subscriber not found'];
        }

        if ($subscriber['banned'] == 1) {
            http_response_code(403);
            return ['error' => 'Subscriber is banned'];
        }

        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['error' => 'Invalid image upload'];
        }

        $id = $this->generateUUID();
        $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = __DIR__ . '/../upload/' . $filename;
        $relativePath = 'upload/' . $filename;

        if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
            http_response_code(500);
            return ['error' => 'Failed to upload image'];
        }

        $sql = "INSERT INTO artists (id, name, image_path, subscriber_id) VALUES (?, ?, ?, ?)";
        $success = $this->db->runQuery($sql, [$id, $name, $relativePath, $subscriber_id]);

        return $success ? ['message' => 'Artist added', 'id' => $id] : ['error' => 'Database error'];
    }

    public function update($id, $name, $image = null) {
        if ($image && $image['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $targetPath = __DIR__ . '/../upload/' . $filename;
            $relativePath = 'upload/' . $filename;

            if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
                return ['error' => 'Failed to upload image'];
            }

            $sql = "UPDATE artists SET name = ?, image_path = ? WHERE id = ?";
            $success = $this->db->runQuery($sql, [$name, $relativePath, $id]);
        } else {
            $sql = "UPDATE artists SET name = ? WHERE id = ?";
            $success = $this->db->runQuery($sql, [$name, $id]);
        }

        return $success ? ['message' => 'Artist updated'] : ['error' => 'Update failed'];
    }
            public function vote($artist_id, $subscriber_id) {
            // Check if subscriber exists and is not banned
            $subscriber = $this->subscriberExists($subscriber_id);
            if (!$subscriber) {
                http_response_code(404);
                return ['error' => 'Subscriber not found'];
            }

            if ($subscriber['banned'] == 1) {
                http_response_code(403);
                return ['error' => 'Subscriber is banned'];
            }

            // Check if vote already exists
            $existingVote = $this->db->runQuery(
                "SELECT id FROM votes WHERE artist_id = ? AND subscriber_id = ?",
                [$artist_id, $subscriber_id]
            );

            if ($existingVote) {
                http_response_code(409);
                return ['error' => 'You have already voted for this artist'];
            }

            // Record the vote
            $insertVote = $this->db->runQuery(
                "INSERT INTO votes (artist_id, subscriber_id) VALUES (?, ?)",
                [$artist_id, $subscriber_id]
            );

            if (!$insertVote) {
                http_response_code(500);
                return ['error' => 'Vote failed'];
            }

            // Increment artist's vote count
            $update = $this->db->runQuery(
                "UPDATE artists SET votes = votes + 1 WHERE id = ?",
                [$artist_id]
            );

            return $update ? ['message' => 'Vote recorded'] : ['error' => 'Failed to update artist votes'];
        }


    public function delete($id) {
        $sql = "DELETE FROM artists WHERE id = ?";
        $success = $this->db->runQuery($sql, [$id]);
        return $success ? ['message' => 'Artist deleted'] : ['error' => 'Delete failed'];
    }

  public function getAll() {
    $sql = "
      SELECT a.id, a.name, a.image_path, a.subscriber_id, a.created_at,
             COALESCE(v.vote_count, 0) AS votes
      FROM artists a
      LEFT JOIN (
          SELECT artist_id, COUNT(*) AS vote_count
          FROM votes
          GROUP BY artist_id
      ) v ON a.id = v.artist_id
      ORDER BY votes DESC, a.created_at DESC
    ";

    return $this->db->runQuery($sql);
}


    public function getById($id) {
        $sql = "SELECT * FROM artists WHERE id = ?";
        $result = $this->db->runQuery($sql, [$id]);
        return $result[0] ?? null;
    }
}
