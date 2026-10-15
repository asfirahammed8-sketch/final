<?php
/**
 * DreamIT Admin Console - Student Work & Portfolio API Engine
 * Manages projects, multi-image carousel gallery (project_images), and project SEO (project_seo)
 */

require_once __DIR__ . '/../config.php';
require_once ADMIN_PATH . '/includes/auth-guard.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? '');
$db = Database::getConnection();

// Verify CSRF for mutating actions
if (in_array($action, ['save', 'delete', 'toggle'])) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
    if (!verify_admin_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF verification failed or session expired.']);
        exit;
    }
}

try {
    // 1. GET PROJECT DETAILS WITH CAROUSEL IMAGES & SEO
    if ($action === 'get-details') {
        $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));
        if (!$id) {
            throw new Exception("Invalid project ID.");
        }

        $pStmt = $db->prepare("SELECT * FROM projects WHERE id = :id LIMIT 1");
        $pStmt->execute([':id' => $id]);
        $project = $pStmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) {
            throw new Exception("Project not found.");
        }

        // Fetch carousel images
        $imgStmt = $db->prepare("SELECT id, image_url, image_order, caption FROM project_images WHERE project_id = :pid ORDER BY image_order ASC, id ASC");
        $imgStmt->execute([':pid' => $id]);
        $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch SEO
        $seoStmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords, og_image FROM project_seo WHERE project_id = :pid LIMIT 1");
        $seoStmt->execute([':pid' => $id]);
        $seo = $seoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        echo json_encode([
            'success' => true,
            'project' => $project,
            'images'  => $images,
            'seo'     => $seo
        ]);
        exit;
    }

    // 2. DELETE PROJECT
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) throw new Exception("Invalid project ID.");

        // Cascade deletes project_images & project_seo automatically
        $stmt = $db->prepare("DELETE FROM projects WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Project and its gallery removed successfully.']);
        exit;
    }

    // 3. SAVE PROJECT (CREATE OR UPDATE)
    if ($action === 'save' || empty($action)) {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $title = trim($input['title'] ?? '');
        $studentName = trim($input['student_name'] ?? '');
        $courseId = !empty($input['course_id']) ? (int)$input['course_id'] : null;
        $department = trim($input['department'] ?? 'graphic');
        $category = trim($input['category'] ?? 'Graphic Design');
        $liveUrl = trim($input['live_url'] ?? '');
        $githubUrl = trim($input['github_url'] ?? '');
        $coverImage = trim($input['cover_image'] ?? '');
        $briefDesc = trim($input['brief_description'] ?? ($input['short_description'] ?? ''));
        $description = trim($input['description'] ?? '');
        $isFeatured = !empty($input['is_featured']) ? 1 : 0;
        $sortOrder = (int)($input['sort_order'] ?? 0);
        $status = ($input['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        if (empty($title)) {
            throw new Exception("Project Title is required.");
        }
        if (empty($studentName)) {
            throw new Exception("Student Name is required.");
        }

        // If course is selected, auto-fill department and category if empty
        if ($courseId) {
            $cStmt = $db->prepare("SELECT department, category FROM courses WHERE id = :cid LIMIT 1");
            $cStmt->execute([':cid' => $courseId]);
            if ($cRow = $cStmt->fetch(PDO::FETCH_ASSOC)) {
                if (empty($department) || $department === 'graphic') $department = $cRow['department'];
                if (empty($category) || $category === 'Graphic Design') $category = $cRow['category'];
            }
        }

        if (empty($briefDesc) && !empty($description)) {
            $briefDesc = mb_substr(strip_tags($description), 0, 250);
        }

        if ($id) {
            // Update projects table
            $stmt = $db->prepare("
                UPDATE projects SET
                    title = :title,
                    course_id = :cid,
                    student_name = :sname,
                    department = :dept,
                    category = :cat,
                    live_url = :lurl,
                    github_url = :ghurl,
                    cover_image = :cover,
                    brief_description = :bdesc,
                    short_description = :sdesc,
                    description = :desc,
                    featured = :feat,
                    is_featured = :isfeat,
                    sort_order = :sort_order,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                ':title'      => $title,
                ':cid'        => $courseId,
                ':sname'      => $studentName,
                ':dept'       => $department,
                ':cat'        => $category,
                ':lurl'       => $liveUrl,
                ':ghurl'      => $githubUrl,
                ':cover'      => $coverImage,
                ':bdesc'      => $briefDesc,
                ':sdesc'      => $briefDesc,
                ':desc'       => $description,
                ':feat'       => $isFeatured,
                ':isfeat'     => $isFeatured,
                ':sort_order' => $sortOrder,
                ':status'     => $status,
                ':id'         => $id
            ]);
            $projectId = $id;
        } else {
            // Create slug
            $slugBase = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
            $slugBase = trim($slugBase, '-');
            if (empty($slugBase)) $slugBase = 'project';
            $slug = $slugBase . '-' . time();

            $stmt = $db->prepare("
                INSERT INTO projects (
                    title, slug, course_id, student_name, department, category,
                    live_url, github_url, cover_image, brief_description, short_description, description,
                    featured, is_featured, sort_order, status
                ) VALUES (
                    :title, :slug, :cid, :sname, :dept, :cat,
                    :lurl, :ghurl, :cover, :bdesc, :sdesc, :desc,
                    :feat, :isfeat, :sort_order, :status
                )
            ");
            $stmt->execute([
                ':title'      => $title,
                ':slug'       => $slug,
                ':cid'        => $courseId,
                ':sname'      => $studentName,
                ':dept'       => $department,
                ':cat'        => $category,
                ':lurl'       => $liveUrl,
                ':ghurl'      => $githubUrl,
                ':cover'      => $coverImage,
                ':bdesc'      => $briefDesc,
                ':sdesc'      => $briefDesc,
                ':desc'       => $description,
                ':feat'       => $isFeatured,
                ':isfeat'     => $isFeatured,
                ':sort_order' => $sortOrder,
                ':status'     => $status
            ]);
            $projectId = (int)$db->lastInsertId();
        }

        // 4. Synchronize Carousel Gallery (project_images table)
        if (isset($input['gallery_images']) && is_array($input['gallery_images'])) {
            // Wipe existing images and re-insert in order
            $delImg = $db->prepare("DELETE FROM project_images WHERE project_id = :pid");
            $delImg->execute([':pid' => $projectId]);

            $insImg = $db->prepare("
                INSERT INTO project_images (project_id, image_url, image_order, caption)
                VALUES (:pid, :img_url, :img_order, :caption)
            ");

            foreach ($input['gallery_images'] as $order => $img) {
                $url = trim($img['image_url'] ?? '');
                if (empty($url)) continue;
                $caption = trim($img['caption'] ?? $title);
                $insImg->execute([
                    ':pid'       => $projectId,
                    ':img_url'   => $url,
                    ':img_order' => (int)($img['image_order'] ?? $order),
                    ':caption'   => $caption
                ]);
            }
        }

        // 5. Synchronize Project SEO (project_seo table)
        $metaTitle = trim($input['meta_title'] ?? '');
        $metaDesc  = trim($input['meta_description'] ?? '');
        $metaKeys  = trim($input['meta_keywords'] ?? '');

        if (!empty($metaTitle) || !empty($metaDesc) || !empty($metaKeys)) {
            $seoCheck = $db->prepare("SELECT id FROM project_seo WHERE project_id = :pid");
            $seoCheck->execute([':pid' => $projectId]);

            if ($seoCheck->fetchColumn()) {
                $seoUp = $db->prepare("
                    UPDATE project_seo SET
                        meta_title = :mtitle,
                        meta_description = :mdesc,
                        meta_keywords = :mkeys,
                        og_image = :ogimg,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE project_id = :pid
                ");
                $seoUp->execute([
                    ':mtitle' => $metaTitle,
                    ':mdesc'  => $metaDesc,
                    ':mkeys'  => $metaKeys,
                    ':ogimg'  => $coverImage,
                    ':pid'    => $projectId
                ]);
            } else {
                $seoIns = $db->prepare("
                    INSERT INTO project_seo (project_id, meta_title, meta_description, meta_keywords, og_image)
                    VALUES (:pid, :mtitle, :mdesc, :mkeys, :ogimg)
                ");
                $seoIns->execute([
                    ':pid'    => $projectId,
                    ':mtitle' => $metaTitle,
                    ':mdesc'  => $metaDesc,
                    ':mkeys'  => $metaKeys,
                    ':ogimg'  => $coverImage
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => $id ? 'Student project updated successfully!' : 'New student project published!',
            'id'      => $projectId
        ]);
        exit;
    }

    throw new Exception("Unknown action: {$action}");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
