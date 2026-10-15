<?php
/**
 * DreamIT Admin Console - Campus Gallery API
 * Production-ready CRUD handling for gallery_items table
 */

require_once __DIR__ . '/../config.php';
require_once ADMIN_PATH . '/includes/auth-guard.php';

header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? '';
$db = Database::getConnection();

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_admin_csrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired.']);
    exit;
}

try {
    switch ($action) {
        case 'save':
            $id         = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $category   = trim($_POST['category'] ?? 'labs');
            $title      = trim($_POST['title'] ?? '');
            $altText    = trim($_POST['alt_text'] ?? '');
            $imageUrl   = trim($_POST['image_url'] ?? '');
            $mediaType  = trim($_POST['media_type'] ?? 'image');
            $videoUrl   = trim($_POST['video_url'] ?? '');
            $color      = trim($_POST['color'] ?? '#1e3a5f');
            $isActive   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $sortOrder  = (int)($_POST['sort_order'] ?? 0);

            if (empty($title)) {
                throw new Exception("Title is required for the campus gallery photo.");
            }
            if ($mediaType === 'image' && empty($imageUrl)) {
                throw new Exception("Please upload or provide an image URL for the gallery item.");
            }
            if ($mediaType === 'video' && empty($videoUrl)) {
                throw new Exception("Please provide a video URL or YouTube embed link.");
            }
            if (empty($altText)) {
                $altText = $title;
            }

            if ($id) {
                // Update
                $stmt = $db->prepare("
                    UPDATE gallery_items SET
                        category = :category,
                        title = :title,
                        alt_text = :alt_text,
                        image_url = :image_url,
                        media_type = :media_type,
                        video_url = :video_url,
                        color = :color,
                        is_active = :is_active,
                        sort_order = :sort_order,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->execute([
                    'category'   => $category,
                    'title'      => $title,
                    'alt_text'   => $altText,
                    'image_url'  => $imageUrl,
                    'media_type' => $mediaType,
                    'video_url'  => $videoUrl,
                    'color'      => $color,
                    'is_active'  => $isActive,
                    'sort_order' => $sortOrder,
                    'id'         => $id
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Campus gallery item updated successfully!',
                    'id'      => $id
                ]);
            } else {
                // Create
                $stmt = $db->prepare("
                    INSERT INTO gallery_items
                        (category, title, alt_text, image_url, media_type, video_url, color, is_active, sort_order)
                    VALUES
                        (:category, :title, :alt_text, :image_url, :media_type, :video_url, :color, :is_active, :sort_order)
                ");
                $stmt->execute([
                    'category'   => $category,
                    'title'      => $title,
                    'alt_text'   => $altText,
                    'image_url'  => $imageUrl,
                    'media_type' => $mediaType,
                    'video_url'  => $videoUrl,
                    'color'      => $color,
                    'is_active'  => $isActive,
                    'sort_order' => $sortOrder
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'New campus gallery photo published!',
                    'id'      => (int)$db->lastInsertId()
                ]);
            }
            break;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid item ID.");

            $stmt = $db->prepare("UPDATE gallery_items SET is_active = NOT is_active WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $newStatus = (int)$db->query("SELECT is_active FROM gallery_items WHERE id = {$id}")->fetchColumn();

            echo json_encode([
                'success'   => true,
                'message'   => 'Gallery item status updated.',
                'is_active' => $newStatus
            ]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid item ID.");

            $stmt = $db->prepare("DELETE FROM gallery_items WHERE id = :id");
            $stmt->execute(['id' => $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Gallery photo removed successfully.'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
