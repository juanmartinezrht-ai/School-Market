<?php
/**
 * Student Product Data Model
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new student product
     */
    public function create($data) {
        $stock = isset($data['stock']) ? max(1, intval($data['stock'])) : 1;
        $sql = "INSERT INTO student_products (user_id, school_id, title, description, category, `condition`, price, stock, image, status) 
                VALUES (:user_id, :school_id, :title, :description, :category, :condition, :price, :stock, :image, 'approved')";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':school_id' => $data['school_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':condition' => $data['condition'],
            ':price' => $data['price'],
            ':stock' => $stock,
            ':image' => $data['image'] // JSON array of image paths
        ]);
    }

    /**
     * Update product details
     */
    public function update($id, $data) {
        $sql = "UPDATE student_products SET 
                title = :title, 
                description = :description, 
                category = :category, 
                `condition` = :condition, 
                price = :price";
        
        $params = [
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':condition' => $data['condition'],
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
     * Delete product
     */
    public function delete($id) {
        $sql = "DELETE FROM student_products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find a product by ID with details of the seller, school and seller reputation
     */
    public function findById($id) {
        $sql = "SELECT p.*, u.full_name as seller_name, u.email as seller_email, u.grade_level, u.group_name, s.school_name, s.primary_color, s.secondary_color
                FROM student_products p
                JOIN users u ON p.user_id = u.id
                JOIN schools s ON p.school_id = s.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * List all products with dynamic filter parameters
     */
    public function list($filters = []) {
        $sql = "SELECT p.*, u.full_name as seller_name, u.grade_level, u.group_name, s.school_name 
                FROM student_products p
                JOIN users u ON p.user_id = u.id
                JOIN schools s ON p.school_id = s.id
                WHERE 1=1";
        
        $params = [];

        // Apply filters
        if (!empty($filters['school_id'])) {
            $sql .= " AND p.school_id = :school_id";
            $params[':school_id'] = $filters['school_id'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND p.condition = :condition";
            $params[':condition'] = $filters['condition'];
        }

        if (!empty($filters['grade_level'])) {
            $sql .= " AND u.grade_level = :grade_level";
            $params[':grade_level'] = $filters['grade_level'];
        }

        if (!empty($filters['group_name'])) {
            $sql .= " AND u.group_name = :group_name";
            $params[':group_name'] = $filters['group_name'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE :search1 OR p.description LIKE :search2)";
            $params[':search1'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $sql .= " AND p.price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $sql .= " AND p.price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }

        // Standard filter for approved products (except for admin requests)
        if (!isset($filters['admin_view']) || !$filters['admin_view']) {
            $sql .= " AND p.status = 'approved'";
        } else if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get products by User ID (Seller listings)
     */
    public function getByUserId($userId) {
        $sql = "SELECT p.*, s.school_name 
                FROM student_products p
                JOIN schools s ON p.school_id = s.id 
                WHERE p.user_id = :user_id 
                ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Moderate Listing status (Admin action)
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, ['pending', 'approved', 'hidden'])) {
            return false;
        }
        $sql = "UPDATE student_products SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Decrement stock for a product, and hide if stock reaches 0
     */
    public function decrementStock($id, $quantity = 1) {
        $quantity = max(1, intval($quantity));
        $sql = "UPDATE student_products SET stock = GREATEST(0, stock - :qty) WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':qty' => $quantity, ':id' => $id]);

        // Check if stock reached 0, then update status to 'hidden'
        $checkStmt = $this->db->prepare("SELECT stock FROM student_products WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        $remaining = intval($checkStmt->fetchColumn());
        if ($remaining <= 0) {
            $this->updateStatus($id, 'hidden');
        }
        return $remaining;
    }
}
