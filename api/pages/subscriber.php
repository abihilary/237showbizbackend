<?php
class Subscriber {
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

public function create($name, $email) {
    // Check if the subscriber already exists
    $sql = "SELECT banned FROM subscribers WHERE email = :email";
    $existingSubscriber = $this->db->runQuery($sql, ['email' => $email]);

    if ($existingSubscriber) {
        // If the subscriber exists and is banned
        if ($existingSubscriber[0]['banned'] == 1) {
            // Return error with appropriate HTTP status code
            return [
                'error' => 'Subscriber is banned and cannot be created.',
                'http_code' => 403
            ];
        }

        // Optionally, notify that the subscriber already exists
        // For now, just return an error or handle as needed
        return [
            'error' => 'Subscriber already exists.',
            'http_code' => 409
        ];
    }

    // Generate a new UUID for the subscriber
    $subscriber_id = $this->generateUUID();

    // Prepare the SQL query to create a new subscriber
    $sql = "INSERT INTO subscribers (id, name, email) VALUES (:subscriber_id, :name, :email)";
    $result = $this->db->runQuery($sql, [
        'subscriber_id' => $subscriber_id,
        'name' => $name,
        'email' => $email
    ]);

    // Check if the query was successful
    if ($result !== false) {
        return [
            'id' => ['subscriber_id' => $subscriber_id],
            'name' => $name,
            'email' => $email
        ];
    } else {
        return [
            'error' => 'Database insert failed.'
        ];
    }
}

 public function getAll() {
        $sql = "SELECT * FROM subscribers";
        return $this->db->runQuery($sql);
    }



    public function read($id) {
        $sql = "SELECT * FROM subscribers WHERE id = :id";
        $result = $this->db->runQuery($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function update($id, $name, $email) {
        $sql = "UPDATE subscribers SET name = :name, email = :email WHERE id = :id";
        return $this->db->runQuery($sql, compact('id', 'name', 'email'));
    }

    public function delete($id) {
        $sql = "DELETE FROM subscribers WHERE id = :id";
        return $this->db->runQuery($sql, ['id' => $id]);
    }

  public function toggleBan($id) {
    try {
        $sql = "SELECT banned FROM subscribers WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $currentBanStatus = $result['banned'];
            $newBanStatus = ($currentBanStatus == 0) ? 1 : 0;

            $updateSql = "UPDATE subscribers SET banned = :banned WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateResult = $updateStmt->execute([
                'banned' => $newBanStatus,
                'id' => $id
            ]);

            if ($updateResult) {
                return [
                    'subscriber_id' => $id,
                    'new_ban_status' => $newBanStatus
                ];
            } else {
                error_log("Failed to update ban status for subscriber ID $id");
                return false;
            }
        } else {
            return false; // Subscriber not found
        }
    } catch (PDOException $e) {
        error_log("Database error in toggleBan: " . $e->getMessage());
        return false;
    }
}


}
