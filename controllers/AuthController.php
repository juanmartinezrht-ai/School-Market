<?php
/**
 * Auth Controller Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$userModel = new User();

// Route actions
switch ($action) {
    case 'register':
        handleRegister($userModel);
        break;
    case 'login':
        handleLogin($userModel);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check-session':
        handleCheckSession($userModel);
        break;
    case 'update-profile':
        handleUpdateProfile($userModel);
        break;
    default:
        jsonResponse('error', 'Invalid auth action specified.');
}

/**
 * Handle Registration
 */
function handleRegister($userModel) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $fullName   = sanitizeInput($_POST['full_name'] ?? '');
    $studentId  = sanitizeInput($_POST['student_id'] ?? '');
    $email      = sanitizeInput($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $schoolId   = intval($_POST['school_id'] ?? 0);
    $gradeLevel = sanitizeInput($_POST['grade_level'] ?? '');
    $groupName  = sanitizeInput($_POST['group_name'] ?? '');
    $role       = sanitizeInput($_POST['role'] ?? 'student');

    // Validation
    if (empty($fullName) || empty($email) || empty($password) || $schoolId <= 0) {
        jsonResponse('error', 'Please complete all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse('error', 'Please enter a valid email address.');
    }

    if (strlen($password) < 6) {
        jsonResponse('error', 'Password must be at least 6 characters long.');
    }

    if ($role === 'student' && empty($studentId)) {
        $studentId = 'EST-' . rand(1000, 9999);
    }

    // Check email uniqueness
    if ($userModel->emailExists($email)) {
        jsonResponse('error', 'This email address is already registered.');
    }

    $data = [
        'full_name' => $fullName,
        'student_id' => $role === 'student' ? $studentId : null,
        'email' => $email,
        'password' => $password,
        'school_id' => $schoolId,
        'grade_level' => $role === 'student' ? $gradeLevel : null,
        'group_name' => $role === 'student' ? $groupName : null,
        'role' => $role
    ];

    if ($userModel->create($data)) {
        jsonResponse('success', 'Registration successful! You can now log in.');
    } else {
        jsonResponse('error', 'Registration failed. Please contact support.');
    }
}

/**
 * Handle Login
 */
function handleLogin($userModel) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $schoolId = intval($_POST['school_id'] ?? 0);

    if (empty($email) || empty($password)) {
        jsonResponse('error', 'Please fill in all fields.');
    }

    $user = $userModel->findByCredentials($email, $schoolId);

    if (!$user) {
        jsonResponse('error', 'Invalid email or password.');
    }

    // Check status
    if ($user['status'] === 'suspended') {
        jsonResponse('error', 'Your account has been suspended. Please contact admin.');
    } elseif ($user['status'] === 'banned') {
        jsonResponse('error', 'Your account has been banned due to policy violations.');
    }

    // Verify Password
    if (password_verify($password, $user['password'])) {
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['school_id'] = $user['school_id'];

        if ($user['role'] === 'admin') {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['full_name'];
            $_SESSION['admin_school_id'] = $user['school_id'];
        }

        // Fetch school theme
        $db = Database::getConnection();
        $schoolStmt = $db->prepare("SELECT primary_color, secondary_color, logo, background_image FROM schools WHERE id = :sid");
        $schoolStmt->execute([':sid' => $user['school_id']]);
        $schoolTheme = $schoolStmt->fetch() ?: [];

        jsonResponse('success', 'Login successful!', [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'school_id' => $user['school_id'],
            'school_name' => $user['school_name'],
            'redirect' => $user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.html',
            'theme' => [
                'primary' => $schoolTheme['primary_color'] ?? '#3b82f6',
                'secondary' => $schoolTheme['secondary_color'] ?? '#10b981',
                'logo' => $schoolTheme['logo'] ?? 'default_logo.png',
                'background_image' => $schoolTheme['background_image'] ?? 'default_bg.jpg'
            ]
        ]);
    } else {
        jsonResponse('error', 'Invalid email or password.');
    }
}

/**
 * Handle Logout
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
    jsonResponse('success', 'Logged out successfully.');
}

/**
 * Handle Session Status Checks
 */
function handleCheckSession($userModel) {
    if (isset($_SESSION['user_id'])) {
        $user = $userModel->findById($_SESSION['user_id']);
        if ($user) {
            jsonResponse('success', 'Session active.', [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'school_id' => $user['school_id'],
                'school_name' => $user['school_name'],
                'grade_level' => $user['grade_level'],
                'group_name' => $user['group_name'],
                'student_id' => $user['student_id'],
                'avatar' => !empty($user['avatar']) ? $user['avatar'] : 'assets/images/default_avatar.svg',
                'theme' => [
                    'primary' => $user['primary_color'],
                    'secondary' => $user['secondary_color'],
                    'logo' => $user['logo']
                ]
            ]);
        }
    }
    jsonResponse('error', 'No active session.');
}

/**
 * Handle Profile Updates
 */
function handleUpdateProfile($userModel) {
    requireLogin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method.');
    }

    $id = $_SESSION['user_id'];
    $fullName   = sanitizeInput($_POST['full_name'] ?? '');
    $studentId  = sanitizeInput($_POST['student_id'] ?? '');
    $email      = sanitizeInput($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $gradeLevel = sanitizeInput($_POST['grade_level'] ?? '');
    $groupName  = sanitizeInput($_POST['group_name'] ?? '');

    if (empty($fullName) || empty($email)) {
        jsonResponse('error', 'Full Name and Email are required.');
    }

    // Check duplicate email
    if ($userModel->emailExists($email, $id)) {
        jsonResponse('error', 'This email is already in use by another user.');
    }

    $data = [
        'full_name' => $fullName,
        'student_id' => $_SESSION['user_role'] === 'student' ? $studentId : null,
        'email' => $email,
        'grade_level' => $_SESSION['user_role'] === 'student' ? $gradeLevel : null,
        'group_name' => $_SESSION['user_role'] === 'student' ? $groupName : null,
        'password' => $password
    ];

    // Handle avatar photo upload if provided
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            jsonResponse('error', 'Format of avatar is not supported. Use JPG, PNG, WEBP, or GIF.');
        }

        if ($file['size'] > MAX_IMAGE_SIZE) {
            jsonResponse('error', 'Avatar image exceeds the 5MB size limit.');
        }

        $avatarDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }

        $newFileName = uniqid('avatar_', true) . '.' . $ext;
        $destination = $avatarDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $data['avatar'] = 'uploads/avatars/' . $newFileName;
        }
    }

    if ($userModel->update($id, $data)) {
        $_SESSION['user_name'] = $fullName;
        if (isset($data['avatar'])) {
            $_SESSION['user_avatar'] = $data['avatar'];
        }
        jsonResponse('success', 'Profile updated successfully.', [
            'avatar' => $data['avatar'] ?? null
        ]);
    } else {
        jsonResponse('error', 'Failed to update profile.');
    }
}
