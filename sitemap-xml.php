<?php
/**
 * Dynamic XML Sitemap Generator
 * Creative IT Institute - DreamIT Platform
 */

header('Content-Type: application/xml; charset=UTF-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$baseUrl = get_canonical_base();

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Core Public Pages -->
    <url>
        <loc><?= e($baseUrl) ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.95</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/about/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/success-stories/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/gallery/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.70</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/faq/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/contact/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.80</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/privacy/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.30</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/sitemap/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.40</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/projects/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>

    <!-- Course Department Category Routes -->
    <url>
        <loc><?= e($baseUrl) ?>/courses/graphic-design/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/web-development/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/digital-marketing/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/3d-animation/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/diploma-courses/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/courses/cyber-security/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    <!-- Dynamic Individual Course Pages from Database -->
    <?php
    try {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT slug, updated_at FROM courses WHERE status = 'active' ORDER BY sort_order ASC, id DESC");
        while ($c = $stmt->fetch()):
            $lastmod = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : date('Y-m-d');
    ?>
    <url>
        <loc><?= e($baseUrl) ?>/courses/<?= e($c['slug']) ?>/</loc>
        <lastmod><?= e($lastmod) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <?php
        endwhile;
    } catch (Exception $e) {
        // Fallback gracefully if database is unreachable
    }
    ?>

    <!-- Dynamic Student Project Pages from Database -->
    <?php
    try {
        $projStmt = $db->query("SELECT slug, updated_at FROM projects WHERE status = 'published' ORDER BY featured DESC, sort_order ASC, created_at DESC");
        while ($p = $projStmt->fetch()):
            $projLastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : date('Y-m-d');
    ?>
    <url>
        <loc><?= e($baseUrl) ?>/project-detail.php?slug=<?= urlencode($p['slug']) ?></loc>
        <lastmod><?= e($projLastmod) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.65</priority>
    </url>
    <?php
        endwhile;
    } catch (Exception $e) {
        // Fallback gracefully
    }
    ?>
</urlset>
