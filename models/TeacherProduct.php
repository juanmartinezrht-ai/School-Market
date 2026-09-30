<?php
/**
 * Teacher Product Data Model
 */

require_once __DIR__ . '/../config/database.php';

class TeacherProduct {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new teacher product
     */
    public function create($data) {
        $stock = isset($data['stock']) ? max(1, intval($data['stock'])) : 1;
        $sql = "INSERT INTO teacher_products (school_id, teacher_name, subject, grade, group_name, title, description, price, stock, image, status) 
                VALUES (:school_id, :teacher_name, :subject, :grade, :group_name, :title, :description, :price, :stock, :image, 'approved')";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':school_id' => $data['school_id'],
            ':teacher_name' => $data['teacher_name'],
            ':subject' => $data['subject'],
            ':grade' => $data['grade'],
            ':group_name' => $data['group_name'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':stock' => $stock,
            ':image' => $data['image'] // JSON array of image paths
        ]);
    }

    /**
     * Update teacher product
     */
    public function update($id, $data) {
        $sql = "UPDATE teacher_products SET 
                title = :title, 
                description = :description, 
                subject = :subject, 
                grade = :grade, 
                group_name = :group_name, 
                price = :price";
        
        $params = [
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':subject' => $data['subject'],
            ':grade' => $data['grade'],
            ':group_name' => $data['group_name'],
            ':price' => $data['price']
        ];

        if (isset($data['image']) && !empty($data['image'])) {
            $sql .= ", image = :image";
            $params[':image'] = $data['image'];
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete teacher product
     */
    public function delete($id) {
        $sql = "DELETE FROM teacher_products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find teacher product by ID
     */
    public function findById($id) {
        $sql = "SELECT tp.*, s.school_name, s.primary_color, s.secondary_color 
                FROM teacher_products tp
                JOIN schools s ON tp.school_id = s.id
                WHERE tp.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * List teacher products with advanced filters
     */
    public function list($filters = []) {
        $sql = "SELECT tp.*, s.school_name 
                FROM teacher_products tp
                JOIN schools s ON tp.school_id = s.id
                WHERE 1=1";
        
        $params = [];

        // Apply filters
        if (!empty($filters['school_id'])) {
            $sql .= " AND tp.school_id = :school_id";
            $params[':school_id'] = $filters['school_id'];
        }

        if (!empty($filters['grade'])) {
            $sql .= " AND tp.grade = :grade";
            $params[':grade'] = $filters['grade'];
        }

        if (!empty($filters['group_name'])) {
            $sql .= " AND tp.group_name = :group_name";
            $params[':group_name'] = $filters['group_name'];
        }

        if (!empty($filters['subject'])) {
            $sql .= " AND tp.subject = :subject";
            $params[':subject'] = $filters['subject'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (tp.title LIKE :search1 OR tp.description LIKE :search2 OR tp.teacher_name LIKE :search3)";
            $params[':search1'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
            $params[':search3'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $sql .= " AND tp.price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $sql .= " AND tp.price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }

        // Standard filter for approved products (except for admin view)
        if (!isset($filters['admin_view']) || !$filters['admin_view']) {
            $sql .= " AND tp.status = 'approved'";
        } else if (!empty($filters['status'])) {
            $sql .= " AND tp.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY tp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get products created by a specific teacher (matched by name/school)
     */
    public function getByTeacherName($teacherName, $schoolId) {
        $sql = "SELECT tp.*, s.school_name 
                FROM teacher_products tp
                JOIN schools s ON tp.school_id = s.id 
                WHERE tp.teacher_name = :teacher_name AND tp.school_id = :school_id
                ORDER BY tp.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':teacher_name' => $teacherName, ':school_id' => $schoolId]);
        return $stmt->fetchAll();
    }

    /**
     * Moderate Teacher Product status (Admin action)
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, ['pending', 'approved', 'hidden'])) {
            return false;
        }
        $sql = "UPDATE teacher_products SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Decrement stock for a teacher resource, and hide if stock reaches 0
     */
    public function decrementStock($id, $quantity = 1) {
        $quantity = max(1, intval($quantity));
        $sql = "UPDATE teacher_products SET stock = GREATEST(0, stock - :qty) WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':qty' => $quantity, ':id' => $id]);

        // Check if stock reached 0, then update status to 'hidden'
        $checkStmt = $this->db->prepare("SELECT stock FROM teacher_products WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        $remaining = intval($checkStmt->fetchColumn());
        if ($remaining <= 0) {
            $this->updateStatus($id, 'hidden');
        }
        return $remaining;
    }
}
