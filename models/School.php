<?php
/**
 * School Data Model
 */

require_once __DIR__ . '/../config/database.php';

class School {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new school (Admin only)
     */
    public function create($data) {
        $sql = "INSERT INTO schools (school_name, address, logo, background_image, primary_color, secondary_color) 
                VALUES (:school_name, :address, :logo, :background_image, :primary_color, :secondary_color)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':school_name' => $data['school_name'],
            ':address' => $data['address'],
            ':logo' => $data['logo'],
            ':background_image' => $data['background_image'],
            ':primary_color' => isset($data['primary_color']) ? $data['primary_color'] : '#3b82f6',
            ':secondary_color' => isset($data['secondary_color']) ? $data['secondary_color'] : '#10b981'
        ]);
    }

    /**
     * Update school data (Admin only)
     */
    public function update($id, $data) {
        $sql = "UPDATE schools SET 
                school_name = :school_name, 
                address = :address, 
                primary_color = :primary_color, 
                secondary_color = :secondary_color";
        
        $params = [
            ':id' => $id,
            ':school_name' => $data['school_name'],
            ':address' => $data['address'],
            ':primary_color' => $data['primary_color'],
            ':secondary_color' => $data['secondary_color']
        ];

        if (isset($data['logo']) && !empty($data['logo'])) {
            $sql .= ", logo = :logo";
            $params[':logo'] = $data['logo'];
        }

        if (isset($data['background_image']) && !empty($data['background_image'])) {
            $sql .= ", background_image = :background_image";
            $params[':background_image'] = $data['background_image'];
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete school (Admin only)
     */
    public function delete($id) {
        $sql = "DELETE FROM schools WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find school by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM schools WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get all schools
     */
    public function getAll() {
        $sql = "SELECT * FROM schools ORDER BY school_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Search schools by name or address
     */
    public function search($query) {
        $sql = "SELECT * FROM schools 
                WHERE school_name LIKE :query OR address LIKE :query 
                ORDER BY school_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll();
    }
}
