<?php
/**
 * DreamIT Admin Console - Departments API Endpoint
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
    if ($action === 'toggle') {
        $id = (int)($input['id'] ?? 0);
        $isActive = !empty($input['is_active']) ? 1 : 0;
        $stmt = $db->prepare("UPDATE departments SET is_active = :act WHERE id = :id");
        $stmt->execute([':act' => $isActive, ':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Status updated']);
        exit;
    }

    if ($action === 'save' || empty($action)) {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $name = trim($input['name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $sortOrder = (int)($input['sort_order'] ?? 0);
        $color = trim($input['color'] ?? '#0B2046');
        $desc = trim($input['description'] ?? '');
        $iconSvg = trim($input['icon_svg'] ?? '');

        if (empty($name) || empty($slug)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name and Slug are required']);
            exit;
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE departments SET name = :n, slug = :s, sort_order = :ord, color = :col, description = :d, icon_svg = :icon WHERE id = :id");
            $stmt->execute([':n' => $name, ':s' => $slug, ':ord' => $sortOrder, ':col' => $color, ':d' => $desc, ':icon' => $iconSvg, ':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Department updated']);
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO departments (name, slug, sort_order, color, description, icon_svg, is_active) VALUES (:n, :s, :ord, :col, :d, :icon, 1)");
            $stmt->execute([':n' => $name, ':s' => $slug, ':ord' => $sortOrder, ':col' => $color, ':d' => $desc, ':icon' => $iconSvg]);
            echo json_encode(['success' => true, 'message' => 'Department created']);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
