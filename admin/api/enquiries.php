<?php
/**
 * DreamIT Admin Console - Enquiries API Endpoint
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!current_admin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? '');
$db = Database::getConnection();

try {
    if ($action === 'update-status') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? 'new';
        $allowed = ['new', 'in_progress', 'contacted', 'enrolled', 'closed'];
        if (!in_array($status, $allowed)) $status = 'new';

        $stmt = $db->prepare("UPDATE enquiries SET status = :st WHERE id = :id");
        $stmt->execute([':st' => $status, ':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Status updated']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM enquiries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Enquiry deleted']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
