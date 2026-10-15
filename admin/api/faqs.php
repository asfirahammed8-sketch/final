<?php
/**
 * DreamIT Admin Console - FAQs API Endpoint
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
        $stmt = $db->prepare("UPDATE faqs SET is_active = :act WHERE id = :id");
        $stmt->execute([':act' => $isActive, ':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'FAQ status updated']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM faqs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'FAQ deleted']);
        exit;
    }

    if ($action === 'save' || empty($action)) {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $question = trim($input['question'] ?? '');
        $answer = trim($input['answer'] ?? '');
        $category = trim($input['category'] ?? 'general');
        $courseId = !empty($input['course_id']) ? (int)$input['course_id'] : null;
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if (empty($question) || empty($answer)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Question and answer are required']);
            exit;
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE faqs SET question = :q, answer = :a, category = :cat, course_id = :cid, sort_order = :ord WHERE id = :id");
            $stmt->execute([':q' => $question, ':a' => $answer, ':cat' => $category, ':cid' => $courseId, ':ord' => $sortOrder, ':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'FAQ updated']);
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO faqs (question, answer, category, course_id, sort_order, is_active) VALUES (:q, :a, :cat, :cid, :ord, 1)");
            $stmt->execute([':q' => $question, ':a' => $answer, ':cat' => $category, ':cid' => $courseId, ':ord' => $sortOrder]);
            echo json_encode(['success' => true, 'message' => 'FAQ created']);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
