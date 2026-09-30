<?php
/**
 * Admin Controller Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/TeacherProduct.php';
require_once __DIR__ . '/../models/School.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Force admin authentication for all actions except login
$isAdmin = isset($_SESSION['admin_id']) || ((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'));
if (!$isAdmin && $action !== 'login') {
    jsonResponse('error', 'Unauthorized. Administrator access required.');
}

if ($action === 'login') {
    handleLogin();
} else {
    $db = Database::getConnection();
    
    switch ($action) {
        case 'logout':
            handleLogout();
            break;
        case 'stats':
            handleStats($db);
            break;
        case 'users-list':
            handleUsersList();
            break;
        case 'user-status':
            handleUserStatus();
            break;
        case 'user-delete':
            handleUserDelete();
            break;
        case 'products-list':
            handleProductsList();
            break;
        case 'product-moderate':
            handleProductModerate();
            break;
        case 'school-save':
            handleSchoolSave();
            break;
        case 'school-delete':
            handleSchoolDelete();
            break;
        case 'reports-list':
            handleReportsList($db);
            break;
        case 'report-dismiss':
            handleReportDismiss($db);
            break;
        case 'commission-save':
            handleCommissionSave($db);
            break;
        default:
            jsonResponse('error', 'Invalid admin action specified.');
    }
}

/**
 * Handle Admin Login
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse('error', 'Please enter username and password.');
    }

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        jsonResponse('success', 'Admin login successful!');
    } else {
        jsonResponse('error', 'Invalid admin username or password.');
    }
}

/**
 * Handle Admin Logout
 */
function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    jsonResponse('success', 'Admin logged out.');
}

/**
 * Retrieve statistics and reports summaries for the Dashboard
 */
function handleStats($db) {
    $adminSchoolId = $_SESSION['admin_school_id'] ?? $_SESSION['school_id'] ?? null;
    
    // School info
    $schoolName = 'Todas las Instituciones';
    if ($adminSchoolId) {
        $st = $db->prepare("SELECT school_name FROM schools WHERE id = :sid");
        $st->execute([':sid' => $adminSchoolId]);
        $schoolName = $st->fetchColumn() ?: 'Institución Educativa';
    }

    if ($adminSchoolId) {
        $uStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id = :sid");
        $uStmt->execute([':sid' => $adminSchoolId]);
        $totalUsers = $uStmt->fetchColumn();

        $spStmt = $db->prepare("SELECT COUNT(*) FROM student_products WHERE school_id = :sid");
        $spStmt->execute([':sid' => $adminSchoolId]);
        $studentProds = $spStmt->fetchColumn();

        $tpStmt = $db->prepare("SELECT COUNT(*) FROM teacher_products WHERE school_id = :sid");
        $tpStmt->execute([':sid' => $adminSchoolId]);
        $teacherProds = $tpStmt->fetchColumn();

        $salesStmt = $db->prepare("SELECT COUNT(*) as total_sales, COALESCE(SUM(s.total_amount), 0) as total_volume, COALESCE(SUM(s.commission_amount), 0) as total_revenue 
                                   FROM sales s 
                                   LEFT JOIN student_products sp ON s.product_id = sp.id AND s.product_type = 'student'
                                   LEFT JOIN teacher_products tp ON s.product_id = tp.id AND s.product_type = 'teacher'
                                   WHERE sp.school_id = :sid1 OR tp.school_id = :sid2");
        $salesStmt->execute([':sid1' => $adminSchoolId, ':sid2' => $adminSchoolId]);
        $salesStats = $salesStmt->fetch();

        $totalSchools = 1;
    } else {
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalSchools = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
        $studentProds = $db->query("SELECT COUNT(*) FROM student_products")->fetchColumn();
        $teacherProds = $db->query("SELECT COUNT(*) FROM teacher_products")->fetchColumn();
        $salesStats = $db->query("SELECT COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_volume, COALESCE(SUM(commission_amount), 0) as total_revenue FROM sales")->fetch();
    }

    $totalProducts = $studentProds + $teacherProds;

    // Get current commission
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'commission_percentage'");
    $stmt->execute();
    $commission = floatval($stmt->fetchColumn() ?: 5.0);

    // Sales history chart mock/data
    $salesHistory = $db->query("SELECT DATE(created_at) as sale_date, COUNT(*) as count, SUM(commission_amount) as revenue FROM sales GROUP BY DATE(created_at) ORDER BY sale_date ASC LIMIT 10")->fetchAll();

    jsonResponse('success', 'Stats compiled.', [
        'school_name' => $schoolName,
        'school_id' => $adminSchoolId,
        'users_count' => $totalUsers,
        'schools_count' => $totalSchools,
        'products_count' => $totalProducts,
        'sales_count' => $salesStats['total_sales'] ?? 0,
        'sales_volume' => floatval($salesStats['total_volume'] ?? 0),
        'total_revenue' => floatval($salesStats['total_revenue'] ?? 0),
        'commission_percentage' => $commission,
        'sales_history' => $salesHistory
    ]);
}

/**
 * List all users
 */
function handleUsersList() {
    $adminSchoolId = $_SESSION['admin_school_id'] ?? $_SESSION['school_id'] ?? null;
    $userModel = new User();
    $users = $userModel->getAll($adminSchoolId);
    jsonResponse('success', 'Users list retrieved.', $users);
}

/**
 * Update user status (block/suspend)
 */
function handleUserStatus() {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');

    if ($userId <= 0 || !in_array($status, ['active', 'suspended', 'banned'])) {
        jsonResponse('error', 'Invalid status update parameters.');
    }

    $userModel = new User();
    if ($userModel->updateStatus($userId, $status)) {
        jsonResponse('success', "User status updated to '$status'.");
    } else {
        jsonResponse('error', 'Failed to update user status.');
    }
}

/**
 * Delete a user account
 */
function handleUserDelete() {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse('error', 'Invalid user ID.');
    }

    $userModel = new User();
    if ($userModel->delete($userId)) {
        jsonResponse('success', 'User deleted successfully.');
    } else {
        jsonResponse('error', 'Failed to delete user.');
    }
}

/**
 * List products for moderation
 */
function handleProductsList() {
    $adminSchoolId = $_SESSION['admin_school_id'] ?? $_SESSION['school_id'] ?? null;
    $productModel = new Product();
    $teacherModel = new TeacherProduct();

    $filters = ['admin_view' => true];
    if ($adminSchoolId) {
        $filters['school_id'] = $adminSchoolId;
    }

    $studentProducts = $productModel->list($filters);
    $teacherProducts = $teacherModel->list($filters);

    jsonResponse('success', 'Moderation listings retrieved.', [
        'student_products' => $studentProducts,
        'teacher_products' => $teacherProducts
    ]);
}

/**
 * Approve, reject or hide listing
 */
function handleProductModerate() {
    $id = intval($_POST['id'] ?? 0);
    $type = sanitizeInput($_POST['type'] ?? 'student'); // student or teacher
    $status = sanitizeInput($_POST['status'] ?? ''); // approved, hidden, or delete

    if ($id <= 0) {
        jsonResponse('error', 'Invalid listing ID.');
    }

    if ($status === 'delete') {
        if ($type === 'student') {
            $productModel = new Product();
            $success = $productModel->delete($id);
        } else {
            $teacherModel = new TeacherProduct();
            $success = $teacherModel->delete($id);
        }
        
        if ($success) {
            jsonResponse('success', 'Listing deleted.');
        } else {
            jsonResponse('error', 'Failed to delete listing.');
        }
    } else {
        if (!in_array($status, ['pending', 'approved', 'hidden'])) {
            jsonResponse('error', 'Invalid status update.');
        }

        if ($type === 'student') {
            $productModel = new Product();
            $success = $productModel->updateStatus($id, $status);
        } else {
            $teacherModel = new TeacherProduct();
            $success = $teacherModel->updateStatus($id, $status);
        }

        if ($success) {
            jsonResponse('success', "Listing status updated to '$status'.");
        } else {
            jsonResponse('error', 'Failed to update status.');
        }
    }
}

/**
 * Add or Edit a school details and custom theme branding
 */
function handleSchoolSave() {
    $id             = intval($_POST['id'] ?? 0);
    $schoolName     = sanitizeInput($_POST['school_name'] ?? '');
    $address        = sanitizeInput($_POST['address'] ?? '');
    $primaryColor   = sanitizeInput($_POST['primary_color'] ?? '#3b82f6');
    $secondaryColor = sanitizeInput($_POST['secondary_color'] ?? '#10b981');

    if (empty($schoolName) || empty($address)) {
        jsonResponse('error', 'School Name and Address are required.');
    }

    $schoolModel = new School();
    
    // Check files upload
    $logoPath = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $fileName = 'logo_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $fileName)) {
                $logoPath = 'uploads/' . $fileName;
            }
        }
    }

    $bgPath = '';
    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $fileName = 'bg_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['background_image']['tmp_name'], UPLOAD_DIR . $fileName)) {
                $bgPath = 'uploads/' . $fileName;
            }
        }
    }

    $data = [
        'school_name' => $schoolName,
        'address' => $address,
        'primary_color' => $primaryColor,
        'secondary_color' => $secondaryColor
    ];

    if ($id > 0) {
        // Edit mode
        $existing = $schoolModel->findById($id);
        if (!$existing) {
            jsonResponse('error', 'School not found.');
        }
        $data['logo'] = !empty($logoPath) ? $logoPath : $existing['logo'];
        $data['background_image'] = !empty($bgPath) ? $bgPath : $existing['background_image'];
        
        if ($schoolModel->update($id, $data)) {
            jsonResponse('success', 'School updated successfully.');
        } else {
            jsonResponse('error', 'Failed to update school.');
        }
    } else {
        // Create mode
        $data['logo'] = !empty($logoPath) ? $logoPath : 'assets/images/default_logo.png';
        $data['background_image'] = !empty($bgPath) ? $bgPath : 'assets/images/default_bg.jpg';

        if ($schoolModel->create($data)) {
            jsonResponse('success', 'School added successfully.');
        } else {
            jsonResponse('error', 'Failed to create school.');
        }
    }
}

/**
 * Remove a school
 */
function handleSchoolDelete() {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid school ID.');
    }

    $schoolModel = new School();
    if ($schoolModel->delete($id)) {
        jsonResponse('success', 'School deleted successfully.');
    } else {
        jsonResponse('error', 'Failed to delete school.');
    }
}

/**
 * Get all fraud safety reports
 */
function handleReportsList($db) {
    $sql = "SELECT r.*, 
                   u1.full_name as reporter_name, u1.email as reporter_email,
                   u2.full_name as reported_name, u2.email as reported_email, u2.status as reported_status
            FROM reports r
            JOIN users u1 ON r.reporter_id = u1.id
            JOIN users u2 ON r.reported_user_id = u2.id
            ORDER BY r.created_at DESC";
    $reports = $db->query($sql)->fetchAll();
    jsonResponse('success', 'Safety reports list retrieved.', $reports);
}

/**
 * Dismiss report safety alert
 */
function handleReportDismiss($db) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid report ID.');
    }

    $sql = "DELETE FROM reports WHERE id = :id";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([':id' => $id])) {
        jsonResponse('success', 'Report dismissed.');
    } else {
        jsonResponse('error', 'Failed to dismiss report.');
    }
}

/**
 * Adjust global commission percentage
 */
function handleCommissionSave($db) {
    $percentage = floatval($_POST['commission_percentage'] ?? -1);
    
    if ($percentage < 0 || $percentage >= 10) {
        jsonResponse('error', 'Commission must be positive and less than 10%.');
    }

    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('commission_percentage', :value)
            ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([':value' => strval($percentage)])) {
        jsonResponse('success', 'Commission settings updated successfully.');
    } else {
        jsonResponse('error', 'Failed to update commission settings.');
    }
}
