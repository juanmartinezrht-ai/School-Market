<?php
/**
 * School Controller Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/School.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$schoolModel = new School();

switch ($action) {
    case 'list':
        handleList($schoolModel);
        break;
    case 'get':
        handleGet($schoolModel);
        break;
    case 'search':
        handleSearch($schoolModel);
        break;
    default:
        jsonResponse('error', 'Invalid school action specified.');
}

/**
 * Handle listing all schools
 */
function handleList($schoolModel) {
    $schools = $schoolModel->getAll();
    jsonResponse('success', 'Schools retrieved successfully.', $schools);
}

/**
 * Handle fetching school by ID
 */
function handleGet($schoolModel) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse('error', 'Invalid school ID.');
    }
    
    $school = $schoolModel->findById($id);
    if ($school) {
        jsonResponse('success', 'School retrieved successfully.', $school);
    } else {
        jsonResponse('error', 'School not found.');
    }
}

/**
 * Handle searching schools
 */
function handleSearch($schoolModel) {
    $query = sanitizeInput($_GET['q'] ?? '');
    
    if (empty($query)) {
        // Return all if query is empty
        $schools = $schoolModel->getAll();
    } else {
        $schools = $schoolModel->search($query);
    }
    
    jsonResponse('success', 'Search results retrieved.', $schools);
}
