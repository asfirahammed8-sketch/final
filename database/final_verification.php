<?php
/**
 * Final Verification - Check All Data is Correctly Added
 */

require_once __DIR__ . '/../config/database.php';

echo "=== FINAL DATABASE VERIFICATION ===" . PHP_EOL . PHP_EOL;

try {
    $db = Database::getConnection();

    $checks = [];
    $allPassed = true;

    // 1. Check Gallery Items
    $galleryCount = $db->query("SELECT COUNT(*) FROM gallery_items WHERE is_active = 1")->fetchColumn();
    $checks[] = [
        'name' => 'Gallery Items',
        'expected' => '≥ 10',
        'actual' => $galleryCount,
        'status' => $galleryCount >= 10 ? '✓' : '✗'
    ];
    if ($galleryCount < 10) $allPassed = false;

    // 2. Check Success Stories
    $storiesCount = $db->query("SELECT COUNT(*) FROM success_stories WHERE is_active = 1")->fetchColumn();
    $checks[] = [
        'name' => 'Success Stories',
        'expected' => '≥ 6',
        'actual' => $storiesCount,
        'status' => $storiesCount >= 6 ? '✓' : '✗'
    ];
    if ($storiesCount < 6) $allPassed = false;

    // 3. Check Featured Success Stories
    $featuredStories = $db->query("SELECT COUNT(*) FROM success_stories WHERE is_active = 1 AND is_featured = 1")->fetchColumn();
    $checks[] = [
        'name' => 'Featured Stories',
        'expected' => '≥ 4',
        'actual' => $featuredStories,
        'status' => $featuredStories >= 4 ? '✓' : '✗'
    ];
    if ($featuredStories < 4) $allPassed = false;

    // 4. Check Projects
    $projectsCount = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn();
    $checks[] = [
        'name' => 'Published Projects',
        'expected' => '≥ 8',
        'actual' => $projectsCount,
        'status' => $projectsCount >= 8 ? '✓' : '✗'
    ];
    if ($projectsCount < 8) $allPassed = false;

    // 5. Check Project Images
    $imagesCount = $db->query("SELECT COUNT(*) FROM project_images")->fetchColumn();
    $checks[] = [
        'name' => 'Project Images',
        'expected' => '≥ 4',
        'actual' => $imagesCount,
        'status' => $imagesCount >= 4 ? '✓' : '✗'
    ];
    if ($imagesCount < 4) $allPassed = false;

    // 6. Check FAQs
    $faqsCount = $db->query("SELECT COUNT(*) FROM faqs WHERE is_active = 1")->fetchColumn();
    $checks[] = [
        'name' => 'Active FAQs',
        'expected' => '≥ 10',
        'actual' => $faqsCount,
        'status' => $faqsCount >= 10 ? '✓' : '✗'
    ];
    if ($faqsCount < 10) $allPassed = false;

    // 7. Check Courses (should not be affected)
    $coursesCount = $db->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
    $checks[] = [
        'name' => 'Active Courses',
        'expected' => '≥ 20',
        'actual' => $coursesCount,
        'status' => $coursesCount >= 20 ? '✓' : '✗'
    ];

    // 8. Check Departments
    $deptsCount = $db->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn();
    $checks[] = [
        'name' => 'Active Departments',
        'expected' => '6',
        'actual' => $deptsCount,
        'status' => $deptsCount == 6 ? '✓' : '✗'
    ];

    // 9. Check Site Settings
    $settingsCount = $db->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    $checks[] = [
        'name' => 'Site Settings',
        'expected' => '≥ 10',
        'actual' => $settingsCount,
        'status' => $settingsCount >= 10 ? '✓' : '✗'
    ];

    // 10. Check Projects Table Structure
    $projectFields = $db->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_ASSOC);
    $fieldNames = array_column($projectFields, 'Field');
    $requiredFields = ['category', 'student_name', 'brief_description', 'view_count', 'featured'];
    $hasAllFields = !array_diff($requiredFields, $fieldNames);
    $checks[] = [
        'name' => 'Projects Table Fields',
        'expected' => 'All 5 required',
        'actual' => count(array_intersect($requiredFields, $fieldNames)) . '/5',
        'status' => $hasAllFields ? '✓' : '✗'
    ];
    if (!$hasAllFields) $allPassed = false;

    // Display Results
    echo "VERIFICATION RESULTS:" . PHP_EOL;
    echo str_repeat("=", 70) . PHP_EOL;
    echo sprintf("%-30s %-15s %-15s %s\n", "Check", "Expected", "Actual", "Status");
    echo str_repeat("-", 70) . PHP_EOL;

    foreach ($checks as $check) {
        echo sprintf("%-30s %-15s %-15s %s\n",
            $check['name'],
            $check['expected'],
            $check['actual'],
            $check['status']
        );
    }

    echo str_repeat("=", 70) . PHP_EOL;

    // Sample Data Check
    echo PHP_EOL . "SAMPLE DATA VERIFICATION:" . PHP_EOL;
    echo str_repeat("-", 70) . PHP_EOL;

    // Check first project
    $firstProject = $db->query("SELECT title, student_name, category FROM projects WHERE status = 'published' ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstProject) {
        echo "First Project:" . PHP_EOL;
        echo "  Title: {$firstProject['title']}" . PHP_EOL;
        echo "  Student: {$firstProject['student_name']}" . PHP_EOL;
        echo "  Category: {$firstProject['category']}" . PHP_EOL;
    }

    // Check first gallery item
    $firstGallery = $db->query("SELECT title, category FROM gallery_items ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstGallery) {
        echo PHP_EOL . "First Gallery Item:" . PHP_EOL;
        echo "  Title: {$firstGallery['title']}" . PHP_EOL;
        echo "  Category: {$firstGallery['category']}" . PHP_EOL;
    }

    // Check first success story
    $firstStory = $db->query("SELECT student_name, headline FROM success_stories ORDER BY id ASC LIMIT 1")->fetch();
    if ($firstStory) {
        echo PHP_EOL . "First Success Story:" . PHP_EOL;
        echo "  Student: {$firstStory['student_name']}" . PHP_EOL;
        echo "  Headline: {$firstStory['headline']}" . PHP_EOL;
    }

    echo PHP_EOL . str_repeat("=", 70) . PHP_EOL;

    // Final Status
    echo PHP_EOL;
    if ($allPassed) {
        echo "✅ ALL CHECKS PASSED! Database is correctly populated." . PHP_EOL;
        echo PHP_EOL . "🎉 Your website is ready! Visit:" . PHP_EOL;
        echo "   Homepage: http://localhost/dreamit/" . PHP_EOL;
        echo "   Gallery:  http://localhost/dreamit/gallery.php" . PHP_EOL;
        echo "   Projects: http://localhost/dreamit/projects.php" . PHP_EOL;
        echo "   Stories:  http://localhost/dreamit/success-stories.php" . PHP_EOL;
        echo "   FAQs:     http://localhost/dreamit/faq.php" . PHP_EOL;
    } else {
        echo "⚠️  Some checks failed. Review the results above." . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
