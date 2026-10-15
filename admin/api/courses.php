<?php
/**
 * DreamIT Admin Console - Courses API Endpoint
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
    // 1. TOGGLE STATUS
    if ($action === 'toggle-status') {
        $id = (int)($input['id'] ?? 0);
        $status = ($input['status'] ?? 'draft') === 'active' ? 'active' : 'draft';

        $stmt = $db->prepare("UPDATE courses SET status = :st WHERE id = :id");
        $stmt->execute([':st' => $status, ':id' => $id]);

        echo json_encode(['success' => true, 'message' => "Course status set to {$status}"]);
        exit;
    }

    // 2. DELETE COURSE
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM courses WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
        exit;
    }

    // 3. QUICK SAVE (FROM POPUP MODAL)
    if ($action === 'quick-save') {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $title = trim($input['title'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $department = trim($input['department'] ?? 'web');
        $price = (float)($input['price'] ?? 0);
        $discountPrice = (isset($input['discount_price']) && $input['discount_price'] !== '') ? (float)$input['discount_price'] : null;
        $duration = trim($input['duration'] ?? '4 Months');
        $totalClasses = trim($input['total_classes'] ?? '32');
        $totalProjects = trim($input['total_projects'] ?? '10');
        $status = ($input['status'] ?? 'active') === 'draft' ? 'draft' : 'active';
        $thumbnailUrl = trim($input['thumbnail_url'] ?? '');

        if (empty($title)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Course title is required']);
            exit;
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        if ($id) {
            $sql = "UPDATE courses SET
                title = :title, slug = :slug, department = :dept, price = :price,
                discount_price = :dprice, duration = :dur, total_classes = :tclass,
                total_projects = :tproj, status = :status";
            $params = [
                ':title' => $title, ':slug' => $slug, ':dept' => $department, ':price' => $price,
                ':dprice' => $discountPrice, ':dur' => $duration, ':tclass' => $totalClasses,
                ':tproj' => $totalProjects, ':status' => $status, ':id' => $id
            ];

            if (!empty($thumbnailUrl)) {
                $sql .= ", thumbnail_url = :thumb";
                $params[':thumb'] = $thumbnailUrl;
            }

            $sql .= " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Course quick-updated successfully!',
                'course' => [
                    'id' => $id, 'title' => $title, 'slug' => $slug, 'department' => $department,
                    'price' => $price, 'discount_price' => $discountPrice, 'status' => $status
                ]
            ]);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course ID missing for quick edit']);
            exit;
        }
    }

    // 4. SAVE / UPDATE COURSE (FROM WIZARD)
    if ($action === 'save' || empty($action)) {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $title = trim($input['title'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $department = trim($input['department'] ?? 'web');
        $price = (float)($input['price'] ?? 0);
        $discountPrice = !empty($input['discount_price']) ? (float)$input['discount_price'] : null;
        $duration = trim($input['duration'] ?? '4 Months');
        $totalClasses = (int)($input['total_classes'] ?? 32);
        $totalProjects = (int)($input['total_projects'] ?? 10);
        $overview = trim($input['overview'] ?? '');
        $thumbnailUrl = trim($input['thumbnail_url'] ?? '');
        $videoUrl = trim($input['video_url'] ?? '');
        $status = ($input['status'] ?? 'active') === 'draft' ? 'draft' : 'active';
        $seoTitle = trim($input['seo_title'] ?? '');
        $metaDesc = trim($input['meta_description'] ?? '');

        if (empty($title) || empty($slug)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Title and slug are required']);
            exit;
        }

        // Format JSON columns safely
        $deliveryModes = is_array($input['course_delivery_modes'] ?? null)
            ? json_encode($input['course_delivery_modes'], JSON_UNESCAPED_UNICODE)
            : null;

        $facilities = is_array($input['course_facilities'] ?? null)
            ? json_encode(array_values(array_filter($input['course_facilities'])), JSON_UNESCAPED_UNICODE)
            : null;

        $highlights = is_array($input['curriculum_highlights'] ?? null)
            ? json_encode(array_values(array_filter($input['curriculum_highlights'])), JSON_UNESCAPED_UNICODE)
            : null;

        $careerPaths = is_array($input['career_paths'] ?? null)
            ? json_encode(array_values(array_filter($input['career_paths'])), JSON_UNESCAPED_UNICODE)
            : null;

        $prerequisites = is_array($input['prerequisites'] ?? null)
            ? json_encode(array_values(array_filter($input['prerequisites'])), JSON_UNESCAPED_UNICODE)
            : null;

        // Software tools format
        $softwaresLearned = null;
        if (is_array($input['softwares_learned'] ?? null)) {
            $formattedSoftwares = [];
            foreach ($input['softwares_learned'] as $swName) {
                $formattedSoftwares[] = ['name' => $swName, 'icon' => strtolower($swName)];
            }
            $softwaresLearned = json_encode($formattedSoftwares, JSON_UNESCAPED_UNICODE);
        }

        if ($id) {
            // Update
            $sql = "UPDATE courses SET
                title = :title, slug = :slug, department = :dept, price = :price,
                discount_price = :dprice, duration = :dur, total_classes = :tclass,
                total_projects = :tproj, overview = :overview, thumbnail_url = :thumb,
                video_url = :vurl, course_delivery_modes = :dmodes, course_facilities = :fac,
                curriculum_highlights = :hl, career_paths = :cp, prerequisites = :prereq,
                softwares_learned = :sw, seo_title = :stitle, meta_description = :mdesc,
                status = :status
                WHERE id = :id";
            $params = [
                ':title' => $title, ':slug' => $slug, ':dept' => $department, ':price' => $price,
                ':dprice' => $discountPrice, ':dur' => $duration, ':tclass' => $totalClasses,
                ':tproj' => $totalProjects, ':overview' => $overview, ':thumb' => $thumbnailUrl,
                ':vurl' => $videoUrl, ':dmodes' => $deliveryModes, ':fac' => $facilities,
                ':hl' => $highlights, ':cp' => $careerPaths, ':prereq' => $prerequisites,
                ':sw' => $softwaresLearned, ':stitle' => $seoTitle, ':mdesc' => $metaDesc,
                ':status' => $status, ':id' => $id
            ];
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Course updated successfully', 'id' => $id]);
            exit;
        } else {
            // Insert
            $sql = "INSERT INTO courses (
                title, slug, department, price, discount_price, duration, total_classes,
                total_projects, overview, thumbnail_url, video_url, course_delivery_modes,
                course_facilities, curriculum_highlights, career_paths, prerequisites,
                softwares_learned, seo_title, meta_description, status
            ) VALUES (
                :title, :slug, :dept, :price, :dprice, :dur, :tclass,
                :tproj, :overview, :thumb, :vurl, :dmodes,
                :fac, :hl, :cp, :prereq,
                :sw, :stitle, :mdesc, :status
            )";
            $params = [
                ':title' => $title, ':slug' => $slug, ':dept' => $department, ':price' => $price,
                ':dprice' => $discountPrice, ':dur' => $duration, ':tclass' => $totalClasses,
                ':tproj' => $totalProjects, ':overview' => $overview, ':thumb' => $thumbnailUrl,
                ':vurl' => $videoUrl, ':dmodes' => $deliveryModes, ':fac' => $facilities,
                ':hl' => $highlights, ':cp' => $careerPaths, ':prereq' => $prerequisites,
                ':sw' => $softwaresLearned, ':stitle' => $seoTitle, ':mdesc' => $metaDesc,
                ':status' => $status
            ];
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $newId = (int)$db->lastInsertId();

            echo json_encode(['success' => true, 'message' => 'Course published successfully', 'id' => $newId]);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
