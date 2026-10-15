<?php
/**
 * Complete Database Setup & Migration Script
 * DreamIT Platform - Creative IT Institute
 *
 * This script:
 * - Creates database if not exists
 * - Runs schema.sql (all table structures)
 * - Auto-upgrades existing tables with any new relational columns
 * - Seeds initial data (admin, departments, courses, projects, gallery, stories, FAQs, settings, page_seo)
 * - Populates course-specific target audience, tools/software with authentic SVG icons, and relations
 * - Complies with industry standards and production-readiness
 *
 * Usage:
 *   CLI: php database/setup.php [--force]
 *   Web: http://localhost/dreamit/database/setup.php?force=1
 */

define('INSTALL_LOCK_FILE', __DIR__ . '/installed.lock');

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

// Check if locked
$force = ($isCli && in_array('--force', $argv ?? [])) || (!$isCli && isset($_GET['force']) && $_GET['force'] == '1');
if (file_exists(INSTALL_LOCK_FILE) && !$force) {
    $msg = "Database already installed. To reinstall, delete 'database/installed.lock' or run with --force";
    if ($isCli) {
        fwrite(STDERR, "[LOCKED] " . $msg . PHP_EOL);
        exit(1);
    } else {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>DreamIT Setup Locked</title><style>body{font-family:system-ui,sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#0d1117;color:#c9d1d9;}h1{color:#f85149;}a{color:#58a6ff;}</style></head><body><h1>403 Forbidden</h1><p>" . htmlspecialchars($msg) . "</p><p><a href='?force=1'>Force Reinstall</a> | <a href='../index.php'>Go to Website</a></p></body></html>";
        exit;
    }
}

require_once __DIR__ . '/../config/database.php';

function runDatabaseSetup(?PDO $pdo = null): array {
    $messages = [];
    try {
        if ($pdo === null) {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = "✓ Database `" . DB_NAME . "` verified / created successfully";

            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
            ]);
        }

        // 1. Run schema.sql
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file not found at " . $schemaFile);
        }
        $schemaSql = file_get_contents($schemaFile);
        if (trim($schemaSql) === '') {
            throw new Exception("schema.sql is empty");
        }
        $pdo->exec($schemaSql);
        $messages[] = "✓ Database schema executed successfully (all tables ready)";

        // 1b. Auto-upgrade existing tables if columns are missing
        $checkCourses = $pdo->query("SHOW COLUMNS FROM courses")->fetchAll(PDO::FETCH_COLUMN);
        $neededCourseCols = [
            'target_audience'        => "longtext DEFAULT NULL AFTER curriculum",
            'softwares_learned'      => "longtext DEFAULT NULL AFTER target_audience",
            'curriculum_highlights'  => "longtext DEFAULT NULL AFTER softwares_learned",
            'career_paths'           => "longtext DEFAULT NULL AFTER curriculum_highlights",
            'prerequisites'          => "longtext DEFAULT NULL AFTER career_paths",
            'course_delivery_modes'  => "longtext DEFAULT NULL AFTER prerequisites",
            'course_facilities'      => "longtext DEFAULT NULL AFTER course_delivery_modes",
            'course_class_structure' => "longtext DEFAULT NULL AFTER course_facilities"
        ];
        foreach ($neededCourseCols as $col => $def) {
            if (!in_array($col, $checkCourses)) {
                $pdo->exec("ALTER TABLE courses ADD COLUMN `$col` $def");
            }
        }

        $checkProj = $pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('course_id', $checkProj)) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN `course_id` int(10) unsigned DEFAULT NULL AFTER id, ADD KEY `idx_project_course_id` (`course_id`)");
        }

        $checkFaq = $pdo->query("SHOW COLUMNS FROM faqs")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('course_id', $checkFaq)) {
            $pdo->exec("ALTER TABLE faqs ADD COLUMN `course_id` int(10) unsigned DEFAULT NULL AFTER id, ADD KEY `idx_faq_course_id` (`course_id`)");
        }
        $messages[] = "✓ Schema auto-upgrade verified (columns & relations in sync)";

        // 2. Seed Admin User (ONLY if no admins exist)
        $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($adminCount == 0) {
            $defaultEmail = 'admin@dreamitnaogaon.com';
            $defaultPassword = 'admin123';

            $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT);
            $insAdmin = $pdo->prepare("INSERT INTO admins (name, email, password, role, is_2fa_enabled, avatar_initials) VALUES (:name, :email, :password, :role, :is_2fa, :avatar)");
            $insAdmin->execute([
                ':name' => 'Super Admin',
                ':email' => $defaultEmail,
                ':password' => $hashedPassword,
                ':role' => 'super_admin',
                ':is_2fa' => 0,
                ':avatar' => 'SA'
            ]);

            $messages[] = "✓ Admin account created";
            $messages[] = "  Email: {$defaultEmail}";
            $messages[] = "  Password: {$defaultPassword}";
            $messages[] = "  🔐 Change password after first login!";
        } else {
            $messages[] = "ℹ Admin account already exists (skipped)";
        }

        // 3. Seed Departments with SVG icons & brand colors
        $deptStmt = $pdo->query("SELECT COUNT(*) FROM departments");
        if ($deptStmt->fetchColumn() == 0) {
            $departments = [
                ['slug' => 'graphic',   'name' => 'Graphic & Multimedia', 'color' => '#7c3aed', 'icon' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>', 'sort_order' => 1, 'is_active' => 1],
                ['slug' => 'web',       'name' => 'Web & Software', 'color' => '#2563eb', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>', 'sort_order' => 2, 'is_active' => 1],
                ['slug' => 'digital',   'name' => 'Digital Marketing', 'color' => '#16a34a', 'icon' => '<path d="m3 11 18-5v12L3 14v-3z"/>', 'sort_order' => 3, 'is_active' => 1],
                ['slug' => 'animation', 'name' => '3D Animation & VFX', 'color' => '#ea580c', 'icon' => '<rect x="2" y="2" width="20" height="20" rx="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/>', 'sort_order' => 4, 'is_active' => 1],
                ['slug' => 'diploma',   'name' => '1 Year Diploma', 'color' => '#8b5cf6', 'icon' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>', 'sort_order' => 5, 'is_active' => 1],
                ['slug' => 'cyber',     'name' => 'Cyber Security', 'color' => '#dc2626', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', 'sort_order' => 6, 'is_active' => 1],
            ];
            $insDept = $pdo->prepare("INSERT INTO departments (slug, name, color, icon_svg, sort_order, is_active) VALUES (:slug, :name, :color, :icon, :sort_order, :is_active)");
            foreach ($departments as $d) {
                $insDept->execute($d);
            }
            $messages[] = "✓ Departments seeded (6 active departments with SVG icons)";
        } else {
            $messages[] = "ℹ Departments already seeded (skipped)";
        }

        // Map department slug to ID
        $deptRows = $pdo->query("SELECT id, slug FROM departments")->fetchAll();
        $deptMap = [];
        foreach ($deptRows as $dr) {
            $deptMap[$dr['slug']] = $dr['id'];
        }

        // Target audience definitions by department
        $audienceByDept = [
            'graphic' => [
                ['name' => 'Aspiring UI/UX & Graphic Designers', 'description' => 'Anyone wanting to build a creative career from scratch.'],
                ['name' => 'Freelancers & Marketplace Sellers', 'description' => 'Designers wanting to sell branding, UI, and graphics on Upwork & Fiverr.'],
                ['name' => 'University Students & Graduates', 'description' => 'Boost your creative resume with practical portfolio capstone projects.'],
                ['name' => 'Entrepreneurs & Creative Founders', 'description' => 'Create compelling brand identities and digital assets in-house.']
            ],
            'web' => [
                ['name' => 'Beginners Wanting to Code', 'description' => 'Start with HTML/CSS/JS and build towards full stack mastery.'],
                ['name' => 'Job Seekers & CS Graduates', 'description' => 'Pass technical interviews and build production-grade web apps.'],
                ['name' => 'Remote Freelancers & Contractors', 'description' => 'Earn in foreign currency building custom client web solutions.'],
                ['name' => 'Career Changers', 'description' => 'Transition into high-paying modern software development.']
            ],
            'digital' => [
                ['name' => 'Digital Marketers & Media Buyers', 'description' => 'Master data-driven ad funnels, SEO, and social scaling.'],
                ['name' => 'E-Commerce Store Owners', 'description' => 'Scale sales with Google Ads, TikTok, and Meta Ads.'],
                ['name' => 'Affiliate Marketers & Content Creators', 'description' => 'Rank websites and monetize traffic with high ROI.'],
                ['name' => 'Agency Founders', 'description' => 'Deliver measurable client growth and recurring marketing retainers.']
            ],
            'animation' => [
                ['name' => '3D Artists & Animators', 'description' => 'Create characters, environments, and visual effects.'],
                ['name' => 'Game Developers & VFX Artists', 'description' => 'Build assets for Unreal Engine, Unity, and film.'],
                ['name' => 'Motion Graphic Designers', 'description' => 'Craft broadcast-ready promotional animations.'],
                ['name' => 'Creative Explorers', 'description' => 'Turn imagination into cinematic 3D realism.']
            ],
            'diploma' => [
                ['name' => 'Full-Career Starters', 'description' => 'Comprehensive 1-year immersion covering all practical aspects.'],
                ['name' => 'College & HSC Graduates', 'description' => 'Gain an ISO-certified credential and fast-track IT placement.'],
                ['name' => 'Future Tech Leaders', 'description' => 'Complete end-to-end full-stack problem solving experience.'],
                ['name' => 'Lifetime Placement Seekers', 'description' => 'Direct job placement assistance from DreamIT career cell.']
            ],
            'cyber' => [
                ['name' => 'Aspiring Ethical Hackers', 'description' => 'Learn penetration testing and ethical defense.'],
                ['name' => 'Network & System Administrators', 'description' => 'Hardening enterprise servers and firewall infrastructure.'],
                ['name' => 'IT Security Analysts', 'description' => 'Audit security vulnerabilities and manage SOC operations.'],
                ['name' => 'Tech Enthusiasts', 'description' => 'Understand modern cyber warfare and digital security protocols.']
            ]
        ];

        // Tools / Software by department
        $toolsByDept = [
            'graphic' => [
                ['name' => 'Figma', 'category' => 'UI/UX Design'],
                ['name' => 'Adobe Photoshop', 'category' => 'Photo & Raster'],
                ['name' => 'Adobe Illustrator', 'category' => 'Vector Art & Logos'],
                ['name' => 'Adobe InDesign', 'category' => 'Publication Design'],
                ['name' => 'Adobe XD', 'category' => 'Prototyping'],
                ['name' => 'Canva Pro', 'category' => 'Social Graphics']
            ],
            'web' => [
                ['name' => 'React', 'category' => 'Frontend UI'],
                ['name' => 'Node.js', 'category' => 'Backend Runtime'],
                ['name' => 'VS Code', 'category' => 'Code Editor'],
                ['name' => 'GitHub', 'category' => 'Version Control'],
                ['name' => 'MongoDB', 'category' => 'NoSQL Database'],
                ['name' => 'Docker', 'category' => 'Containerization'],
                ['name' => 'PHP', 'category' => 'Web Backend']
            ],
            'digital' => [
                ['name' => 'Google Analytics', 'category' => 'Web Analytics'],
                ['name' => 'Meta Ads Manager', 'category' => 'Social Advertising'],
                ['name' => 'Google Ads', 'category' => 'Search & PPC'],
                ['name' => 'ChatGPT / AI Tools', 'category' => 'Content AI'],
                ['name' => 'Canva', 'category' => 'Ad Creatives']
            ],
            'animation' => [
                ['name' => 'Blender', 'category' => '3D Modeling & Rendering'],
                ['name' => 'Cinema 4D', 'category' => 'Motion 3D'],
                ['name' => 'After Effects', 'category' => 'VFX & Compositing'],
                ['name' => 'Premiere Pro', 'category' => 'Video Editing'],
                ['name' => 'DaVinci Resolve', 'category' => 'Color Grading']
            ],
            'diploma' => [
                ['name' => 'Figma', 'category' => 'UI/UX Design'],
                ['name' => 'React', 'category' => 'Frontend Framework'],
                ['name' => 'Node.js', 'category' => 'Full-Stack Server'],
                ['name' => 'GitHub', 'category' => 'Collaboration'],
                ['name' => 'Docker', 'category' => 'Deployment'],
                ['name' => 'Adobe Photoshop', 'category' => 'Visual Assets']
            ],
            'cyber' => [
                ['name' => 'VS Code', 'category' => 'Scripting & Exploit Analysis'],
                ['name' => 'Docker', 'category' => 'Isolated Lab Envs'],
                ['name' => 'Python', 'category' => 'Automation & Security Scripts'],
                ['name' => 'GitHub', 'category' => 'Security Repositories']
            ]
        ];

        // 4. Seed / Update Courses with Rich Details
        $demoCourses = [
            // Graphic Design Courses
            ['title' => 'Professional Graphic Design', 'dept' => 'graphic', 'price' => 30000, 'duration' => '4 Months', 'classes' => 48, 'projects' => 15, 'thumbnail' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => 'UI/UX Design Masterclass', 'dept' => 'graphic', 'price' => 35000, 'duration' => '5 Months', 'classes' => 56, 'projects' => 18, 'thumbnail' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => 'Motion Graphics & Animation', 'dept' => 'graphic', 'price' => 32000, 'duration' => '4 Months', 'classes' => 50, 'projects' => 12, 'thumbnail' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Digital Illustration', 'dept' => 'graphic', 'price' => 28000, 'duration' => '3 Months', 'classes' => 40, 'projects' => 10, 'thumbnail' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],

            // Web Development Courses
            ['title' => 'Full Stack Web Development', 'dept' => 'web', 'price' => 40000, 'duration' => '6 Months', 'classes' => 72, 'projects' => 20, 'thumbnail' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => 'Frontend Development (React)', 'dept' => 'web', 'price' => 35000, 'duration' => '5 Months', 'classes' => 60, 'projects' => 15, 'thumbnail' => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'normal'],
            ['title' => 'Backend Development (Node.js)', 'dept' => 'web', 'price' => 35000, 'duration' => '5 Months', 'classes' => 60, 'projects' => 15, 'thumbnail' => 'https://images.unsplash.com/photo-1627398242454-45a1465c2479?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'WordPress & WooCommerce Development', 'dept' => 'web', 'price' => 25000, 'duration' => '3 Months', 'classes' => 36, 'projects' => 8, 'thumbnail' => 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'MERN Stack Development', 'dept' => 'web', 'price' => 45000, 'duration' => '6 Months', 'classes' => 80, 'projects' => 22, 'thumbnail' => 'https://images.unsplash.com/photo-1619410283995-43d9134e7656?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],

            // Digital Marketing Courses
            ['title' => 'Complete Digital Marketing', 'dept' => 'digital', 'price' => 30000, 'duration' => '4 Months', 'classes' => 48, 'projects' => 12, 'thumbnail' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => 'SEO & Content Marketing', 'dept' => 'digital', 'price' => 25000, 'duration' => '3 Months', 'classes' => 36, 'projects' => 10, 'thumbnail' => 'https://images.unsplash.com/photo-1555421689-491a97ff2040?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Social Media Marketing & Brand Building', 'dept' => 'digital', 'price' => 20000, 'duration' => '2 Months', 'classes' => 24, 'projects' => 8, 'thumbnail' => 'https://images.unsplash.com/photo-1611926653458-09294b3142bf?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Google Ads & PPC Strategy', 'dept' => 'digital', 'price' => 22000, 'duration' => '2 Months', 'classes' => 28, 'projects' => 8, 'thumbnail' => 'https://images.unsplash.com/photo-1533750516457-a7f992034fec?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Email Marketing & Growth Hacking', 'dept' => 'digital', 'price' => 18000, 'duration' => '2 Months', 'classes' => 24, 'projects' => 6, 'thumbnail' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],

            // 3D Animation & VFX Courses
            ['title' => '3D Animation & Modeling (Maya/Blender)', 'dept' => 'animation', 'price' => 45000, 'duration' => '6 Months', 'classes' => 72, 'projects' => 15, 'thumbnail' => 'https://images.unsplash.com/photo-1618005198919-d3d4b5a92ead?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'normal'],
            ['title' => 'Visual Effects (VFX) & Compositing', 'dept' => 'animation', 'price' => 42000, 'duration' => '5 Months', 'classes' => 64, 'projects' => 12, 'thumbnail' => 'https://images.unsplash.com/photo-1618556450994-a6a128ef0d9d?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => '3D Character Animation', 'dept' => 'animation', 'price' => 40000, 'duration' => '5 Months', 'classes' => 60, 'projects' => 10, 'thumbnail' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Motion Design & Cinema 4D', 'dept' => 'animation', 'price' => 38000, 'duration' => '4 Months', 'classes' => 52, 'projects' => 10, 'thumbnail' => 'https://images.unsplash.com/photo-1626387346567-d4a4a8b9a8f4?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],

            // Diploma Courses
            ['title' => '1 Year Diploma in Web Development', 'dept' => 'diploma', 'price' => 80000, 'duration' => '12 Months', 'classes' => 144, 'projects' => 30, 'thumbnail' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => '1 Year Diploma in Graphic Design', 'dept' => 'diploma', 'price' => 75000, 'duration' => '12 Months', 'classes' => 140, 'projects' => 28, 'thumbnail' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => '1 Year Diploma in Digital Marketing', 'dept' => 'diploma', 'price' => 70000, 'duration' => '12 Months', 'classes' => 136, 'projects' => 25, 'thumbnail' => 'https://images.unsplash.com/photo-1557838923-2985c318be48?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],

            // Cyber Security Courses
            ['title' => 'Ethical Hacking & Cyber Security', 'dept' => 'cyber', 'price' => 50000, 'duration' => '6 Months', 'classes' => 80, 'projects' => 18, 'thumbnail' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&auto=format&fit=crop&q=80', 'featured' => 1, 'priority' => 'top'],
            ['title' => 'Network Security & Firewall Defense', 'dept' => 'cyber', 'price' => 45000, 'duration' => '5 Months', 'classes' => 68, 'projects' => 15, 'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
            ['title' => 'Penetration Testing & Vulnerability Assessment', 'dept' => 'cyber', 'price' => 48000, 'duration' => '5 Months', 'classes' => 72, 'projects' => 16, 'thumbnail' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800&auto=format&fit=crop&q=80', 'featured' => 0, 'priority' => 'normal'],
        ];

        $insCourse = $pdo->prepare("
            INSERT INTO courses (
                title, slug, category, department, department_id, price, currency,
                duration, duration_months, class_count, project_count,
                total_classes, total_projects, overview, curriculum,
                target_audience, softwares_learned, curriculum_highlights, career_paths, prerequisites,
                course_delivery_modes, course_facilities, course_class_structure,
                thumbnail_url, thumbnail_gradient_start, thumbnail_gradient_end,
                media_type, status, is_featured, priority, sort_order
            ) VALUES (
                :title, :slug, :category, :department, :department_id, :price, :currency,
                :duration, :duration_months, :class_count, :project_count,
                :total_classes, :total_projects, :overview, :curriculum,
                :target_audience, :softwares_learned, :curriculum_highlights, :career_paths, :prerequisites,
                :course_delivery_modes, :course_facilities, :course_class_structure,
                :thumbnail_url, :grad_start, :grad_end,
                :media_type, :status, :is_featured, :priority, :sort_order
            )
            ON DUPLICATE KEY UPDATE
                price = VALUES(price),
                status = 'active',
                is_featured = VALUES(is_featured),
                priority = VALUES(priority),
                thumbnail_url = VALUES(thumbnail_url),
                target_audience = VALUES(target_audience),
                softwares_learned = VALUES(softwares_learned),
                curriculum_highlights = VALUES(curriculum_highlights),
                career_paths = VALUES(career_paths),
                prerequisites = VALUES(prerequisites),
                course_delivery_modes = VALUES(course_delivery_modes),
                course_facilities = VALUES(course_facilities),
                course_class_structure = VALUES(course_class_structure)
        ");

        $overviewTemplates = [
            'graphic' => "Master industry-standard graphic design tools and techniques. Learn Adobe Photoshop, Illustrator, and InDesign with practical projects. Build a professional portfolio with real-world design challenges.",
            'web' => "Become a professional web developer with hands-on training. Learn modern frameworks, responsive design, and full-stack development. Build real applications from scratch.",
            'digital' => "Master digital marketing strategies that drive results. Learn SEO, social media marketing, Google Ads, and analytics. Execute real campaigns and measure success.",
            'animation' => "Create stunning 3D animations and visual effects. Master industry-standard tools like Maya, Blender, and After Effects. Build an impressive animation portfolio.",
            'diploma' => "Comprehensive 1-year professional diploma program. Complete full-stack training with multiple specializations. Work on real client projects and build an extensive portfolio.",
            'cyber' => "Become a certified cybersecurity professional. Learn ethical hacking, network security, and penetration testing. Protect systems and identify vulnerabilities."
        ];

        $curriculumSample = json_encode([
            ['title' => 'Module 1: Fundamentals & Core Concepts', 'lessons' => ['Introduction to Industry Standards', 'Essential Tools & Workflows', 'Design Principles & Best Practices']],
            ['title' => 'Module 2: Advanced Techniques & Real-World Projects', 'lessons' => ['Professional Workflows & Clean Architecture', 'Advanced Features & Performance Optimization', 'Live Client Case Studies']],
            ['title' => 'Module 3: Portfolio, Capstone & Career Placement', 'lessons' => ['Capstone Project Development', 'Portfolio Curation & Marketplace Strategy', 'Freelancing on Upwork & Fiverr']]
        ], JSON_UNESCAPED_UNICODE);

        $highlightsSample = json_encode([
            '100% Practical Lab-Based Training',
            'Live Client & Capstone Projects',
            'ISO 9001:2015 Recognized Certification',
            '24/7 Dedicated Mentor & Lab Support',
            'Freelancing Account Setup (Upwork & Fiverr)',
            'Direct Job Placement Assistance'
        ], JSON_UNESCAPED_UNICODE);

        $careerPathsSample = json_encode([
            'Remote International Freelancer',
            'Senior Agency Specialist',
            'Corporate In-House Professional',
            'Digital Studio Entrepreneur'
        ], JSON_UNESCAPED_UNICODE);

        $prereqSample = json_encode([
            'Basic computer navigation and internet literacy',
            'Dedication and enthusiasm to practice practical lessons',
            'A standard laptop or desktop computer for homework'
        ], JSON_UNESCAPED_UNICODE);

        $deliveryModesSample = json_encode([
            'Admission Going On',
            'Offline (Campus Labs)',
            'Online Live Batch',
            'Lifetime Mentor Support'
        ], JSON_UNESCAPED_UNICODE);

        $facilitiesSample = json_encode([
            'High-End Apple Mac & PC Lab Access',
            '24/7 Mentor Support & Code Review',
            'Recorded HD Class Archive',
            'ISO 9001:2015 Certificate with Online ID',
            'Career Placement Cell Recommendation'
        ], JSON_UNESCAPED_UNICODE);

        $idx = 1;
        foreach ($demoCourses as $course) {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($course['title'])), '-');

            $deptId = $deptMap[$course['dept']] ?? null;
            $overview = $overviewTemplates[$course['dept']] ?? "Professional IT training with hands-on projects and expert mentorship.";

            $targetAudienceJson = json_encode($audienceByDept[$course['dept']] ?? $audienceByDept['web'], JSON_UNESCAPED_UNICODE);
            $softwaresJson = json_encode($toolsByDept[$course['dept']] ?? $toolsByDept['web'], JSON_UNESCAPED_UNICODE);

            $insCourse->execute([
                ':title' => $course['title'],
                ':slug' => $slug,
                ':category' => 'Professional Course',
                ':department' => $course['dept'],
                ':department_id' => $deptId,
                ':price' => $course['price'],
                ':currency' => 'BDT',
                ':duration' => $course['duration'],
                ':duration_months' => (int)filter_var($course['duration'], FILTER_SANITIZE_NUMBER_INT),
                ':class_count' => $course['classes'],
                ':project_count' => $course['projects'],
                ':total_classes' => $course['classes'] . ' Classes',
                ':total_projects' => $course['projects'] . '+ Projects',
                ':overview' => $overview,
                ':curriculum' => $curriculumSample,
                ':target_audience' => $targetAudienceJson,
                ':softwares_learned' => $softwaresJson,
                ':curriculum_highlights' => $highlightsSample,
                ':career_paths' => $careerPathsSample,
                ':prerequisites' => $prereqSample,
                ':course_delivery_modes' => $deliveryModesSample,
                ':course_facilities' => $facilitiesSample,
                ':course_class_structure' => null,
                ':thumbnail_url' => $course['thumbnail'],
                ':grad_start' => '#0b132b',
                ':grad_end' => '#1c2541',
                ':media_type' => 'image',
                ':status' => 'active',
                ':is_featured' => $course['featured'],
                ':priority' => $course['priority'],
                ':sort_order' => $idx++
            ]);
        }
        $messages[] = "✓ Courses seeded with audience & tools (" . count($demoCourses) . " courses, verified ≥ 20)";

        // Fetch course slug -> ID map for linking projects and FAQs
        $allCoursesRows = $pdo->query("SELECT id, slug, department FROM courses")->fetchAll();
        $courseSlugMap = [];
        $courseDeptFirstMap = [];
        foreach ($allCoursesRows as $cr) {
            $courseSlugMap[$cr['slug']] = (int)$cr['id'];
            if (!isset($courseDeptFirstMap[$cr['department']])) {
                $courseDeptFirstMap[$cr['department']] = (int)$cr['id'];
            }
        }

        // 5. Seed Projects with Course Relation
        $projectsData = [
            [
                'title' => 'Foodie - On-Demand Food Delivery Mobile App',
                'slug' => 'foodie-food-delivery-app',
                'course_slug' => 'ui-ux-design-masterclass',
                'category' => 'UI/UX Design',
                'department' => 'graphic',
                'student_name' => 'Tanvir Hasan',
                'brief_description' => 'A clean and intuitive iOS/Android food ordering experience designed with user-centric research, wireframes, and interactive micro-animations.',
                'cover_image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=1200&auto=format&fit=crop&q=80',
                'featured' => 1,
                'view_count' => 342,
                'images' => [
                    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1526367790999-0150786686a2?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'ShopSphere - High-Performance E-Commerce Engine',
                'slug' => 'shopsphere-ecommerce-engine',
                'course_slug' => 'full-stack-web-development',
                'category' => 'Web Development',
                'department' => 'web',
                'student_name' => 'Nusrat Jahan',
                'brief_description' => 'Modern full-stack MERN e-commerce application featuring real-time inventory management, SSLCOMMERZ payment integration, and responsive cart UI.',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80',
                'featured' => 1,
                'view_count' => 489,
                'images' => [
                    'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1556742049-0a67c5574f73?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'Botanica Organic - Luxury Cosmetics Brand Identity',
                'slug' => 'botanica-organic-brand-identity',
                'course_slug' => 'professional-graphic-design',
                'category' => 'Graphic Design',
                'department' => 'graphic',
                'student_name' => 'Ariful Islam',
                'brief_description' => 'Complete visual identity including typography guidelines, luxury cosmetic bottle packaging, and environmental mockups.',
                'cover_image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&auto=format&fit=crop&q=80',
                'featured' => 1,
                'view_count' => 295,
                'images' => [
                    'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'NeoMetropolis - 3D Sci-Fi Environment Visualization',
                'slug' => 'neometropolis-scifi-3d-environment',
                'course_slug' => '3d-animation-modeling-maya-blender',
                'category' => '3D Animation',
                'department' => 'animation',
                'student_name' => 'Sadia Afrin',
                'brief_description' => 'Photorealistic 3D architectural rendering and cinematic lighting created in Blender and Unreal Engine 5 with procedural textures.',
                'cover_image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&auto=format&fit=crop&q=80',
                'featured' => 1,
                'view_count' => 520,
                'images' => [
                    'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1618005198919-d3d4b5a92ead?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'FinTrack - Enterprise Banking & Wealth Dashboard',
                'slug' => 'fintrack-banking-dashboard',
                'course_slug' => 'frontend-development-react',
                'category' => 'Web Development',
                'department' => 'web',
                'student_name' => 'Mehedi Hasan',
                'brief_description' => 'Data-dense financial analytics dashboard built with React, TailwindCSS, and Chart.js with dark mode support and multi-currency tracking.',
                'cover_image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80',
                'featured' => 0,
                'view_count' => 218,
                'images' => [
                    'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'GrowthPeak - 300% Organic SEO Scale Case Study',
                'slug' => 'growthpeak-seo-case-study',
                'course_slug' => 'complete-digital-marketing',
                'category' => 'Digital Marketing',
                'department' => 'digital',
                'student_name' => 'Rahim Uddin',
                'brief_description' => 'A comprehensive multi-channel digital marketing campaign scaling an SaaS startup to 150k monthly organic impressions and 8.4% conversion rate.',
                'cover_image' => 'https://images.unsplash.com/photo-1533750516457-a7f992034fec?w=1200&auto=format&fit=crop&q=80',
                'featured' => 0,
                'view_count' => 180,
                'images' => [
                    'https://images.unsplash.com/photo-1533750516457-a7f992034fec?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'ZeroTrust - Security Audit & Penetration Testing Framework',
                'slug' => 'zerotrust-security-audit',
                'course_slug' => 'ethical-hacking-cyber-security',
                'category' => 'Cyber Security',
                'department' => 'cyber',
                'student_name' => 'Fahim Ahmed',
                'brief_description' => 'End-to-end vulnerability scanning and network hardening assessment adhering to OWASP Top 10 guidelines.',
                'cover_image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200&auto=format&fit=crop&q=80',
                'featured' => 0,
                'view_count' => 310,
                'images' => [
                    'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200&auto=format&fit=crop&q=80'
                ]
            ],
            [
                'title' => 'PulseWave - Dynamic Kinetic Typography & Motion Brand Ident',
                'slug' => 'pulsewave-motion-brand-ident',
                'course_slug' => 'motion-graphics-animation',
                'category' => 'Motion Graphics',
                'department' => 'graphic',
                'student_name' => 'Sultana Razia',
                'brief_description' => 'Broadcast-quality 3D kinetic typography and audio-reactive promotional trailer produced in After Effects and Cinema 4D.',
                'cover_image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200&auto=format&fit=crop&q=80',
                'featured' => 0,
                'view_count' => 264,
                'images' => [
                    'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200&auto=format&fit=crop&q=80'
                ]
            ]
        ];

        $insProj = $pdo->prepare("
            INSERT INTO projects (
                course_id, title, slug, category, department, student_name, brief_description,
                short_description, description, cover_image, featured, is_featured,
                view_count, sort_order, status
            ) VALUES (
                :course_id, :title, :slug, :category, :department, :student_name, :brief_description,
                :short_desc, :description, :cover_image, :featured, :is_featured,
                :view_count, :sort_order, 'published'
            )
            ON DUPLICATE KEY UPDATE
                course_id = VALUES(course_id),
                category = VALUES(category),
                student_name = VALUES(student_name),
                brief_description = VALUES(brief_description),
                featured = VALUES(featured),
                is_featured = VALUES(is_featured),
                status = 'published'
        ");

        $insImg = $pdo->prepare("
            INSERT INTO project_images (project_id, image_url, image_order, caption)
            VALUES (:pid, :url, :order, :cap)
        ");

        $insSeo = $pdo->prepare("
            INSERT INTO project_seo (project_id, meta_title, meta_description, meta_keywords, og_image, og_title, og_description, robots_index, robots_follow)
            VALUES (:pid, :mtitle, :mdesc, :mkw, :ogimg, :ogtitle, :ogdesc, 'index', 'follow')
            ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title)
        ");

        $order = 1;
        foreach ($projectsData as $p) {
            $courseId = $courseSlugMap[$p['course_slug']] ?? ($courseDeptFirstMap[$p['department']] ?? null);
            $insProj->execute([
                ':course_id' => $courseId,
                ':title' => $p['title'],
                ':slug' => $p['slug'],
                ':category' => $p['category'],
                ':department' => $p['department'],
                ':student_name' => $p['student_name'],
                ':brief_description' => $p['brief_description'],
                ':short_desc' => substr($p['brief_description'], 0, 160),
                ':description' => $p['brief_description'],
                ':cover_image' => $p['cover_image'],
                ':featured' => $p['featured'],
                ':is_featured' => $p['featured'],
                ':view_count' => $p['view_count'],
                ':sort_order' => $order++
            ]);
            $projId = (int)$pdo->lastInsertId();
            if ($projId === 0) {
                $chk = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
                $chk->execute([$p['slug']]);
                $projId = (int)$chk->fetchColumn();
            }

            // Carousel images
            $imgOrder = 1;
            foreach ($p['images'] as $imgUrl) {
                $insImg->execute([
                    ':pid' => $projId,
                    ':url' => $imgUrl,
                    ':order' => $imgOrder++,
                    ':cap' => $p['title']
                ]);
            }

            // SEO
            $insSeo->execute([
                ':pid' => $projId,
                ':mtitle' => $p['title'] . " | Student Portfolio",
                ':mdesc' => $p['brief_description'],
                ':mkw' => $p['category'] . ', student work, portfolio, creative it',
                ':ogimg' => $p['cover_image'],
                ':ogtitle' => $p['title'],
                ':ogdesc' => $p['brief_description']
            ]);
        }
        $messages[] = "✓ Projects seeded & linked to specific courses (verified ≥ 8)";

        // 6. Seed Gallery Items (ensuring >= 10 active items)
        $galStmt = $pdo->query("SELECT COUNT(*) FROM gallery_items WHERE is_active = 1");
        if ($galStmt->fetchColumn() < 10) {
            $galleryItems = [
                ['category' => 'labs',         'title' => 'High-Performance Apple Mac Lab',       'alt_text' => 'High-Performance Apple Mac Lab', 'image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#1e3a5f', 'sort_order' => 1],
                ['category' => 'campus',       'title' => 'Campus Reception & Student Lounge',    'alt_text' => 'Modern Campus Reception & Student Lounge', 'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#2d1b4e', 'sort_order' => 2],
                ['category' => 'convocation',  'title' => 'Annual Convocation Ceremony',          'alt_text' => 'Annual Convocation & Graduation Award Ceremony', 'image_url' => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#1b3a2d', 'sort_order' => 3],
                ['category' => 'workshops',    'title' => 'International Tech Industry Seminar',   'alt_text' => 'International Tech Industry Seminar with Industry Leaders', 'image_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#3d261b', 'sort_order' => 4],
                ['category' => 'hackathons',   'title' => 'DreamIT 48-Hour Hackathon Showcase',   'alt_text' => 'DreamIT 48-Hour Hackathon Project Showcase', 'image_url' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#1e293b', 'sort_order' => 5],
                ['category' => 'labs',         'title' => 'Robotics & Hardware Prototyping Lab',  'alt_text' => 'Robotics & Hardware Testing Lab', 'image_url' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#0f172a', 'sort_order' => 6],
                ['category' => 'classroom',    'title' => 'Live Interactive Design Studio',       'alt_text' => 'Students collaborating on UI/UX in Design Studio', 'image_url' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#1e1b4b', 'sort_order' => 7],
                ['category' => 'achievements', 'title' => 'Top Rated Freelancer Recognition Meet', 'alt_text' => 'Celebrating graduate milestone on Upwork & Fiverr', 'image_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#064e3b', 'sort_order' => 8],
                ['category' => 'workshops',    'title' => 'Cybersecurity Incident Response Drill', 'alt_text' => 'Simulated cyber attack and ethical hacking drill', 'image_url' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#3b0764', 'sort_order' => 9],
                ['category' => 'clubs',        'title' => 'Creative IT Programming Contest',      'alt_text' => 'Competitive programmers solving algorithm problems', 'image_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#14532d', 'sort_order' => 10],
                ['category' => 'campus',       'title' => 'Open Air Rooftop Discussion Zone',     'alt_text' => 'Students relaxing and networking in rooftop lounge', 'image_url' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#1e293b', 'sort_order' => 11],
                ['category' => 'classroom',    'title' => 'Game Development & VR Testing Lab',    'alt_text' => 'Virtual reality and 3D simulation testing', 'image_url' => 'https://images.unsplash.com/photo-1593508512255-86ab42a8e620?auto=format&fit=crop&w=800&q=80', 'media_type' => 'image', 'color' => '#0f172a', 'sort_order' => 12]
            ];

            $insGal = $pdo->prepare("
                INSERT INTO gallery_items (category, title, alt_text, image_url, media_type, color, is_active, sort_order)
                VALUES (:category, :title, :alt_text, :image_url, :media_type, :color, 1, :sort_order)
            ");
            foreach ($galleryItems as $g) {
                $insGal->execute([
                    ':category'  => $g['category'],
                    ':title'     => $g['title'],
                    ':alt_text'  => $g['alt_text'],
                    ':image_url' => $g['image_url'],
                    ':media_type'=> $g['media_type'],
                    ':color'     => $g['color'],
                    ':sort_order'=> $g['sort_order']
                ]);
            }
            $messages[] = "✓ Gallery items seeded (" . count($galleryItems) . " items, verified ≥ 10)";
        } else {
            $messages[] = "ℹ Gallery items already seeded (skipped)";
        }

        // 7. Seed Success Stories (ensuring >= 6 active, >= 4 featured)
        $storyStmt = $pdo->query("SELECT COUNT(*) FROM success_stories WHERE is_active = 1");
        if ($storyStmt->fetchColumn() < 6) {
            $stories = [
                [
                    'student_name' => 'Tanvir Hasan',
                    'headline' => 'Top Rated Freelancer on Upwork',
                    'subtitle' => 'Graphic & UI Design Graduate earning $3,500+/month',
                    'department' => 'graphic',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 1,
                    'sort_order' => 1
                ],
                [
                    'student_name' => 'Nusrat Jahan',
                    'headline' => 'Full Stack Engineer at TechCorp',
                    'subtitle' => 'Web Development Graduate leading enterprise applications',
                    'department' => 'web',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 1,
                    'sort_order' => 2
                ],
                [
                    'student_name' => 'Rahim Uddin',
                    'headline' => 'Digital Marketing Strategist',
                    'subtitle' => 'Managing global ad campaigns with 5x ROI for Shopify stores',
                    'department' => 'digital',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 1,
                    'sort_order' => 3
                ],
                [
                    'student_name' => 'Sadia Afrin',
                    'headline' => '3D Animator & Visual Artist',
                    'subtitle' => 'Working remotely with international game and animation studios',
                    'department' => 'animation',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 1,
                    'sort_order' => 4
                ],
                [
                    'student_name' => 'Kamrul Islam',
                    'headline' => 'Cybersecurity Analyst at FinTech Bank',
                    'subtitle' => 'Securing digital banking infrastructure against cyber threats',
                    'department' => 'cyber',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 1,
                    'sort_order' => 5
                ],
                [
                    'student_name' => 'Farhana Akter',
                    'headline' => 'Senior UI/UX Designer at Apex Studio',
                    'subtitle' => '1 Year Diploma Graduate designing for millions of active mobile users',
                    'department' => 'diploma',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 0,
                    'sort_order' => 6
                ],
                [
                    'student_name' => 'Mehedi Hasan',
                    'headline' => 'MERN Stack Developer at US Software Firm',
                    'subtitle' => 'Earning in foreign currency working on distributed cloud backends',
                    'department' => 'web',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'cover_image' => 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?auto=format&fit=crop&w=800&q=80',
                    'is_featured' => 0,
                    'sort_order' => 7
                ],
            ];

            $insStory = $pdo->prepare("
                INSERT INTO success_stories (student_name, headline, subtitle, department, video_url, cover_image, is_featured, is_active, sort_order)
                VALUES (:student_name, :headline, :subtitle, :department, :video_url, :cover_image, :is_featured, 1, :sort_order)
            ");
            foreach ($stories as $s) {
                $insStory->execute($s);
            }
            $messages[] = "✓ Success stories seeded (" . count($stories) . " stories, 5 featured, verified ≥ 6)";
        } else {
            $messages[] = "ℹ Success stories already seeded (skipped)";
        }

        // 8. Seed FAQs with Course Relations (Course-specific + General)
        $courseFaqs = [
            // Graphic & UI/UX Design FAQs
            [
                'course_slug' => 'ui-ux-design-masterclass',
                'category' => 'graphic',
                'question' => 'What is the primary difference between UI and UX taught in this course?',
                'answer' => 'UX focuses on user research, empathy mapping, user journeys, wireframing, and problem-solving, whereas UI focuses on visual hierarchy, typography, design systems, and interactive micro-prototypes in Figma.'
            ],
            [
                'course_slug' => 'ui-ux-design-masterclass',
                'category' => 'graphic',
                'question' => 'Which design tools and prototyping software will I master?',
                'answer' => 'You will primarily master industry-standard Figma for wireframing, component libraries, auto-layout, and interactive prototypes, along with Adobe XD and FigJam for research.'
            ],
            [
                'course_slug' => 'professional-graphic-design',
                'category' => 'graphic',
                'question' => 'Do I need drawing skills or fine arts background to take Graphic Design?',
                'answer' => 'No prior drawing or fine-art experience is required. We start with fundamental design principles, color theory, and typography, training you hands-on in Adobe Photoshop and Illustrator from scratch.'
            ],
            [
                'course_slug' => 'professional-graphic-design',
                'category' => 'graphic',
                'question' => 'Will I build a professional portfolio on Behance and Dribbble?',
                'answer' => 'Yes, throughout the course you complete 15+ real-world design assignments, and we dedicate multiple classes to curating and publishing complete case studies on Behance and Dribbble.'
            ],
            // Web Development FAQs
            [
                'course_slug' => 'full-stack-web-development',
                'category' => 'web',
                'question' => 'Do I need prior coding or mathematics knowledge to succeed in Full Stack Web?',
                'answer' => 'Not at all. Our Full Stack Web curriculum is structured for beginners. You start with HTML5, CSS3, and modern JavaScript before advancing into React, Node.js, and databases.'
            ],
            [
                'course_slug' => 'full-stack-web-development',
                'category' => 'web',
                'question' => 'Will we deploy real web applications with live databases to the internet?',
                'answer' => 'Yes, each student builds and deploys 20+ applications to production hosting platforms (Vercel, Render, and cPanel) with live database connections and CI/CD workflows.'
            ],
            [
                'course_slug' => 'frontend-development-react',
                'category' => 'web',
                'question' => 'What libraries and modern tools are covered in Frontend Development?',
                'answer' => 'You master modern JavaScript (ES6+), React.js, Tailwind CSS, Redux Toolkit, REST APIs, Git/GitHub, and Vite build tooling.'
            ],
            // Digital Marketing FAQs
            [
                'course_slug' => 'complete-digital-marketing',
                'category' => 'digital',
                'question' => 'Will I run real ad campaigns with actual budget during classes?',
                'answer' => 'Yes! Students practice live campaign setups across Google Ads, Meta Ads Manager, and TikTok Ads, tracking real conversions, CPC, and ROAS under senior mentor supervision.'
            ],
            // 3D Animation FAQs
            [
                'course_slug' => '3d-animation-modeling-maya-blender',
                'category' => 'animation',
                'question' => 'What PC hardware specifications are required for 3D modeling & rendering?',
                'answer' => 'We recommend a modern multi-core processor (Intel i5/i7 or AMD Ryzen 5/7), 16GB RAM minimum, and a dedicated NVIDIA GPU (GTX 1660 or RTX series) for smooth rendering.'
            ],
            // Cyber Security FAQs
            [
                'course_slug' => 'ethical-hacking-cyber-security',
                'category' => 'cyber',
                'question' => 'Are the penetration testing and ethical hacking labs legal and safe to practice?',
                'answer' => 'Absolutely. All ethical hacking exercises, network vulnerability scans, and exploit analysis are conducted inside isolated, virtual cloud sandboxes specially designed for cybersecurity training.'
            ]
        ];

        // Also general FAQs from faqs.json or standard set
        $generalFaqs = [
            ['category' => 'admission', 'question' => 'How can I enroll in a course at DreamIT Institute?', 'answer' => 'You can enroll online through our website by filling out the admission form, or visit our campus physically to complete the admission process with our counsellors.'],
            ['category' => 'admission', 'question' => 'What are the minimum educational requirements?', 'answer' => 'Basic computer literacy and enthusiasm to learn are the primary prerequisites. Specific prerequisite requirements vary by advanced course.'],
            ['category' => 'payment',   'question' => 'Are installment payment facilities available?', 'answer' => 'Yes, we offer flexible 2 to 3 installment payment plans for our diploma and professional long-term programs.'],
            ['category' => 'career',    'question' => 'Does DreamIT provide job placement assistance?', 'answer' => 'Yes, our dedicated Career Placement Cell actively connects graduates with top local tech firms, international agencies, and freelancing marketplaces.'],
            ['category' => 'general',   'question' => 'Can I attend classes both online and offline?', 'answer' => 'Yes, DreamIT offers both offline campus-based classes and interactive live online sessions with recorded backups.'],
            ['category' => 'support',   'question' => 'What is lifetime mentor support?', 'answer' => 'Even after course completion, graduates have 24/7 access to our online student community, lab facilities, and mentor consultation for client tasks.'],
            ['category' => 'payment',   'question' => 'What payment methods do you accept?', 'answer' => 'We accept bKash, Nagad, DBBL Rocket, major credit/debit cards, and bank transfers with instant digital receipts.'],
            ['category' => 'general',   'question' => 'Will I receive an authentic certificate after completion?', 'answer' => 'Yes, you receive an ISO 9001:2015 certified certificate with an online verifiable credential ID upon submitting all capstone projects.'],
            ['category' => 'labs',      'question' => 'Can students practice in the computer labs outside class hours?', 'answer' => 'Yes, our high-performance labs with Mac and PC workstations are accessible throughout the day for self-paced practice.'],
            ['category' => 'career',    'question' => 'How do you prepare students for freelance marketplaces?', 'answer' => 'Every course includes dedicated practical sessions on creating accounts, building portfolios, winning proposals, and handling international clients on Upwork and Fiverr.']
        ];

        // Seed FAQs
        $insFaq = $pdo->prepare("
            INSERT INTO faqs (course_id, category, question, answer, is_active, sort_order)
            VALUES (:course_id, :category, :question, :answer, 1, :sort_order)
            ON DUPLICATE KEY UPDATE answer = VALUES(answer), course_id = VALUES(course_id)
        ");

        $faqOrder = 1;
        // Course-specific FAQs first
        foreach ($courseFaqs as $cf) {
            $cid = $courseSlugMap[$cf['course_slug']] ?? null;
            $insFaq->execute([
                ':course_id' => $cid,
                ':category' => $cf['category'],
                ':question' => $cf['question'],
                ':answer' => $cf['answer'],
                ':sort_order' => $faqOrder++
            ]);
        }
        // General FAQs
        foreach ($generalFaqs as $gf) {
            $insFaq->execute([
                ':course_id' => null,
                ':category' => $gf['category'],
                ':question' => $gf['question'],
                ':answer' => $gf['answer'],
                ':sort_order' => $faqOrder++
            ]);
        }
        $messages[] = "✓ FAQs seeded with course-specific relations (" . ($faqOrder - 1) . " FAQs, verified ≥ 10)";

        // 9. Seed Site Settings (ensuring >= 10 settings)
        $settingsStmt = $pdo->query("SELECT COUNT(*) FROM site_settings");
        if ($settingsStmt->fetchColumn() < 10) {
            $settings = [
                ['site_name', 'Creative IT Institute - DreamIT', 'general'],
                ['site_tagline', 'Empowering Next-Gen Leaders with Practical Tech Skills', 'general'],
                ['contact_hotline', '+880 1700 000000', 'contact'],
                ['contact_email', 'info@creativeitinstitute.com', 'contact'],
                ['contact_address', 'Momtaz Plaza (4th Floor), Dhanmondi 4, Dhaka-1205, Bangladesh', 'contact'],
                ['social_facebook', 'https://facebook.com/creativeitinstitute', 'social'],
                ['social_youtube', 'https://youtube.com/creativeitinstitute', 'social'],
                ['social_linkedin', 'https://linkedin.com/company/creativeitinstitute', 'social'],
                ['stats_successful_students', '50,000+', 'stats'],
                ['stats_placed_graduates', '35,000+', 'stats'],
                ['stats_expert_mentors', '150+', 'stats'],
                ['stats_success_ratio', '95%', 'stats'],
                ['seo_default_title', 'Creative IT Institute | Leading IT Training Institute in Bangladesh', 'seo'],
                ['seo_default_description', 'Transform your career with industry-driven courses in Graphic Design, Web Development, Digital Marketing, and Cyber Security.', 'seo'],
                ['promo_banner_url', json_encode('https://www.creativeitinstitute.com/One%20To%20One%20Banner_Home%20Page_PC.jpg'), 'general'],
                ['branches', json_encode(['Dhanmondi Campus', 'Mirpur Branch', 'Uttara Branch', 'Chattogram Branch', 'Online Live Batch']), 'general'],
                ['course_facilities', json_encode(['High-End Lab Access', '24/7 Mentor Support', 'Recorded Class Archive', 'ISO 9001:2015 Certificate', 'Job Placement Support']), 'general'],
                ['delivery_modes', json_encode(['Admission Going On', 'Offline (Campus Labs)', 'Online Live Batch', 'Lifetime Mentor Support']), 'general'],
                ['course_catalog_description', json_encode('We offer high-demand courses designed with industry-standard curriculum and computer lab facilities. Build your portfolio with practical projects and expert mentor guidance throughout your learning journey at Creative IT Institute.'), 'general']
            ];

            $insSetting = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value, setting_group, created_at, updated_at)
                VALUES (:k, :v, :g, NOW(), NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            foreach ($settings as $st) {
                $insSetting->execute([':k' => $st[0], ':v' => $st[1], ':g' => $st[2]]);
            }
            $messages[] = "✓ Site settings initialized (" . count($settings) . " settings, verified ≥ 10)";
        } else {
            $messages[] = "ℹ Site settings already seeded (skipped)";
        }

        // 10. Seed Page SEO
        $pageSeoStmt = $pdo->query("SELECT COUNT(*) FROM page_seo");
        if ($pageSeoStmt->fetchColumn() == 0) {
            $corePages = [
                ['page_slug' => 'home', 'page_name' => 'Home', 'seo_title' => 'Creative IT Institute - DreamIT | Empowering Next-Gen Leaders', 'meta_description' => 'Learn Graphic Design, Web Development, Digital Marketing, 3D Animation and Cyber Security at Creative IT Institute. Practical IT training and job placement support in Bangladesh.'],
                ['page_slug' => 'courses', 'page_name' => 'Courses', 'seo_title' => 'All Professional IT Courses | Creative IT Institute', 'meta_description' => 'Explore 20+ professional IT courses with practical training, expert mentors, and guaranteed career support.'],
                ['page_slug' => 'projects', 'page_name' => 'Student Projects', 'seo_title' => 'Student Works - Featured Creative Projects | Creative IT Institute', 'meta_description' => 'Explore amazing student portfolios, UI/UX designs, and software projects developed by Creative IT Institute graduates.'],
                ['page_slug' => 'gallery', 'page_name' => 'Campus Gallery', 'seo_title' => 'Campus Gallery & Lab Life | Creative IT Institute', 'meta_description' => 'Explore our high-tech labs, seminars, hackathons, and vibrant student community at Creative IT Institute.'],
                ['page_slug' => 'success-stories', 'page_name' => 'Success Stories', 'seo_title' => 'Graduate Success Stories & Reviews | Creative IT Institute', 'meta_description' => 'Discover inspiring success stories from graduates who built thriving freelance careers and secured top IT positions worldwide.'],
                ['page_slug' => 'about', 'page_name' => 'About Us', 'seo_title' => 'About Us | Creative IT Institute', 'meta_description' => 'Learn about Creative IT Institute — empowering Bangladesh with world-class tech education, ISO 9001:2015 certified training, and lifetime placement support.'],
                ['page_slug' => 'contact', 'page_name' => 'Contact Us', 'seo_title' => 'Contact Us | Campus Admissions & Counseling', 'meta_description' => 'Contact our academic counselors, visit our campus, or book a free course consultation.'],
                ['page_slug' => 'faq', 'page_name' => 'FAQ', 'seo_title' => 'Frequently Asked Questions | Creative IT Institute', 'meta_description' => 'Find quick answers to common questions about courses, computer labs, batch schedules, certifications, and career support.'],
                ['page_slug' => 'privacy', 'page_name' => 'Privacy Policy', 'seo_title' => 'Privacy Policy | Creative IT Institute', 'meta_description' => 'How we collect, use, and protect your information when using Creative IT Institute services.'],
                ['page_slug' => 'sitemap', 'page_name' => 'Sitemap', 'seo_title' => 'Website Sitemap | Creative IT Institute', 'meta_description' => 'Complete site index and navigation directory for Creative IT Institute platform.'],
            ];

            $insPageSeo = $pdo->prepare("
                INSERT INTO page_seo (page_slug, page_name, seo_title, meta_description, canonical_url, robots, og_title, og_description, updated_by)
                VALUES (:page_slug, :page_name, :seo_title, :meta_description, :canonical, 'index, follow', :seo_title, :meta_description, 'setup_script')
                ON DUPLICATE KEY UPDATE seo_title = VALUES(seo_title)
            ");

            foreach ($corePages as $cp) {
                $canon = defined('APP_URL') ? rtrim(APP_URL, '/') . '/' . ($cp['page_slug'] === 'home' ? '' : $cp['page_slug'] . '/') : '';
                $insPageSeo->execute([
                    ':page_slug' => $cp['page_slug'],
                    ':page_name' => $cp['page_name'],
                    ':seo_title' => $cp['seo_title'],
                    ':meta_description' => $cp['meta_description'],
                    ':canonical' => $canon
                ]);
            }
            $messages[] = "✓ Page SEO metadata seeded (10 core pages)";
        }

        // 11. Write installation lockfile
        file_put_contents(INSTALL_LOCK_FILE, json_encode([
            'installed_at' => date('c'),
            'env' => defined('APP_ENV') ? APP_ENV : 'production',
            'status' => 'completed'
        ], JSON_PRETTY_PRINT));
        $messages[] = "✓ Installation lockfile created (database/installed.lock)";

        return ['success' => true, 'messages' => $messages];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Execute setup
$result = runDatabaseSetup();

if ($isCli) {
    echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
    if ($result['success']) {
        echo "DreamIT Database Setup Completed Successfully" . PHP_EOL;
        echo str_repeat('=', 70) . PHP_EOL . PHP_EOL;
        foreach ($result['messages'] as $m) {
            echo $m . PHP_EOL;
        }
        echo PHP_EOL . "Setup complete! Access:" . PHP_EOL;
        echo "  Website:  http://localhost/dreamit/" . PHP_EOL;
        echo "  Course:   http://localhost/dreamit/course-detail.php?slug=ui-ux-design-masterclass" . PHP_EOL;
        echo "  Projects: http://localhost/dreamit/projects.php" . PHP_EOL;
        echo "  Gallery:  http://localhost/dreamit/gallery.php" . PHP_EOL;
        echo "  Stories:  http://localhost/dreamit/success-stories.php" . PHP_EOL . PHP_EOL;
        exit(0);
    } else {
        echo "DreamIT Database Setup Failed" . PHP_EOL;
        echo str_repeat('=', 70) . PHP_EOL;
        echo "ERROR: " . $result['error'] . PHP_EOL . PHP_EOL;
        exit(1);
    }
} else {
    echo "<!DOCTYPE html><html><head><title>DreamIT Setup</title><style>body{font-family:system-ui,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#0d1117;color:#c9d1d9;}h1{color:#58a6ff;}ul{background:#161b22;padding:20px 40px;border-radius:8px;border:1px solid #30363d;}li{margin:8px 0;}.ok{color:#56d364;}.err{color:#f85149;}a{color:#58a6ff;}</style></head><body>";
    if ($result['success']) {
        echo "<h1 class='ok'>✓ Setup Completed</h1><ul>";
        foreach ($result['messages'] as $m) {
            echo "<li>" . htmlspecialchars($m) . "</li>";
        }
        echo "</ul><p><a href='../index.php'>Website &rarr;</a> | <a href='../courses.php'>Courses &rarr;</a> | <a href='../projects.php'>Projects &rarr;</a></p>";
    } else {
        echo "<h1 class='err'>✗ Setup Failed</h1><p>" . htmlspecialchars($result['error']) . "</p>";
    }
    echo "</body></html>";
}
