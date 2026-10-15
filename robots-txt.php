<?php
/**
 * Dynamic robots.txt generator
 * Creative IT Institute - DreamIT Platform
 */
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$canonicalBase = get_canonical_base();
?>
User-agent: *
Allow: /
Allow: /courses/
Allow: /about/
Allow: /success-stories/
Allow: /gallery/
Allow: /faq/
Allow: /contact/
Allow: /privacy/
Allow: /sitemap/

# Disallow private, administrative, and system directories
Disallow: /admin/
Disallow: /api/
Disallow: /config/
Disallow: /database/
Disallow: /.env
Disallow: /*.sql$
Disallow: /*.lock$

Sitemap: <?= e($canonicalBase) ?>/sitemap.xml
