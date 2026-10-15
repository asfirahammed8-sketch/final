<?php
/**
 * DreamIT Admin Console - Universal Media Upload Engine
 * Supports single and multiple file uploads with strict MIME validation,
 * automatic folder routing, unique safe hashing, and dynamic URL generation.
 * Production-ready & Zero hardcoding.
 */

require_once __DIR__ . '/../config.php';
require_once ADMIN_PATH . '/includes/auth-guard.php';

header('Content-Type: application/json; charset=UTF-8');

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_admin_csrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed or session expired.']);
    exit;
}

// Allowed folders
$allowedFolders = [
    'gallery'     => 'uploads/gallery',
    'projects'    => 'uploads/projects',
    'courses'     => 'uploads/courses',
    'departments' => 'uploads/departments',
    'general'     => 'uploads/general',
];

$targetKey = $_POST['folder'] ?? 'general';
$subFolder = $allowedFolders[$targetKey] ?? 'uploads/general';

// Auto-detect base uploads root path
$uploadsRootDir = dirname(__DIR__, 2) . '/' . $subFolder;
if (!is_dir($uploadsRootDir)) {
    @mkdir($uploadsRootDir, 0755, true);
}

// Allowed MIME types and extensions
$allowedMimes = [
    'image/jpeg'    => 'jpg',
    'image/png'     => 'png',
    'image/webp'    => 'webp',
    'image/svg+xml' => 'svg',
    'image/gif'     => 'gif'
];

$maxBytes = 10 * 1024 * 1024; // 10MB limit

if (empty($_FILES['file']) && empty($_FILES['files'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
    exit;
}

$uploadedItems = [];

// Helper function to process single file
$processFile = function($tmpName, $origName, $size, $error) use ($allowedMimes, $maxBytes, $uploadsRootDir, $subFolder) {
    if ($error !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error code: {$error}");
    }
    if ($size > $maxBytes) {
        throw new Exception("File exceeds maximum allowed size of 10MB.");
    }

    // Validate MIME type safely using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);

    // Fallback for SVG if finfo returns text/plain or image/svg
    $pathExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if ($pathExt === 'svg' && (str_contains($mime, 'svg') || $mime === 'text/plain' || $mime === 'text/xml')) {
        $mime = 'image/svg+xml';
    }

    if (!isset($allowedMimes[$mime])) {
        throw new Exception("Invalid file type ({$mime}). Only WebP, JPG, PNG, SVG, and GIF are allowed.");
    }

    $ext = $allowedMimes[$mime];
    // Create clean safe slug from original name
    $rawBase = pathinfo($origName, PATHINFO_FILENAME);
    $safeSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($rawBase));
    $safeSlug = trim($safeSlug, '-');
    if (empty($safeSlug)) $safeSlug = 'upload';
    if (strlen($safeSlug) > 40) $safeSlug = substr($safeSlug, 0, 40);

    $finalName = $safeSlug . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $uploadsRootDir . '/' . $finalName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new Exception("Failed to move uploaded file to destination directory.");
    }

    // Set standard file permissions
    @chmod($destination, 0644);

    // Generate dynamic absolute web URL without hardcoding
    $webUrl = base_url($subFolder . '/' . $finalName);

    return [
        'filename' => $finalName,
        'url'      => $webUrl,
        'path'     => $subFolder . '/' . $finalName,
        'size'     => $size,
        'mime'     => $mime,
        'ext'      => $ext
    ];
};

try {
    // 1. Single file upload: $_FILES['file']
    if (!empty($_FILES['file']) && !is_array($_FILES['file']['name'])) {
        $item = $processFile(
            $_FILES['file']['tmp_name'],
            $_FILES['file']['name'],
            $_FILES['file']['size'],
            $_FILES['file']['error']
        );
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully!',
            'data'    => $item,
            'url'     => $item['url']
        ]);
        exit;
    }

    // 2. Multi-file upload: $_FILES['files'] or array $_FILES['file']
    $filesArray = !empty($_FILES['files']) ? $_FILES['files'] : $_FILES['file'];
    if (is_array($filesArray['name'])) {
        $count = count($filesArray['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($filesArray['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $item = $processFile(
                $filesArray['tmp_name'][$i],
                $filesArray['name'][$i],
                $filesArray['size'][$i],
                $filesArray['error'][$i]
            );
            $uploadedItems[] = $item;
        }

        echo json_encode([
            'success' => true,
            'message' => count($uploadedItems) . ' file(s) uploaded successfully!',
            'items'   => $uploadedItems,
            'urls'    => array_column($uploadedItems, 'url')
        ]);
        exit;
    }

    throw new Exception("Invalid file payload provided.");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
