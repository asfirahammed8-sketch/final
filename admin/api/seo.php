<?php
/**
 * DreamIT Admin Console - SEO API Endpoint
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
    $id = (int)($input['id'] ?? 0);
    $seoTitle = trim($input['seo_title'] ?? '');
    $metaDesc = trim($input['meta_description'] ?? '');
    $ogTitle = trim($input['og_title'] ?? '');
    $ogImage = trim($input['og_image'] ?? '');
    $robots = trim($input['robots'] ?? 'index, follow');

    if (!$id) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Page ID is required']);
        exit;
    }

    $stmt = $db->prepare("UPDATE page_seo SET
        seo_title = :st, meta_description = :md, og_title = :ot,
        og_image = :oi, robots = :rb, updated_at = NOW()
        WHERE id = :id");
    $stmt->execute([
        ':st' => $seoTitle, ':md' => $metaDesc, ':ot' => $ogTitle,
        ':oi' => $ogImage, ':rb' => $robots, ':id' => $id
    ]);

    echo json_encode(['success' => true, 'message' => 'SEO metadata updated successfully']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
