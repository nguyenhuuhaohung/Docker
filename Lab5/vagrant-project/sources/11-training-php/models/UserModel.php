<?php
require_once 'BaseModel.php';

class UserModel extends BaseModel {

    // Find by id (safe)
    public function findUserById($id) {
        $id = (int)$id;
        $sql = 'SELECT * FROM users WHERE id = ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $user;
    }

    // Find by keyword (safe, LIKE)
    public function findUser($keyword) {
        $kw = '%' . $keyword . '%';
        $sql = 'SELECT * FROM users WHERE user_name LIKE ? OR user_email LIKE ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $kw, $kw);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $user;
    }

    // Authentication user (safe) — use password_hash in future if possible
    public function auth($userName, $password) {
        // If you're using plain MD5 currently, still avoid injection:
        $md5Password = md5($password);
        $sql = 'SELECT * FROM users WHERE name = ? AND password = ? LIMIT 1';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $userName, $md5Password);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $user;
    }

    // Delete user by id (safe)
    public function deleteUserById($id) {
        $id = (int)$id;
        $sql = 'DELETE FROM users WHERE id = ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Update user (safe)
    public function updateUser($input) {
        $id = (int)$input['id'];
        $name = $input['name'];
        $password = md5($input['password']); // consider migrating to password_hash()
        $sql = 'UPDATE users SET name = ?, password = ? WHERE id = ?';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ssi', $name, $password, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Insert user (safe)
    public function insertUser($input) {
        $name = $input['name'];
        $password = md5($input['password']); // migrate to password_hash()
        $sql = 'INSERT INTO users (name, password) VALUES (?, ?)';
        $stmt = self::$_connection->prepare($sql);
        $stmt->bind_param('ss', $name, $password);
        $ok = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        return $ok ? $insertId : false;
    }

    // Search users (safe) — replace vulnerable multi_query usage
    public function getUsers($params = []) {
        if (!empty($params['keyword'])) {
            // safe prepared LIKE
            $kw = '%' . $params['keyword'] . '%';
            $sql = 'SELECT * FROM users WHERE name LIKE ?';
            $stmt = self::$_connection->prepare($sql);
            $stmt->bind_param('s', $kw);
            $stmt->execute();
            $res = $stmt->get_result();
            $users = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $sql = 'SELECT * FROM users';
            $users = $this->select($sql); // legacy helper still fine
        }
        return $users;
    }
}
