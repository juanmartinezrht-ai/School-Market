<?php
/**
 * Product Controller Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Review.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$productModel = new Product();

switch ($action) {
    case 'list':
        handleList($productModel);
        break;
    case 'get':
        handleGet($productModel);
        break;
    case 'create':
        handleCreate($productModel);
        break;
    case 'delete':
        handleDelete($productModel);
        break;
    case 'my-listings':
        handleMyListings($productModel);
        break;
    case 'my-transactions':
        handleMyTransactions();
        break;
    case 'seller-reviews':
        handleSellerReviews();
        break;
    case 'buy':
        handleBuy($productModel);
        break;
    case 'submit-review':
        handleSubmitReview();
        break;
    case 'report-user':
        handleReportUser();
        break;
    default:
        jsonResponse('error', 'Invalid product action specified.');
}

/**
 * Fetch reviews and statistics for a specific seller
 */
function handleSellerReviews() {
    $sellerId = intval($_GET['seller_id'] ?? 0);
    if ($sellerId <= 0) {
        jsonResponse('error', 'Invalid seller ID.');
    }

    $reviewModel = new Review();
    $reviews = $reviewModel->getBySellerId($sellerId);
    $stats = $reviewModel->getStats($sellerId);

    jsonResponse('success', 'Seller reviews retrieved.', [
        'reviews' => $reviews,
        'average_rating' => floatval($stats['average_rating']),
        'total_reviews' => intval($stats['total_reviews'])
    ]);
}

/**
 * Get current user's transactions (sales and purchases)
 */
function handleMyTransactions() {
    requireLogin();
    $userId = $_SESSION['user_id'];
    $db = Database::getConnection();

    // Fetch purchases
    $stmt1 = $db->prepare("
        SELECT s.*, COALESCE(p.title, tp.title) as product_title, u.full_name as seller_name 
        FROM sales s
        LEFT JOIN student_products p ON s.product_id = p.id AND s.product_type = 'student'
        LEFT JOIN teacher_products tp ON s.product_id = tp.id AND s.product_type = 'teacher'
        LEFT JOIN users u ON s.seller_id = u.id
        WHERE s.buyer_id = :user_id
        ORDER BY s.created_at DESC
    ");
    $stmt1->execute([':user_id' => $userId]);
    $purchases = $stmt1->fetchAll();

    // Fetch sales
    $stmt2 = $db->prepare("
        SELECT s.*, COALESCE(p.title, tp.title) as product_title, u.full_name as buyer_name 
        FROM sales s
        LEFT JOIN student_products p ON s.product_id = p.id AND s.product_type = 'student'
        LEFT JOIN teacher_products tp ON s.product_id = tp.id AND s.product_type = 'teacher'
        LEFT JOIN users u ON s.buyer_id = u.id
        WHERE s.seller_id = :user_id
        ORDER BY s.created_at DESC
    ");
    $stmt2->execute([':user_id' => $userId]);
    $sales = $stmt2->fetchAll();

    jsonResponse('success', 'Transactions retrieved.', [
        'purchases' => $purchases,
        'sales' => $sales
    ]);
}

/**
 * Handle listing student products with filters
 */
function handleList($productModel) {
    $filters = [
        'school_id'   => intval($_GET['school_id'] ?? 0),
        'category'    => sanitizeInput($_GET['category'] ?? ''),
        'condition'   => sanitizeInput($_GET['condition'] ?? ''),
        'grade_level' => sanitizeInput($_GET['grade_level'] ?? ''),
        'group_name'  => sanitizeInput($_GET['group_name'] ?? ''),
        'search'      => sanitizeInput($_GET['search'] ?? ''),
        'price_min'   => (isset($_GET['price_min']) && $_GET['price_min'] !== '') ? floatval($_GET['price_min']) : '',
        'price_max'   => (isset($_GET['price_max']) && $_GET['price_max'] !== '') ? floatval($_GET['price_max']) : '',
    ];
    
    // Filter out empty params
    $filters = array_filter($filters, function($value) {
        return $value !== '' && $value !== 0;
    });

    $products = $productModel->list($filters);
    jsonResponse('success', 'Products retrieved.', $products);
}

/**
 * Get product details by ID (including seller reviews)
 */
function handleGet($productModel) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $productModel->findById($id);
    if (!$product) {
        jsonResponse('error', 'Product not found.');
    }

    // Get reviews for this seller
    $reviewModel = new Review();
    $sellerId = $product['user_id'];
    $reviews = $reviewModel->getBySellerId($sellerId);
    $stats = $reviewModel->getStats($sellerId);

    $product['seller_reviews'] = $reviews;
    $product['seller_rating'] = floatval($stats['average_rating']);
    $product['seller_review_count'] = intval($stats['total_reviews']);

    jsonResponse('success', 'Product details retrieved.', $product);
}

/**
 * Handle listing creation with image uploading
 */
function handleCreate($productModel) {
    requireLogin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $title       = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category    = sanitizeInput($_POST['category'] ?? '');
    $condition   = sanitizeInput($_POST['condition'] ?? 'new');
    if (empty($condition)) {
        $condition = 'new';
    }
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = max(1, intval($_POST['stock'] ?? 1));
    $schoolId    = $_SESSION['school_id'];
    $userId      = $_SESSION['user_id'];

    if (empty($title) || empty($description) || empty($category) || $price <= 0) {
        jsonResponse('error', 'Please fill in all listing details.');
    }

    // Ensure uploads directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $uploadedImages = [];
    
    // Handle multiple file upload (up to 5 images)
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $fileCount = count($files['name']);
        
        if ($fileCount > 5) {
            jsonResponse('error', 'You can upload a maximum of 5 images.');
        }

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $fileTmp  = $files['tmp_name'][$i];
            
            // Check size
            if ($fileSize > MAX_IMAGE_SIZE) {
                jsonResponse('error', "Image '$fileName' exceeds the 5MB size limit.");
            }

            // Check file type
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                jsonResponse('error', "Format of '$fileName' is not supported. Use JPG, PNG, WEBP, or GIF.");
            }

            // Generate unique file name
            $newFileName = uniqid('prod_', true) . '.' . $ext;
            $destination = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $uploadedImages[] = 'uploads/' . $newFileName;
            }
        }
    }

    // If no images uploaded, use a default image placeholder
    if (empty($uploadedImages)) {
        $uploadedImages[] = 'assets/images/default_product.png';
    }

    $data = [
        'user_id' => $userId,
        'school_id' => $schoolId,
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'condition' => $condition,
        'price' => $price,
        'stock' => $stock,
        'image' => json_encode($uploadedImages)
    ];

    if ($productModel->create($data)) {
        jsonResponse('success', 'Your listing has been submitted for approval!');
    } else {
        jsonResponse('error', 'Failed to publish listing.');
    }
}

/**
 * Delete a listing (must be owner)
 */
function handleDelete($productModel) {
    requireLogin();

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $productModel->findById($id);
    if (!$product) {
        jsonResponse('error', 'Listing not found.');
    }

    // Must be the owner to delete
    if ($product['user_id'] !== $_SESSION['user_id']) {
        jsonResponse('error', 'You do not have permission to delete this listing.');
    }

    if ($productModel->delete($id)) {
        // Delete physical files if they exist and aren't default images
        $images = json_decode($product['image'], true);
        if ($images) {
            foreach ($images as $img) {
                if (strpos($img, 'uploads/') !== false && file_exists(dirname(__DIR__) . '/' . $img)) {
                    @unlink(dirname(__DIR__) . '/' . $img);
                }
            }
        }
        jsonResponse('success', 'Listing deleted successfully.');
    } else {
        jsonResponse('error', 'Failed to delete listing.');
    }
}

/**
 * List the current user's products
 */
function handleMyListings($productModel) {
    requireLogin();
    $listings = $productModel->getByUserId($_SESSION['user_id']);
    jsonResponse('success', 'My listings retrieved.', $listings);
}

/**
 * Handle simulated transaction checkout with commission log
 */
function handleBuy($productModel) {
    requireLogin();

    $productId = intval($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $productModel->findById($productId);
    if (!$product) {
        jsonResponse('error', 'Product not found.');
    }

    if ($product['user_id'] === $_SESSION['user_id']) {
        jsonResponse('error', 'You cannot purchase your own product.');
    }

    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $currentStock = intval($product['stock'] ?? 1);

    if ($currentStock < $quantity) {
        jsonResponse('error', "Lo sentimos, solo quedan {$currentStock} unidad(es) disponible(s).");
    }

    // Fetch commission percentage from settings
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'commission_percentage'");
    $stmt->execute();
    $commissionPct = floatval($stmt->fetchColumn() ?: 5.0);

    $unitPrice = floatval($product['price']);
    $totalAmount = $unitPrice * $quantity;
    $commissionAmount = $totalAmount * ($commissionPct / 100);
    $sellerAmount = $totalAmount - $commissionAmount;

    $buyerId = $_SESSION['user_id'];
    $sellerId = $product['user_id'];

    // Insert transaction log
    $sql = "INSERT INTO sales (buyer_id, seller_id, product_id, product_type, total_amount, commission_amount, seller_amount) 
            VALUES (:buyer_id, :seller_id, :product_id, 'student', :total_amount, :commission_amount, :seller_amount)";
    $stmt = $db->prepare($sql);
    
    $success = $stmt->execute([
        ':buyer_id' => $buyerId,
        ':seller_id' => $sellerId,
        ':product_id' => $productId,
        ':total_amount' => $totalAmount,
        ':commission_amount' => $commissionAmount,
        ':seller_amount' => $sellerAmount
    ]);

    if ($success) {
        // Decrement product stock; will automatically set to 'hidden' if remaining <= 0
        $remainingStock = $productModel->decrementStock($productId, $quantity);

        // Compile receipt information
        $receipt = [
            'transaction_id' => $db->lastInsertId(),
            'buyer_name' => $_SESSION['user_name'],
            'seller_name' => $product['seller_name'],
            'product_title' => $product['title'],
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'price' => $totalAmount,
            'remaining_stock' => $remainingStock,
            'commission_rate' => $commissionPct . '%',
            'commission_charged' => $commissionAmount,
            'seller_payment' => $sellerAmount,
            'date' => date('Y-m-d H:i:s'),
            'school_name' => $product['school_name']
        ];
        
        jsonResponse('success', 'Purchase transaction processed successfully!', $receipt);
    } else {
        jsonResponse('error', 'Purchase checkout failed.');
    }
}

/**
 * Handle seller feedback submission
 */
function handleSubmitReview() {
    requireLogin();

    $sellerId = intval($_POST['seller_id'] ?? 0);
    $rating    = intval($_POST['rating'] ?? 0);
    $comment   = sanitizeInput($_POST['comment'] ?? '');

    if ($sellerId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        jsonResponse('error', 'Please fill in a valid rating (1-5 stars) and a written comment.');
    }

    if ($sellerId === $_SESSION['user_id']) {
        jsonResponse('error', 'You cannot rate yourself.');
    }

    $reviewModel = new Review();
    $data = [
        'user_id' => $_SESSION['user_id'],
        'seller_id' => $sellerId,
        'rating' => $rating,
        'comment' => $comment
    ];

    if ($reviewModel->create($data)) {
        jsonResponse('success', 'Review submitted successfully!');
    } else {
        jsonResponse('error', 'Failed to submit review.');
    }
}

/**
 * Report user for fraud/safety check
 */
function handleReportUser() {
    requireLogin();

    $reportedUserId = intval($_POST['reported_user_id'] ?? 0);
    $reason         = sanitizeInput($_POST['reason'] ?? '');

    if ($reportedUserId <= 0 || empty($reason)) {
        jsonResponse('error', 'Please specify a user and describe the issue.');
    }

    if ($reportedUserId === $_SESSION['user_id']) {
        jsonResponse('error', 'You cannot report yourself.');
    }

    $db = Database::getConnection();
    $sql = "INSERT INTO reports (reporter_id, reported_user_id, reason) VALUES (:reporter_id, :reported_user_id, :reason)";
    $stmt = $db->prepare($sql);
    
    $success = $stmt->execute([
        ':reporter_id' => $_SESSION['user_id'],
        ':reported_user_id' => $reportedUserId,
        ':reason' => $reason
    ]);

    if ($success) {
        jsonResponse('success', 'The user has been reported to administration. Thank you for keeping School Market safe!');
    } else {
        jsonResponse('error', 'Failed to submit safety report.');
    }
}
