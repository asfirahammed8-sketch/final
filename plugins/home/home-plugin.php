<?php
/**
 * Plugin Name: WP Sync - Home Page Plugin
 * Description: High performance management of Home Page options and stats. Supports WP Transient caching and secure reset actions.
 * Version: 1.2
 */

if (!defined('ABSPATH')) exit;

class WPSync_Home_Plugin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Invalidate transient on option save
        add_action('update_option_hero_title', [$this, 'clear_transient']);
        add_action('update_option_hero_subtitle', [$this, 'clear_transient']);
        add_action('update_option_hero_cta_text', [$this, 'clear_transient']);
        add_action('update_option_stats_total_students', [$this, 'clear_transient']);
        add_action('update_option_stats_active_courses', [$this, 'clear_transient']);
    }

    public function clear_transient() {
        delete_transient('wp_sync_home_cache');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Home Page Sync Settings',
            'WP Home Settings',
            'manage_options',
            'wp-home-settings',
            [$this, 'render_admin_form'],
            'dashicons-admin-home'
        );
    }

    public function register_settings() {
        register_setting('wp_home_group', 'hero_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wp_home_group', 'hero_subtitle', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wp_home_group', 'hero_cta_text', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wp_home_group', 'stats_total_students', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wp_home_group', 'stats_active_courses', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function render_admin_form() {
        ?>
        <div class="wrap wp-sync-admin-container" style="max-width: 800px; margin: 20px auto; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;">
            <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 25px;">
                    <h1 style="margin: 0; font-size: 24px; color: #1d2327;">WP Sync — Home Page</h1>
                    <span style="background: #007cba; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">ACTIVE CONTRACT</span>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('wp_home_group'); ?>

                    <div style="margin-bottom: 25px;">
                        <h2 style="font-size: 16px; margin-bottom: 15px; color: #2c3338; border-left: 4px solid #007cba; padding-left: 10px;">Hero Section</h2>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">Hero Title</label>
                            <input type="text" name="hero_title" value="<?php echo esc_attr(get_option('hero_title', 'Welcome to our Learning Hub')); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px;" required />
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">Hero Subtitle</label>
                            <input type="text" name="hero_subtitle" value="<?php echo esc_attr(get_option('hero_subtitle', 'Expand your horizons with our hand-crafted courses & events')); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px;" required />
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">Hero CTA Button Text</label>
                            <input type="text" name="hero_cta_text" value="<?php echo esc_attr(get_option('hero_cta_text', 'Get Started Now')); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px;" required />
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h2 style="font-size: 16px; margin-bottom: 15px; color: #2c3338; border-left: 4px solid #007cba; padding-left: 10px;">Statistics Counters</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">Total Students (e.g. 15,000+)</label>
                                <input type="text" name="stats_total_students" value="<?php echo esc_attr(get_option('stats_total_students', '15,000+')); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px;" required />
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327;">Active Courses (e.g. 120+)</label>
                                <input type="text" name="stats_active_courses" value="<?php echo esc_attr(get_option('stats_active_courses', '120+')); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px;" required />
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Save Bar -->
                    <div style="position: sticky; bottom: 0; background: #fff; padding: 15px 0; border-top: 1px solid #f0f0f1; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="submit" class="button button-primary" style="background: #007cba; border-color: #007cba; padding: 6px 20px; font-weight: 600; height: auto;">Save Options</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('wp-sync/v1', '/home', [
            'methods' => 'GET',
            'callback' => [$this, 'get_home_data'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('wp-sync/v1', '/reset/home', [
            'methods' => 'POST',
            'callback' => [$this, 'reset_to_default'],
            'permission_callback' => [$this, 'verify_reset_permission']
        ]);
    }

    public function get_home_data($request) {
        $cached = get_transient('wp_sync_home_cache');
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }

        $data = [
            'hero_title' => get_option('hero_title', 'Welcome to our Learning Hub'),
            'hero_subtitle' => get_option('hero_subtitle', 'Expand your horizons with our hand-crafted courses & events'),
            'hero_cta_text' => get_option('hero_cta_text', 'Get Started Now'),
            'stats_total_students' => get_option('stats_total_students', '15,000+'),
            'stats_active_courses' => get_option('stats_active_courses', '120+')
        ];

        set_transient('wp_sync_home_cache', $data, 12 * HOUR_IN_SECONDS);
        return new WP_REST_Response($data, 200);
    }

    public function verify_reset_permission() {
        // Secure permission check requiring administrative privileges
        return current_user_can('manage_options');
    }

    public function reset_to_default($request) {
        $json_file = dirname(dirname(dirname(__FILE__))) . '/defaults/default-home.json';
        if (!file_exists($json_file)) {
            return new WP_Error('no_defaults_file', 'Default-home.json not found', ['status' => 404]);
        }
        $raw = file_get_contents($json_file);
        $defaults = json_decode($raw, true);
        if (!$defaults) {
            return new WP_Error('invalid_json', 'Default file JSON is invalid', ['status' => 500]);
        }

        update_option('hero_title', sanitize_text_field($defaults['hero_title']));
        update_option('hero_subtitle', sanitize_text_field($defaults['hero_subtitle']));
        update_option('hero_cta_text', sanitize_text_field($defaults['hero_cta_text']));
        update_option('stats_total_students', sanitize_text_field($defaults['stats_total_students']));
        update_option('stats_active_courses', sanitize_text_field($defaults['stats_active_courses']));

        $this->clear_transient();

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Home settings successfully reset to defaults.',
            'reset_fields' => array_keys($defaults)
        ], 200);
    }
}

new WPSync_Home_Plugin();
