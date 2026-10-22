<?php
/**
 * Plugin Name: WP Sync - Event Calendar Plugin
 * Description: High performance, secure management of Events with transient caching, restricted payloads, and strict contracts.
 * Version: 1.2
 */

if (!defined('ABSPATH')) exit;

class WPSync_Event_Plugin {
    public function __construct() {
        add_action('init', [$this, 'register_event_cpt']);
        add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
        add_action('save_post_event', [$this, 'save_event_meta'], 10, 2);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cache invalidation hooks
        add_action('save_post_event', [$this, 'clear_specific_transients_on_save'], 10, 2);
        add_action('delete_post', [$this, 'clear_transients_on_delete']);
    }

    public function clear_all_caches() {
        delete_transient('wp_sync_events_list_cache');
        delete_transient('wp_sync_events_upcoming_cache');

        $posts = get_posts(['post_type' => 'event', 'numberposts' => -1, 'post_status' => 'any']);
        foreach ($posts as $p) {
            delete_transient('wp_sync_event_detail_' . $p->post_name);
        }
    }

    public function clear_specific_transients_on_save($post_id, $post) {
        delete_transient('wp_sync_events_list_cache');
        delete_transient('wp_sync_events_upcoming_cache');
        if (!empty($post->post_name)) {
            delete_transient('wp_sync_event_detail_' . $post->post_name);
        }
    }

    public function clear_transients_on_delete($post_id) {
        if (get_post_type($post_id) === 'event') {
            delete_transient('wp_sync_events_list_cache');
            delete_transient('wp_sync_events_upcoming_cache');
            $post = get_post($post_id);
            if ($post && !empty($post->post_name)) {
                delete_transient('wp_sync_event_detail_' . $post->post_name);
            }
        }
    }

    public function register_event_cpt() {
        register_post_type('event', [
            'labels' => [
                'name' => 'Events',
                'singular_name' => 'Event'
            ],
            'public' => true,
            'show_in_rest' => false, // Using tailored custom rest endpoints
            'supports' => ['title', 'editor'],
            'has_archive' => false,
        ]);
    }

    public function add_event_meta_boxes() {
        add_meta_box('event_meta', 'Event Data Contract Settings', [$this, 'render_meta_box'], 'event', 'normal', 'high');
    }

    public function render_meta_box($post) {
        // Nonce for security verification
        wp_nonce_field('save_event_meta_action', 'event_meta_nonce');

        $event_date = get_post_meta($post->ID, '_event_date', true);
        $location = get_post_meta($post->ID, '_event_location', true);
        $short_description = get_post_meta($post->ID, '_event_short_description', true);
        $speaker_name = get_post_meta($post->ID, '_event_speaker_name', true);
        $capacity = get_post_meta($post->ID, '_event_capacity', true);
        $ticket_price = get_post_meta($post->ID, '_event_ticket_price', true);
        $upcoming = get_post_meta($post->ID, '_event_upcoming', true);
        ?>
        <div style="font-family: -apple-system,BlinkMacSystemFont,sans-serif;">
            <p style="background: #e7f5fe; border-left: 4px solid #007cba; padding: 10px; font-weight: 500; color: #1d2327;">
                💡 This form is synchronized with the React customer application. Only visible fields are managed.
            </p>
            <table class="form-table">
                <tr>
                    <th style="font-weight: 600;"><label>Is Upcoming Event?</label></th>
                    <td>
                        <input type="checkbox" name="event_upcoming" value="1" <?php checked($upcoming, '1'); ?> />
                        <span class="description">Check this to pin this event to the Home Page upcoming grid.</span>
                    </td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Event Date</label></th>
                    <td><input type="date" name="event_date" value="<?php echo esc_attr($event_date); ?>" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Location</label></th>
                    <td><input type="text" name="event_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="e.g. Online / San Francisco" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Short Card Description</label></th>
                    <td><input type="text" name="event_short_description" value="<?php echo esc_attr($short_description); ?>" class="large-text" placeholder="Brief event summary for the calendar grid" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Speaker Name</label></th>
                    <td><input type="text" name="event_speaker_name" value="<?php echo esc_attr($speaker_name); ?>" class="regular-text" placeholder="e.g. Sarah Jenkins" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Seat Capacity</label></th>
                    <td><input type="number" name="event_capacity" value="<?php echo esc_attr($capacity); ?>" class="regular-text" placeholder="e.g. 500" required /></td>
                </tr>
                <tr>
                    <th style="font-weight: 600;"><label>Ticket Price ($)</label></th>
                    <td><input type="text" name="event_ticket_price" value="<?php echo esc_attr($ticket_price); ?>" class="regular-text" placeholder="e.g. 49" required /></td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function save_event_meta($post_id, $post) {
        // Nonce check
        if (!isset($_POST['event_meta_nonce']) || !wp_verify_nonce($_POST['event_meta_nonce'], 'save_event_meta_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        // Capability authorization check
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'event_date' => '_event_date',
            'event_location' => '_event_location',
            'event_short_description' => '_event_short_description',
            'event_speaker_name' => '_event_speaker_name',
            'event_capacity' => '_event_capacity',
            'event_ticket_price' => '_event_ticket_price'
        ];
        foreach ($fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }

        // Checkbox save
        $upcoming_val = isset($_POST['event_upcoming']) ? '1' : '0';
        update_post_meta($post_id, '_event_upcoming', $upcoming_val);
    }

    public function register_rest_routes() {
        register_rest_route('wp-sync/v1', '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'get_events'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('wp-sync/v1', '/events/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_event_detail'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('wp-sync/v1', '/reset/events', [
            'methods' => 'POST',
            'callback' => [$this, 'reset_to_default'],
            'permission_callback' => [$this, 'verify_reset_permission']
        ]);
    }

    public function verify_reset_permission() {
        // Enforce administrative checks for data resets
        return current_user_can('manage_options');
    }

    public function get_events($request) {
        $upcoming_only = !empty($request['upcoming']) && $request['upcoming'] == '1';
        $cache_key = $upcoming_only ? 'wp_sync_events_upcoming_cache' : 'wp_sync_events_list_cache';

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }

        $args = [
            'post_type' => 'event',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        if ($upcoming_only) {
            $args['meta_query'] = [
                [
                    'key' => '_event_upcoming',
                    'value' => '1',
                    'compare' => '='
                ]
            ];
        }

        $posts = get_posts($args);
        $data = [];
        foreach ($posts as $post) {
            // Trimmed payload: return ONLY fields needed by the event grid cards
            $data[] = [
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'event_date' => get_post_meta($post->ID, '_event_date', true),
                'location' => get_post_meta($post->ID, '_event_location', true),
                'short_description' => get_post_meta($post->ID, '_event_short_description', true),
                'ticket_price' => get_post_meta($post->ID, '_event_ticket_price', true)
            ];
        }

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return new WP_REST_Response($data, 200);
    }

    public function get_event_detail($request) {
        $slug = sanitize_title($request['slug']);
        $cache_key = 'wp_sync_event_detail_' . $slug;

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }

        $posts = get_posts([
            'post_type' => 'event',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        if (empty($posts)) {
            return new WP_Error('no_event', 'Event not found', ['status' => 404]);
        }
        $post = $posts[0];

        $data = [
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'event_date' => get_post_meta($post->ID, '_event_date', true),
            'location' => get_post_meta($post->ID, '_event_location', true),
            'description' => $post->post_content,
            'speaker_name' => get_post_meta($post->ID, '_event_speaker_name', true),
            'capacity' => get_post_meta($post->ID, '_event_capacity', true),
            'ticket_price' => get_post_meta($post->ID, '_event_ticket_price', true)
        ];

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return new WP_REST_Response($data, 200);
    }

    public function reset_to_default($request) {
        $json_file = dirname(dirname(dirname(__FILE__))) . '/defaults/default-events.json';
        if (!file_exists($json_file)) {
            return new WP_Error('no_defaults_file', 'Default-events.json not found', ['status' => 404]);
        }
        $raw = file_get_contents($json_file);
        $defaults = json_decode($raw, true);
        if (!$defaults) {
            return new WP_Error('invalid_json', 'Default file JSON is invalid', ['status' => 500]);
        }

        // Delete all existing events
        $existing = get_posts(['post_type' => 'event', 'numberposts' => -1, 'post_status' => 'any']);
        foreach ($existing as $p) {
            wp_delete_post($p->ID, true);
        }

        $restored = [];
        foreach ($defaults as $index => $e) {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($e['title']),
                'post_name' => sanitize_title($e['slug']),
                'post_content' => wp_kses_post($e['description']),
                'post_status' => 'publish',
                'post_type' => 'event'
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_event_date', sanitize_text_field($e['event_date']));
                update_post_meta($post_id, '_event_location', sanitize_text_field($e['location']));
                update_post_meta($post_id, '_event_short_description', sanitize_text_field($e['short_description']));
                update_post_meta($post_id, '_event_speaker_name', sanitize_text_field($e['speaker_name']));
                update_post_meta($post_id, '_event_capacity', sanitize_text_field($e['capacity']));
                update_post_meta($post_id, '_event_ticket_price', sanitize_text_field($e['ticket_price']));
                update_post_meta($post_id, '_event_upcoming', $index === 0 ? '1' : '0');

                $restored[] = $e['slug'];
            }
        }

        $this->clear_all_caches();

        return new WP_REST_Response([
            'status' => 'success',
            'message' => 'Events calendar reset to static data contract.',
            'events_restored' => $restored
        ], 200);
    }
}

new WPSync_Event_Plugin();
