<?php
/**
 * Teacher Product Controller Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/TeacherProduct.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$teacherProductModel = new TeacherProduct();

switch ($action) {
    case 'list':
        handleList($teacherProductModel);
        break;
    case 'get':
        handleGet($teacherProductModel);
        break;
    case 'create':
        handleCreate($teacherProductModel);
        break;
    case 'delete':
        handleDelete($teacherProductModel);
        break;
    case 'buy':
        handleBuy($teacherProductModel);
        break;
    case 'my-listings':
        handleMyListings($teacherProductModel);
        break;
    default:
        jsonResponse('error', 'Invalid teacher product action specified.');
}

/**
 * List the current teacher's published resources
 */
function handleMyListings($teacherProductModel) {
    requireLogin();
    $listings = $teacherProductModel->getByTeacherName($_SESSION['user_name'], $_SESSION['school_id']);
    jsonResponse('success', 'Teacher resources retrieved.', $listings);
}

/**
 * List teacher products with advanced filters
 */
function handleList($teacherProductModel) {
    $filters = [
        'school_id'  => intval($_GET['school_id'] ?? 0),
        'grade'      => sanitizeInput($_GET['grade'] ?? ''),
        'group_name' => sanitizeInput($_GET['group_name'] ?? ''),
        'subject'    => sanitizeInput($_GET['subject'] ?? ''),
        'search'     => sanitizeInput($_GET['search'] ?? ''),
        'price_min'  => (isset($_GET['price_min']) && $_GET['price_min'] !== '') ? floatval($_GET['price_min']) : '',
        'price_max'  => (isset($_GET['price_max']) && $_GET['price_max'] !== '') ? floatval($_GET['price_max']) : '',
    ];

    // Filter out empty params
    $filters = array_filter($filters, function($value) {
        return $value !== '' && $value !== 0;
    });

    $products = $teacherProductModel->list($filters);
    jsonResponse('success', 'Teacher products retrieved.', $products);
}

/**
 * Fetch a single teacher product
 */
function handleGet($teacherProductModel) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $teacherProductModel->findById($id);
    if ($product) {
        jsonResponse('success', 'Teacher product details retrieved.', $product);
    } else {
        jsonResponse('error', 'Teacher product not found.');
    }
}

/**
 * Create a teacher product upload
 */
function handleCreate($teacherProductModel) {
    requireLogin();

    if ($_SESSION['user_role'] !== 'teacher') {
        jsonResponse('error', 'Only registered teachers can publish in this section.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $title       = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $subject     = sanitizeInput($_POST['subject'] ?? '');
    if (empty($subject)) {
        $subject = sanitizeInput($_POST['category'] ?? 'Material Académico');
    }
    $grade       = sanitizeInput($_POST['grade'] ?? '');
    if (empty($grade)) {
        $grade = 'Todos';
    }
    $groupName   = sanitizeInput($_POST['group_name'] ?? '');
    if (empty($groupName)) {
        $groupName = 'General';
    }
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = max(1, intval($_POST['stock'] ?? 1));
    $schoolId    = $_SESSION['school_id'];
    $teacherName = $_SESSION['user_name']; // Take teacher name directly from profile session

    if (empty($title) || empty($description) || $price < 0) {
        jsonResponse('error', 'Por favor completa todos los campos requeridos (Título, Descripción y Precio).');
    }

    // Ensure uploads directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $uploadedImages = [];
    
    // Handle image uploads
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

            if ($fileSize > MAX_IMAGE_SIZE) {
                jsonResponse('error', "Image '$fileName' exceeds the 5MB size limit.");
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                jsonResponse('error', "Format of '$fileName' is not supported.");
            }

            $newFileName = uniqid('teach_', true) . '.' . $ext;
            $destination = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $uploadedImages[] = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($uploadedImages)) {
        $uploadedImages[] = 'assets/images/default_teacher_product.png';
    }

    $data = [
        'school_id' => $schoolId,
        'teacher_name' => $teacherName,
        'subject' => $subject,
        'grade' => $grade,
        'group_name' => $groupName,
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'stock' => $stock,
        'image' => json_encode($uploadedImages)
    ];

    if ($teacherProductModel->create($data)) {
        jsonResponse('success', '¡Tu material o recurso docente ha sido publicado exitosamente!');
    } else {
        jsonResponse('error', 'No se pudo publicar el recurso académico. Por favor intenta de nuevo.');
    }
}

/**
 * Delete teacher product listing
 */
function handleDelete($teacherProductModel) {
    requireLogin();

    if ($_SESSION['user_role'] !== 'teacher') {
        jsonResponse('error', 'Only teachers can delete these resources.');
    }

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $teacherProductModel->findById($id);
    if (!$product) {
        jsonResponse('error', 'Resource not found.');
    }

    // Security check: teacher name must match or it must belong to their school
    if ($product['teacher_name'] !== $_SESSION['user_name'] || $product['school_id'] !== $_SESSION['school_id']) {
        jsonResponse('error', 'You do not have permission to delete this listing.');
    }

    if ($teacherProductModel->delete($id)) {
        $images = json_decode($product['image'], true);
        if ($images) {
            foreach ($images as $img) {
                if (strpos($img, 'uploads/') !== false && file_exists(dirname(__DIR__) . '/' . $img)) {
                    @unlink(dirname(__DIR__) . '/' . $img);
                }
            }
        }
        jsonResponse('success', 'Resource deleted successfully.');
    } else {
        jsonResponse('error', 'Failed to delete resource.');
    }
}

/**
 * Handle checkout of teacher resource
 */
function handleBuy($teacherProductModel) {
    requireLogin();

    $productId = intval($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        jsonResponse('error', 'Invalid product ID.');
    }

    $product = $teacherProductModel->findById($productId);
    if (!$product) {
        jsonResponse('error', 'Resource not found.');
    }

    $db = Database::getConnection();
    
    // Fetch commission rate
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'commission_percentage'");
    $stmt->execute();
    $commissionPct = floatval($stmt->fetchColumn() ?: 5.0);

    $totalAmount = floatval($product['price']);
    $commissionAmount = $totalAmount * ($commissionPct / 100);
    $sellerAmount = $totalAmount - $commissionAmount;

    $buyerId = $_SESSION['user_id'];
    
    // To record in sales table: since teacher products do not strictly link to a user_id, 
    // we can search for a user whose full_name matches teacher_name in the same school.
    $stmtUser = $db->prepare("SELECT id FROM users WHERE full_name = :name AND school_id = :school_id AND role = 'teacher' LIMIT 1");
    $stmtUser->execute([':name' => $product['teacher_name'], ':school_id' => $product['school_id']]);
    $sellerId = $stmtUser->fetchColumn();
    
    if (!$sellerId) {
        // Fallback: system or default ID, or use 1 if matching teacher account wasn't found
        $sellerId = 1; 
    }

    if ($sellerId == $buyerId) {
        jsonResponse('error', 'You cannot purchase your own resources.');
    }

    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $currentStock = intval($product['stock'] ?? 1);

    if ($currentStock < $quantity) {
        jsonResponse('error', "Lo sentimos, solo quedan {$currentStock} unidad(es) disponible(s).");
    }

    $unitPrice = floatval($product['price']);
    $totalAmount = $unitPrice * $quantity;
    $commissionAmount = $totalAmount * ($commissionPct / 100);
    $sellerAmount = $totalAmount - $commissionAmount;

    $sql = "INSERT INTO sales (buyer_id, seller_id, product_id, product_type, total_amount, commission_amount, seller_amount) 
            VALUES (:buyer_id, :seller_id, :product_id, 'teacher', :total_amount, :commission_amount, :seller_amount)";
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
        // Decrement stock; if stock reaches 0, decrementStock automatically marks status as 'hidden'
        $remainingStock = $teacherProductModel->decrementStock($productId, $quantity);

        $receipt = [
            'transaction_id' => $db->lastInsertId(),
            'buyer_name' => $_SESSION['user_name'],
            'seller_name' => $product['teacher_name'],
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
