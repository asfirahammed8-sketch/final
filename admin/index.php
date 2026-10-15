<?php
/**
 * DreamIT Admin Console - Front Controller & Route Dispatcher
 * Dispatches requests received either directly or through private slug rewrites
 */

require_once __DIR__ . '/config.php';

// Extract route from GET or PATH_INFO
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Handle direct access to /admin/ without rewrite
if (empty($route) || $route === 'index.php') {
    $route = '';
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (!empty($pathInfo)) {
        $route = trim($pathInfo, '/');
    }
}

// ----------------------------------------------------------------------
// 1. PUBLIC AUTHENTICATION ROUTES (NO GUARD)
// ----------------------------------------------------------------------
if ($route === 'login') {
    if (isset($_GET['step']) && $_GET['step'] === 'reset') {
        unset($_SESSION['admin_login_step']);
        unset($_SESSION['admin_pending_email']);
        unset($_SESSION['admin_dev_otp']);
    }
    require __DIR__ . '/auth/login.php';
    exit;
}

if ($route === 'api/auth/login') {
    $_GET['action'] = 'login';
    require __DIR__ . '/auth/actions.php';
    exit;
}

if ($route === 'api/auth/verify-otp') {
    $_GET['action'] = 'verify-otp';
    require __DIR__ . '/auth/actions.php';
    exit;
}

if ($route === 'api/auth/resend-otp') {
    $_GET['action'] = 'resend-otp';
    require __DIR__ . '/auth/actions.php';
    exit;
}

if ($route === 'forgot-password') {
    require __DIR__ . '/auth/forgot-password.php';
    exit;
}

if ($route === 'api/auth/forgot-password') {
    $_GET['action'] = 'forgot-password';
    require __DIR__ . '/auth/actions.php';
    exit;
}

if ($route === 'reset-password') {
    require __DIR__ . '/auth/reset-password.php';
    exit;
}

if ($route === 'api/auth/reset-password') {
    $_GET['action'] = 'reset-password';
    require __DIR__ . '/auth/actions.php';
    exit;
}

if ($route === 'logout') {
    $_GET['action'] = 'logout';
    require __DIR__ . '/auth/actions.php';
    exit;
}

// ----------------------------------------------------------------------
// 2. PROTECTED API ENDPOINTS
// ----------------------------------------------------------------------
if (str_starts_with($route, 'api/')) {
    if ($route === 'api/courses/save') {
        $_GET['action'] = 'save';
        require __DIR__ . '/api/courses.php';
        exit;
    }
    if ($route === 'api/courses/quick-save') {
        $_GET['action'] = 'quick-save';
        require __DIR__ . '/api/courses.php';
        exit;
    }
    if ($route === 'api/courses/toggle-status') {
        $_GET['action'] = 'toggle-status';
        require __DIR__ . '/api/courses.php';
        exit;
    }
    if ($route === 'api/courses/delete') {
        $_GET['action'] = 'delete';
        require __DIR__ . '/api/courses.php';
        exit;
    }

    if ($route === 'api/projects/save') {
        $_GET['action'] = 'save';
        require __DIR__ . '/api/projects.php';
        exit;
    }
    if ($route === 'api/projects/delete') {
        $_GET['action'] = 'delete';
        require __DIR__ . '/api/projects.php';
        exit;
    }

    if ($route === 'api/departments/save') {
        $_GET['action'] = 'save';
        require __DIR__ . '/api/departments.php';
        exit;
    }
    if ($route === 'api/departments/toggle') {
        $_GET['action'] = 'toggle';
        require __DIR__ . '/api/departments.php';
        exit;
    }

    if ($route === 'api/faqs/save') {
        $_GET['action'] = 'save';
        require __DIR__ . '/api/faqs.php';
        exit;
    }
    if ($route === 'api/faqs/toggle') {
        $_GET['action'] = 'toggle';
        require __DIR__ . '/api/faqs.php';
        exit;
    }
    if ($route === 'api/faqs/delete') {
        $_GET['action'] = 'delete';
        require __DIR__ . '/api/faqs.php';
        exit;
    }

    if ($route === 'api/enquiries/update-status') {
        $_GET['action'] = 'update-status';
        require __DIR__ . '/api/enquiries.php';
        exit;
    }
    if ($route === 'api/enquiries/delete') {
        $_GET['action'] = 'delete';
        require __DIR__ . '/api/enquiries.php';
        exit;
    }

    if ($route === 'api/settings/save') {
        require __DIR__ . '/api/settings.php';
        exit;
    }

    if ($route === 'api/seo/save') {
        require __DIR__ . '/api/seo.php';
        exit;
    }

    if ($route === 'api/upload') {
        require __DIR__ . '/api/upload.php';
        exit;
    }

    if ($route === 'api/gallery/save') {
        $_GET['action'] = 'save';
        require __DIR__ . '/api/gallery.php';
        exit;
    }
    if ($route === 'api/gallery/toggle') {
        $_GET['action'] = 'toggle';
        require __DIR__ . '/api/gallery.php';
        exit;
    }
    if ($route === 'api/gallery/delete') {
        $_GET['action'] = 'delete';
        require __DIR__ . '/api/gallery.php';
        exit;
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'API endpoint not found: ' . $route]);
    exit;
}

// ----------------------------------------------------------------------
// 3. PROTECTED ADMIN VIEWS (REQUIRES AUTH)
// ----------------------------------------------------------------------
switch ($route) {
    case '':
    case 'dashboard':
        require __DIR__ . '/views/dashboard.php';
        break;

    case 'courses':
        require __DIR__ . '/views/courses/index.php';
        break;

    case 'courses/create':
    case 'courses/edit':
        require __DIR__ . '/views/courses/wizard.php';
        break;

    case 'projects':
        require __DIR__ . '/views/projects/index.php';
        break;

    case 'gallery':
        require __DIR__ . '/views/gallery/index.php';
        break;

    case 'departments':
        require __DIR__ . '/views/departments/index.php';
        break;

    case 'enquiries':
        require __DIR__ . '/views/enquiries/index.php';
        break;

    case 'faqs':
        require __DIR__ . '/views/faqs/index.php';
        break;

    case 'settings':
        require __DIR__ . '/views/settings/index.php';
        break;

    case 'seo':
        require __DIR__ . '/views/seo/index.php';
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../404.php';
        break;
}
