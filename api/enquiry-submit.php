<?php
/**
 * Public Enquiry Submission Endpoint
 * Creative IT Institute - DreamIT Platform
 *
 * Security:
 *   - CSRF token required on every POST (checked against session token)
 *   - Honeypot anti-spam (invisible field that bots fill)
 *   - Rate limiting (max 3 submissions per 5 minutes per IP)
 *   - All inputs sanitised and validated server-side
 *   - Prepared statements only
 *
 * Standard JSON response:
 *   Success: { "success": true, "data": { ... }, "message": "..." }
 *   Error:   { "success": false, "error": { "code": "...", "message": "...", "fields": { ... } } }
 */

// Define this is an API request
define('API_REQUEST', true);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/helpers.php';

// Apply security headers and configure session
apply_security_headers();
configure_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('METHOD_NOT_ALLOWED', 'Invalid request method. Only POST is accepted.', [], 405);
}

// Honeypot validation (anti-spam) - Must be BEFORE other checks
// If honeypot field is filled, it's a bot - silently reject
honeypot_require('website');

// CSRF verification — token must match session token.
// The public forms send the token as a hidden field AND as X-CSRF-TOKEN header via JS.
csrf_require();

// Rate limiting (3 submissions per 5 minutes per IP)
$clientIp = get_client_ip();
rate_limit_require('form', $clientIp, 3, 300);

// Read input from form-data or JSON payload
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Validate inputs
$fields = [];
$name        = sanitize_text($input['name'] ?? '');
$phone       = sanitize_text($input['phone'] ?? '');
$email       = sanitize_email($input['email'] ?? '');
$message     = sanitize_text($input['message'] ?? '');
$subject     = sanitize_text($input['subject'] ?? '');
$department  = sanitize_text($input['department'] ?? ($input['branch'] ?? ''));
$branch      = $department;
$courseId    = !empty($input['course_id']) ? (int)$input['course_id'] : null;
$courseTitle = sanitize_text($input['course_title'] ?? ($input['course_name'] ?? ''));

if (!validate_required($name, 2)) {
    $fields['name'] = 'Please enter your full name (at least 2 characters).';
}

if (!validate_phone($phone)) {
    $fields['phone'] = 'Please provide a valid contact phone number (7-17 digits).';
}

if (!empty($input['email']) && !validate_email($email)) {
    $fields['email'] = 'Please enter a valid email address.';
}

if (!empty($fields)) {
    api_error('VALIDATION_ERROR', 'Please resolve the highlighted validation errors.', $fields, 422);
}

if (empty($message)) {
    $message = "Interested in learning more about courses, batch schedules, and admission fees.";
}

try {
    $db = Database::getConnection();

    // Verify course_id validity if provided to satisfy foreign key constraint
    if ($courseId !== null) {
        $chk = $db->prepare("SELECT id, title FROM courses WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $courseId]);
        $foundCourse = $chk->fetch();
        if ($foundCourse) {
            $courseTitle = $foundCourse['title'];
        } else {
            $courseId = null;
        }
    }

    $stmt = $db->prepare("
        INSERT INTO enquiries (
            name, phone, email, course_id, course_name, course_title, subject, branch, message, ip_address, user_agent, status
        ) VALUES (
            :name, :phone, :email, :course_id, :course_name, :course_title, :subject, :branch, :message, :ip, :ua, 'new'
        )
    ");

    $stmt->execute([
        ':name'         => $name,
        ':phone'        => $phone,
        ':email'        => $email ?: null,
        ':course_id'    => $courseId,
        ':course_name'  => $courseTitle ?: null,
        ':course_title' => $courseTitle ?: null,
        ':subject'      => $subject ?: ($courseTitle ? 'Course Admission: ' . $courseTitle : 'General Enquiry'),
        ':branch'       => $branch ?: null,
        ':message'      => $message,
        ':ip'           => $clientIp,
        ':ua'           => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);

    $newId = (int)$db->lastInsertId();

    // Record rate limit timestamp
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['enquiry_hits'][] = time();
    }

    api_success(
        ['id' => $newId, 'name' => $name],
        'Thank you! Your enquiry has been received. Our counselor will contact you shortly.',
        201
    );

} catch (Exception $e) {
    error_log("Enquiry submission error: " . $e->getMessage());
    api_error('SERVER_ERROR', 'We were unable to submit your request at this moment. Please call our hotline directly.', [], 500);
}
