<?php
/**
 * Plugin Name: WP Sync - Course Catalog Plugin
 * Description: High performance, secure management of Courses with transient caching, restricted payloads, and strict contracts.
 * Version: 1.2
 */

if (!defined('ABSPATH')) exit;

class WPSync_Course_Plugin {
    public function __construct() {
        add_action('init', [$this, 'register_course_cpt']);
        add_action('add_meta_boxes', [$this, 'add_course_meta_boxes']);
        add_action('save_post_course', [$this, 'save_course_meta'], 10, 2);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cache invalidation hooks
        add_action('save_post_course', [$this, 'clear_specific_transients_on_save'], 10, 2);
        add_action('delete_post', [$this, 'clear_transients_on_delete']);
    }

    public function clear_all_caches() {
        delete_transient('wp_sync_courses_list_cache');
        delete_transient('wp_sync_courses_featured_cache');

        // Invalidate specific cache paths
        $posts = get_posts(['post_type' => 'course', 'numberposts' => -1, 'post_status' => 'any']);
        foreach ($posts as $p) {
            delete_transient('wp_sync_course_detail_' . $p->post_name);
        }
    }

    public function clear_specific_transients_on_save($post_id, $post) {
        delete_transient('wp_sync_courses_list_cache');
        delete_transient('wp_sync_courses_featured_cache');
        if (!empty($post->post_name)) {
            delete_transient('wp_sync_course_detail_' . $post->post_name);
        }
    }

    public function clear_transients_on_delete($post_id) {
        if (get_post_type($post_id) === 'course') {
            delete_transient('wp_sync_courses_list_cache');
            delete_transient('wp_sync_courses_featured_cache');
            $post = get_post($post_id);
            if ($post && !empty($post->post_name)) {
                delete_transient('wp_sync_course_detail_' . $post->post_name);
            }
        }
    }

    public function register_course_cpt() {
        register_post_type('course', [
            'labels' => [
                'name' => 'Courses',
                'singular_name' => 'Course'
            ],
            'public' => true,
            'show_in_rest' => false, // We use our own optimized endpoints
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'has_archive' => false,
        ]);
    }

    public function add_course_meta_boxes() {
        add_meta_box('course_meta', 'Course Data Contract Settings', [$this, 'render_meta_box'], 'course', 'normal', 'high');
    }

    public function render_meta_box($post) {
        // Output nonce for CSRF verification
        wp_nonce_field('save_course_meta_action', 'course_meta_nonce');

        $duration = get_post_meta($post->ID, '_course_duration', true);
        $rating = get_post_meta($post->ID, '_course_rating', true);
        $original_price = get_post_meta($post->ID, '_course_original_price', true);
        $discount_price = get_post_meta($post->ID, '_course_discount_price', true);
        $category = get_post_meta($post->ID, '_course_category', true);
        $student_limit = get_post_meta($post->ID, '_course_student_limit', true);
        $pdf_url = get_post_meta($post->ID, '_course_pdf_url', true);
        $syllabus = get_post_meta($post->ID, '_course_syllabus', true);
        $instructor = get_post_meta($post->ID, '_course_instructor', true);
        $featured = get_post_meta($post->ID, '_course_featured', true);
        ?>
        <div style="font-family: -apple-system,BlinkMacSystemFont,sans-serif;">
            <p style="background: #e7f5fe; border-left: 4px solid #007cba; padding: 10px; font-weight: 500; color: #1d2327;">
                💡 This form is synchronized with the React customer application. Only visible fields are managed.
            </p>
            <table class="form-table">
                <tr>
                    <th style="font-weight: 600;"><label>Is Featured Course?</label></th>
                    <td>
                        <input type="checkbox" name="course_featured" value="1" <?php checked($featured, '1'); ?> />
                        <span class="description">Check this to showcase this course on the Home Page grid.</span>
                    </td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Duration</label></th>
                    <td><input type="text" name="course_duration" value="<?php echo esc_attr($duration); ?>" class="regular-text" placeholder="e.g. 40 Hours" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Rating</label></th>
                    <td><input type="text" name="course_rating" value="<?php echo esc_attr($rating); ?>" class="regular-text" placeholder="e.g. 4.9" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Original Price ($)</label></th>
                    <td><input type="text" name="course_original_price" value="<?php echo esc_attr($original_price); ?>" class="regular-text" placeholder="e.g. 199" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Discount Price ($)</label></th>
                    <td><input type="text" name="course_discount_price" value="<?php echo esc_attr($discount_price); ?>" class="regular-text" placeholder="e.g. 99" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Category</label></th>
                    <td><input type="text" name="course_category" value="<?php echo esc_attr($category); ?>" class="regular-text" placeholder="e.g. Development" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Instructor Name</label></th>
                    <td><input type="text" name="course_instructor" value="<?php echo esc_attr($instructor); ?>" class="regular-text" placeholder="e.g. John Doe" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Student Limit</label></th>
                    <td><input type="number" name="course_student_limit" value="<?php echo esc_attr($student_limit); ?>" class="regular-text" placeholder="e.g. 100" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Syllabus Detail</label></th>
                    <td><textarea name="course_syllabus" class="large-text" rows="3" placeholder="Modules, chapters, topics..." required><?php echo esc_textarea($syllabus); ?></textarea></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Syllabus PDF URL</label></th>
                    <td><input type="url" name="course_pdf_url" value="<?php echo esc_url($pdf_url); ?>" class="large-text" placeholder="https://example.com/syllabus.pdf" /></td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function save_course_meta($post_id, $post) {
        // CSRF verification
        if (!isset($_POST['course_meta_nonce']) || !wp_verify_nonce($_POST['course_meta_nonce'], 'save_course_meta_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        // Capability authorization check
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'course_duration' => '_course_duration',
            'course_rating' => '_course_rating',
            'course_original_price' => '_course_original_price',
            'course_discount_price' => '_course_discount_price',
            'course_category' => '_course_category',
            'course_student_limit' => '_course_student_limit',
            'course_syllabus' => '_course_syllabus',
            'course_instructor' => '_course_instructor'
        ];
        foreach ($fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }

        if (isset($_POST['course_pdf_url'])) {
            update_post_meta($post_id, '_course_pdf_url', esc_url_raw($_POST['course_pdf_url']));
        }

        // Handle checkbox
        $featured_val = isset($_POST['course_featured']) ? '1' : '0';
        update_post_meta($post_id, '_course_featured', $featured_val);
    }

    public function register_rest_routes() {
        register_rest_route('wp-sync/v1', '/courses', [
            'methods' => 'GET',
            'callback' => [$this, 'get_courses'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('wp-sync/v1', '/courses/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_course_detail'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('wp-sync/v1', '/reset/courses', [
            'methods' => 'POST',
            'callback' => [$this, 'reset_to_default'],
            'permission_callback' => [$this, 'verify_reset_permission']
        ]);
    }

    public function verify_reset_permission() {
        // Enforce administrative checks for data resets
        return current_user_can('manage_options');
    }

    public function get_courses($request) {
        $featured_only = !empty($request['featured']) && $request['featured'] == '1';
        $cache_key = $featured_only ? 'wp_sync_courses_featured_cache' : 'wp_sync_courses_list_cache';

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }

        $args = [
            'post_type' => 'course',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        if ($featured_only) {
            $args['meta_query'] = [
                [
                    'key' => '_course_featured',
                    'value' => '1',
                    'compare' => '='
                ]
            ];
        }

        $posts = get_posts($args);
        $data = [];
        foreach ($posts as $post) {
            // Trimmed payload matching EXACT CourseCard visibility rules
            $data[] = [
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full') ?: 'https://example.com/default-course.jpg',
                'short_description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 12, '...'),
                'duration' => get_post_meta($post->ID, '_course_duration', true),
                'rating' => get_post_meta($post->ID, '_course_rating', true),
                'original_price' => get_post_meta($post->ID, '_course_original_price', true),
                'discount_price' => get_post_meta($post->ID, '_course_discount_price', true),
                'category' => get_post_meta($post->ID, '_course_category', true)
            ];
        }

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return new WP_REST_Response($data, 200);
    }

    public function get_course_detail($request) {
        $slug = sanitize_title($request['slug']);
        $cache_key = 'wp_sync_course_detail_' . $slug;

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }

        $posts = get_posts([
            'post_type' => 'course',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        if (empty($posts)) {
            return new WP_Error('no_course', 'Course not found', ['status' => 404]);
        }
        $post = $posts[0];

        $data = [
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full') ?: 'https://example.com/default-course.jpg',
            'overview' => $post->post_content,
            'syllabus' => get_post_meta($post->ID, '_course_syllabus', true),
            'instructor' => get_post_meta($post->ID, '_course_instructor', true),
            'duration' => get_post_meta($post->ID, '_course_duration', true),
            'student_limit' => get_post_meta($post->ID, '_course_student_limit', true),
            'pdf_url' => get_post_meta($post->ID, '_course_pdf_url', true)
        ];

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return new WP_REST_Response($data, 200);
    }

    public function reset_to_default($request) {
        $json_file = dirname(dirname(dirname(__FILE__))) . '/defaults/default-courses.json';
        if (!file_exists($json_file)) {
            return new WP_Error('no_defaults_file', 'Default-courses.json not found', ['status' => 404]);
        }
        $raw = file_get_contents($json_file);
        $defaults = json_decode($raw, true);
        if (!$defaults) {
            return new WP_Error('invalid_json', 'Default file JSON is invalid', ['status' => 500]);
        }

        // Delete existing courses
        $existing = get_posts(['post_type' => 'course', 'numberposts' => -1, 'post_status' => 'any']);
        foreach ($existing as $p) {
            wp_delete_post($p->ID, true);
        }

        $restored = [];
        foreach ($defaults as $index => $c) {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($c['title']),
                'post_name' => sanitize_title($c['slug']),
                'post_content' => wp_kses_post($c['overview']),
                'post_excerpt' => sanitize_text_field($c['short_description']),
                'post_status' => 'publish',
                'post_type' => 'course'
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_course_duration', sanitize_text_field($c['duration']));
                update_post_meta($post_id, '_course_rating', sanitize_text_field($c['rating']));
                update_post_meta($post_id, '_course_original_price', sanitize_text_field($c['original_price']));
                update_post_meta($post_id, '_course_discount_price', sanitize_text_field($c['discount_price']));
                update_post_meta($post_id, '_course_category', sanitize_text_field($c['category']));
                update_post_meta($post_id, '_course_syllabus', sanitize_text_field($c['syllabus']));
                update_post_meta($post_id, '_course_instructor', sanitize_text_field($c['instructor']));
                update_post_meta($post_id, '_course_student_limit', sanitize_text_field($c['student_limit']));
                update_post_meta($post_id, '_course_pdf_url', esc_url_raw($c['pdf_url']));
                update_post_meta($post_id, '_course_featured', $index === 0 ? '1' : '0');

                $restored[] = $c['slug'];
            }
        }

        $this->clear_all_caches();

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Course catalog reset to static data contract.',
            'courses_restored' => $restored
        ], 200);
    }
}

new WPSync_Course_Plugin();
