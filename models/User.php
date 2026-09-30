<?php
/**
 * User Data Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new user (Student or Teacher)
     */
    public function create($data) {
        // Encrypt password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (full_name, student_id, email, password, school_id, grade_level, group_name, role, status) 
                VALUES (:full_name, :student_id, :email, :password, :school_id, :grade_level, :group_name, :role, 'active')";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':full_name' => $data['full_name'],
            ':student_id' => isset($data['student_id']) ? $data['student_id'] : null,
            ':email' => $data['email'],
            ':password' => $hashedPassword,
            ':school_id' => $data['school_id'],
            ':grade_level' => isset($data['grade_level']) ? $data['grade_level'] : null,
            ':group_name' => isset($data['group_name']) ? $data['group_name'] : null,
            ':role' => isset($data['role']) ? $data['role'] : 'student'
        ]);
    }

    /**
     * Find user by Email
     */
    public function findByEmail($email) {
        $sql = "SELECT u.*, s.school_name 
                FROM users u 
                JOIN schools s ON u.school_id = s.id 
                WHERE u.email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Find user by Email or by Admin alias with School ID
     */
    public function findByCredentials($identifier, $schoolId = null) {
        $identifier = trim($identifier);
        
        // 1. Direct search by email
        $user = $this->findByEmail($identifier);
        if ($user) return $user;

        // 2. If identifier is "admin" or "administrador" and schoolId is supplied
        if (in_array(strtolower($identifier), ['admin', 'administrador', 'admin_colegio']) && !empty($schoolId)) {
            $sql = "SELECT u.*, s.school_name 
                    FROM users u 
                    JOIN schools s ON u.school_id = s.id 
                    WHERE u.role = 'admin' AND u.school_id = :school_id
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':school_id' => intval($schoolId)]);
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT u.*, s.school_name, s.primary_color, s.secondary_color, s.logo 
                FROM users u 
                JOIN schools s ON u.school_id = s.id 
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Check if email is already registered
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Update user profile details
     */
    public function update($id, $data) {
        $sql = "UPDATE users SET 
                full_name = :full_name, 
                student_id = :student_id, 
                email = :email, 
                grade_level = :grade_level, 
                group_name = :group_name";
        
        $params = [
            ':id' => $id,
            ':full_name' => $data['full_name'],
            ':student_id' => isset($data['student_id']) ? $data['student_id'] : null,
            ':email' => $data['email'],
            ':grade_level' => isset($data['grade_level']) ? $data['grade_level'] : null,
            ':group_name' => isset($data['group_name']) ? $data['group_name'] : null
        ];

        // If new password is provided, update it
        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        // If new avatar is provided, update it
        if (isset($data['avatar']) && !empty($data['avatar'])) {
            $sql .= ", avatar = :avatar";
            $params[':avatar'] = $data['avatar'];
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get all users (Admin dashboard functionality)
     */
    public function getAll($schoolId = null) {
        $sql = "SELECT u.id, u.full_name, u.student_id, u.email, u.grade_level, u.group_name, u.role, u.status, u.created_at, s.school_name 
                FROM users u 
                JOIN schools s ON u.school_id = s.id";
        
        $params = [];
        if (!empty($schoolId)) {
            $sql .= " WHERE u.school_id = :school_id";
            $params[':school_id'] = intval($schoolId);
        }

        $sql .= " ORDER BY u.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update user status (Active / Suspended / Banned)
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, ['active', 'suspended', 'banned'])) {
            return false;
        }
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Delete user completely
     */
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get seller rating details and count
     */
    public function getSellerReputation($sellerId) {
        $sql = "SELECT COALESCE(AVG(rating), 0.0) as average_rating, COUNT(*) as review_count 
                FROM reviews 
                WHERE seller_id = :seller_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':seller_id' => $sellerId]);
        return $stmt->fetch();
    }
}
