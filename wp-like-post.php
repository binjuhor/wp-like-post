<?php
/**
 * Plugin Name:       WordPress Like Post
 * Plugin URI:        https://binjuhor.com/plugins/wp-like-post
 * Description:       A comprehensive like system for WordPress posts with AJAX functionality, database storage, rate limiting, and email notifications to admin.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Binjuhor
 * Author URI:        https://github.com/binjuhor
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       like-system
 * Domain Path:       /languages
 *
 * @package LikeSystem
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WPLP_RATE_LIMIT_SECONDS', 60);
define('WPLP_PLUGIN_VERSION', '1.0.0');
define('WPLP_PLUGIN_FILE', __FILE__);
define('WPLP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPLP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load plugin text domain for translations
 */
function wplp_load_textdomain()
{
    load_plugin_textdomain(
        'like-system',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

add_action('plugins_loaded', 'wplp_load_textdomain');

/**
 * Create database table on plugin activation
 */
function wplp_create_likes_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_likes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        ip_address varchar(45) NOT NULL,
        user_agent varchar(255) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY ip_address (ip_address),
        KEY created_at (created_at),
        UNIQUE KEY unique_like (post_id, user_id, ip_address)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('wplp_like_db_version', WPLP_PLUGIN_VERSION);

    if (! get_option('wplp_notification_email')) {
        update_option('wplp_notification_email', get_option('admin_email'));
    }
}

register_activation_hook(WPLP_PLUGIN_FILE, 'wplp_create_likes_table');

/**
 * Get notification email address
 */
function wplp_get_notification_email()
{
    $email = get_option('wplp_notification_email');

    if (! $email) {
        $email = get_option('admin_email');
    }

    return sanitize_email($email);
}

/**
 * Register plugin settings
 */
function wplp_register_settings()
{
    register_setting('wplp_settings_group', 'wplp_notification_email', [
        'type' => 'string',
        'sanitize_callback' => 'wplp_sanitize_email_setting',
        'default' => get_option('admin_email'),
    ]);

    add_settings_section(
        'wplp_email_section',
        __('Email Notifications', 'like-system'),
        'wplp_email_section_callback',
        'wplp-settings'
    );

    add_settings_field(
        'wplp_notification_email',
        __('Notification Email', 'like-system'),
        'wplp_notification_email_callback',
        'wplp-settings',
        'wplp_email_section'
    );
}

add_action('admin_init', 'wplp_register_settings');

/**
 * Sanitize email setting
 */
function wplp_sanitize_email_setting($email)
{
    $email = sanitize_email($email);

    if (! is_email($email)) {
        add_settings_error(
            'wplp_notification_email',
            'invalid_email',
            __('Please enter a valid email address.', 'like-system'),
            'error'
        );

        return get_option('wplp_notification_email');
    }

    return $email;
}

/**
 * Email section description
 */
function wplp_email_section_callback()
{
    echo '<p>' . esc_html__('Configure email notifications for new likes.', 'like-system') . '</p>';
}

/**
 * Notification email field
 */
function wplp_notification_email_callback()
{
    $email = get_option('wplp_notification_email', get_option('admin_email'));

    echo sprintf(
        '<input type="email" name="wplp_notification_email" value="%s" class="regular-text" required />',
        esc_attr($email)
    );
    echo '<p class="description">' . esc_html__('Email address to receive like notifications. Defaults to WordPress admin email.', 'like-system') . '</p>';
}

/**
 * Add admin menu
 */
function wplp_add_admin_menu()
{
    add_options_page(
        __('Like System Settings', 'like-system'),
        __('Like System', 'like-system'),
        'manage_options',
        'wplp-settings',
        'wplp_settings_page'
    );
}

add_action('admin_menu', 'wplp_add_admin_menu');

/**
 * Settings page
 */
function wplp_settings_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'wplp_messages',
            'wplp_message',
            __('Settings saved successfully!', 'like-system'),
            'success'
        );
    }

    settings_errors('wplp_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wplp_settings_group');
            do_settings_sections('wplp-settings');
            submit_button(__('Save Settings', 'like-system'));
            ?>
        </form>

        <div class="wplp-stats" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e('Statistics', 'like-system'); ?></h2>
            <?php wplp_display_statistics(); ?>
        </div>
    </div>
    <?php
}

/**
 * Display like statistics
 */
function wplp_display_statistics()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_likes';

    $total_likes = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_name} WHERE user_id IS NOT NULL");
    $total_guests = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE user_id IS NULL");
    $total_posts = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$table_name}");

    echo '<table class="widefat striped">';
    echo '<tbody>';
    echo '<tr><td><strong>' . esc_html__('Total Likes', 'like-system') . '</strong></td><td>' . number_format($total_likes) . '</td></tr>';
    echo '<tr><td><strong>' . esc_html__('Liked Posts', 'like-system') . '</strong></td><td>' . number_format($total_posts) . '</td></tr>';
    echo '<tr><td><strong>' . esc_html__('Registered User Likes', 'like-system') . '</strong></td><td>' . number_format($total_users) . '</td></tr>';
    echo '<tr><td><strong>' . esc_html__('Guest Likes', 'like-system') . '</strong></td><td>' . number_format($total_guests) . '</td></tr>';
    echo '</tbody>';
    echo '</table>';

    $top_posts = $wpdb->get_results(
        "SELECT post_id, COUNT(*) as like_count
         FROM {$table_name}
         GROUP BY post_id
         ORDER BY like_count DESC
         LIMIT 10"
    );

    if ($top_posts) {
        echo '<h3 style="margin-top: 20px;">' . esc_html__('Top 10 Most Liked Posts', 'like-system') . '</h3>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Post', 'like-system') . '</th><th>' . esc_html__('Likes', 'like-system') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($top_posts as $post) {
            $post_title = get_the_title($post->post_id);
            $post_link = get_edit_post_link($post->post_id);

            echo '<tr>';
            echo '<td><a href="' . esc_url($post_link) . '">' . esc_html($post_title) . '</a></td>';
            echo '<td>' . number_format($post->like_count) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}

/**
 * Enqueue scripts and styles
 */
function wplp_enqueue_assets()
{
    wp_register_style(
        'wplp-like-style',
        WPLP_PLUGIN_URL . 'css/wplp-like.css',
        [],
        WPLP_PLUGIN_VERSION
    );
    wp_enqueue_style('wplp-like-style');

    wp_register_script(
        'wplp-like',
        WPLP_PLUGIN_URL . 'js/wplp-like.js',
        ['jquery'],
        WPLP_PLUGIN_VERSION,
        true
    );

    wp_localize_script('wplp-like', 'WPLP_AJAX', [
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wplp_like_nonce'),
    ]);

    wp_enqueue_script('wplp-like');
}

add_action('wp_enqueue_scripts', 'wplp_enqueue_assets');

/**
 * Helper: Get user's IP address
 */
function wplp_get_user_ip()
{
    $ip = '0.0.0.0';

    if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (! empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return sanitize_text_field($ip);
}

/**
 * Helper: Check if user has already liked the post
 */
function wplp_has_user_liked($post_id)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_likes';
    $user_id = get_current_user_id();
    $ip = wplp_get_user_ip();

    if ($user_id > 0) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND user_id = %d",
            $post_id,
            $user_id
        ));
    } else {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND ip_address = %s AND user_id IS NULL",
            $post_id,
            $ip
        ));
    }

    return (int) $exists > 0;
}

/**
 * Helper: Get like count for a post
 */
function wplp_get_like_count($post_id)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_likes';

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
        $post_id
    ));

    return (int) $count;
}

/**
 * AJAX Handler: Add like
 */
add_action('wp_ajax_wplp_send_like', 'wplp_send_like_handler');
add_action('wp_ajax_nopriv_wplp_send_like', 'wplp_send_like_handler');

function wplp_send_like_handler()
{
    check_ajax_referer('wplp_like_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0 || get_post_status($post_id) === false) {
        wp_send_json_error(['message' => __('Invalid post', 'like-system')], 400);
    }

    $user_id = get_current_user_id();
    $ip = wplp_get_user_ip();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

    $key = "wplp_rl_{$user_id}_{$ip}_{$post_id}";

    if (get_transient($key)) {
        wp_send_json_error(['message' => __('Please wait before liking again', 'like-system')], 429);
    }

    set_transient($key, 1, WPLP_RATE_LIMIT_SECONDS);

    if (wplp_has_user_liked($post_id)) {
        wp_send_json_error(['message' => __('You have already liked this post', 'like-system')], 403);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $inserted = $wpdb->insert(
        $table_name,
        [
            'post_id' => $post_id,
            'user_id' => $user_id > 0 ? $user_id : null,
            'ip_address' => $ip,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if (! $inserted) {
        wp_send_json_error(['message' => __('Failed to save like', 'like-system')], 500);
    }

    $total_likes = wplp_get_like_count($post_id);

    $title = get_the_title($post_id);
    $permalink = get_permalink($post_id);
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

    $user_info = $user_id > 0
        ? sprintf(__('User: %1$s (ID: %2$d)', 'like-system'), wp_get_current_user()->display_name, $user_id)
        : sprintf(__('Guest (IP: %s)', 'like-system'), $ip);

    $subject = sprintf(__('[%1$s] New Like: %2$s', 'like-system'), $site_name, $title);
    $body = __('A post has just been liked!', 'like-system') . "\n\n"
          . sprintf(__('Title: %s', 'like-system'), $title) . "\n"
          . sprintf(__('URL: %s', 'like-system'), $permalink) . "\n"
          . sprintf(__('Total likes: %d', 'like-system'), $total_likes) . "\n"
          . $user_info . "\n"
          . sprintf(__('Time: %s', 'like-system'), wp_date('Y-m-d H:i:s')) . "\n";

    $headers = ["From: {$site_name} <no-reply@" . parse_url(home_url(), PHP_URL_HOST) . '>'];

    wp_mail(wplp_get_notification_email(), $subject, $body, $headers);

    wp_send_json_success([
        'message' => __('Liked successfully', 'like-system'),
        'likes' => $total_likes,
        'has_liked' => true,
    ]);
}

/**
 * AJAX Handler: Get like status
 */
add_action('wp_ajax_wplp_get_like_status', 'wplp_get_like_status_handler');
add_action('wp_ajax_nopriv_wplp_get_like_status', 'wplp_get_like_status_handler');

function wplp_get_like_status_handler()
{
    check_ajax_referer('wplp_like_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error(['message' => __('Invalid post', 'like-system')], 400);
    }

    wp_send_json_success([
        'likes' => wplp_get_like_count($post_id),
        'has_liked' => wplp_has_user_liked($post_id),
    ]);
}

/**
 * Render like button (use in template)
 */
function wplp_render_like_button($post_id = null)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }

    $likes = wplp_get_like_count($post_id);
    $has_liked = wplp_has_user_liked($post_id);
    $button_class = $has_liked ? 'wplp-like wplp-liked' : 'wplp-like';
    $button_text = $has_liked ? __('Liked', 'like-system') : __('Like', 'like-system');

    echo sprintf(
        '<button class="%s" data-post-id="%d"><span class="wplp-like-icon">❤️</span><span class="wplp-like-text">%s</span><span class="wplp-like-count">%d</span></button>',
        esc_attr($button_class),
        esc_attr($post_id),
        esc_html($button_text),
        $likes
    );
}

/**
 * Shortcode: Display like button
 * Usage: [wplp_like_button] or [wplp_like_button post_id="123"]
 */
function wplp_like_button_shortcode($atts)
{
    $atts = shortcode_atts([
        'post_id' => null,
    ], $atts, 'wplp_like_button');

    $post_id = ! empty($atts['post_id']) ? intval($atts['post_id']) : get_the_ID();

    if (! $post_id || get_post_status($post_id) === false) {
        return '<p class="wplp-error">' . esc_html__('Invalid post ID', 'like-system') . '</p>';
    }

    $likes = wplp_get_like_count($post_id);
    $has_liked = wplp_has_user_liked($post_id);
    $button_class = $has_liked ? 'wplp-like wplp-liked' : 'wplp-like';
    $button_text = $has_liked ? __('Liked', 'like-system') : __('Like', 'like-system');

    return sprintf(
        '<button class="%s" data-post-id="%d"><span class="wplp-like-icon">❤️</span><span class="wplp-like-text">%s</span><span class="wplp-like-count">%d</span></button>',
        esc_attr($button_class),
        esc_attr($post_id),
        esc_html($button_text),
        $likes
    );
}

add_shortcode('wplp_like_button', 'wplp_like_button_shortcode');
