<?php
/**
 * DreamIT Admin Console - Settings API Endpoint
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!current_admin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$db = Database::getConnection();

try {
    $stmt = $db->prepare("
        INSERT INTO site_settings (setting_key, setting_value, setting_group)
        VALUES (:k, :v, 'general')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");

    foreach ($input as $key => $val) {
        if ($key === 'action') continue;
        $valString = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
        $stmt->execute([':k' => $key, ':v' => $valString]);
    }

    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
