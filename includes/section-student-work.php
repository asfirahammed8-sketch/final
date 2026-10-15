<?php
/**
 * Reusable "Our Student Work" Section
 * Include this file wherever a Student Work preview is needed.
 *
 * Expects:
 *   $db  — active PDO connection (Database::getConnection())
 *
 * Optional caller variables (with defaults):
 *   $studentWorkLimit   — how many cards to show (default 6)
 *   $studentWorkHeading — override section heading (default "Our Student Work")
 *   $studentWorkSubtitle — override subtitle
 *   $studentWorkExcludeSlug — slug to exclude (e.g. current project on detail page)
 */

$_swLimit     = (int)($studentWorkLimit   ?? 6);
$_swHeading   = $studentWorkHeading  ?? 'Our Student Work';
$_swSub       = $studentWorkSubtitle ?? 'Explore creative projects built by our talented students — real work, real skills, real results.';
$_swExclude   = $studentWorkExcludeSlug ?? '';
$_swCourseId  = (int)($studentWorkCourseId ?? 0);

try {
    if (!isset($db)) { $db = Database::getConnection(); }

    $conditions = ["status = 'published'"];
    $params = [];

    if ($_swCourseId > 0) {
        $conditions[] = "course_id = :cid";
        $params[':cid'] = [$_swCourseId, PDO::PARAM_INT];
    }

    if (!empty($_swExclude)) {
        $conditions[] = "slug != :ex";
        $params[':ex'] = [$_swExclude, PDO::PARAM_STR];
    }

    $whereSql = implode(' AND ', $conditions);
    $sql = "SELECT id, title, slug, category, student_name, cover_image, course_id
            FROM projects
            WHERE {$whereSql}
            ORDER BY featured DESC, sort_order ASC, created_at DESC
            LIMIT :lim";

    $swStmt = $db->prepare($sql);
    foreach ($params as $paramKey => [$paramVal, $paramType]) {
        $swStmt->bindValue($paramKey, $paramVal, $paramType);
    }
    $swStmt->bindValue(':lim', $_swLimit, PDO::PARAM_INT);
    $swStmt->execute();
    $_swProjects = $swStmt->fetchAll(PDO::FETCH_ASSOC);

    // If course had no projects yet, fetch general published projects as fallback
    if (empty($_swProjects) && $_swCourseId > 0) {
        $fallbackSql = "SELECT id, title, slug, category, student_name, cover_image, course_id
                        FROM projects
                        WHERE status = 'published' " . (!empty($_swExclude) ? "AND slug != :ex " : "") . "
                        ORDER BY featured DESC, sort_order ASC, created_at DESC
                        LIMIT :lim";
        $fbStmt = $db->prepare($fallbackSql);
        if (!empty($_swExclude)) {
            $fbStmt->bindValue(':ex', $_swExclude, PDO::PARAM_STR);
        }
        $fbStmt->bindValue(':lim', $_swLimit, PDO::PARAM_INT);
        $fbStmt->execute();
        $_swProjects = $fbStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $_swEx) {
    $_swProjects = [];
}

if (empty($_swProjects)) return; // nothing to show — render nothing
?>
<section class="student-work-section" aria-label="Student Work Portfolio">
  <div class="c-container">

    <header class="student-work-header">
      <h2 class="student-work-title"><?= e($_swHeading) ?></h2>
      <p class="student-work-subtitle"><?= e($_swSub) ?></p>
    </header>

    <div class="student-work-grid">
      <?php foreach ($_swProjects as $_swP):
        $swUrl   = base_url('project-detail.php?slug=' . urlencode($_swP['slug']));
        $swCover = !empty($_swP['cover_image']) ? e($_swP['cover_image']) : 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80';
      ?>
        <a href="<?= $swUrl ?>" class="sw-card" aria-label="<?= e($_swP['title']) ?> by <?= e($_swP['student_name']) ?>">
          <img src="<?= $swCover ?>"
               alt="<?= e($_swP['title']) ?>"
               loading="lazy"
               onerror="this.src='https://images.unsplash.com/photo-1626785774573-4b799315345d?w=800&auto=format&fit=crop&q=80'" />
          <div class="sw-card-hover-overlay">
            <span class="sw-overlay-category"><?= e($_swP['category'] ?? '') ?></span>
            <h3 class="sw-overlay-student-name"><?= e($_swP['student_name'] ?? '') ?></h3>
            <p class="sw-overlay-project-title"><?= e($_swP['title']) ?> &rarr;</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="student-work-cta">
      <a href="<?= base_url('projects/') ?>" class="btn-see-more">See More Student Work</a>
    </div>

  </div>
</section>
