<?php
/**
 * Review Data Model
 */

require_once __DIR__ . '/../config/database.php';

class Review {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Submit a review for a seller
     */
    public function create($data) {
        $sql = "INSERT INTO reviews (user_id, seller_id, rating, comment) 
                VALUES (:user_id, :seller_id, :rating, :comment)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':seller_id' => $data['seller_id'],
            ':rating' => $data['rating'],
            ':comment' => $data['comment']
        ]);
    }

    /**
     * Get all reviews for a specific seller
     */
    public function getBySellerId($sellerId) {
        $sql = "SELECT r.*, u.full_name as reviewer_name, u.role as reviewer_role 
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.seller_id = :seller_id 
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':seller_id' => $sellerId]);
        return $stmt->fetchAll();
    }

    /**
     * Get average rating and counts for a seller
     */
    public function getStats($sellerId) {
        $sql = "SELECT COALESCE(AVG(rating), 0) as average_rating, COUNT(*) as total_reviews 
                FROM reviews 
                WHERE seller_id = :seller_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':seller_id' => $sellerId]);
        return $stmt->fetch();
    }
}
