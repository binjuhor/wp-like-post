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

define('WPLP_MAIL_TO', get_option('admin_email'));
define('WPLP_RATE_LIMIT_SECONDS', 60);
define('WPLP_PLUGIN_VERSION', '1.0.0');
define('WPLP_PLUGIN_FILE', __FILE__);
define('WPLP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPLP_PLUGIN_URL', plugin_dir_url(__FILE__));

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
}

register_activation_hook(WPLP_PLUGIN_FILE, 'wplp_create_likes_table');

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
        wp_send_json_error(['message' => 'Invalid post'], 400);
    }

    $user_id = get_current_user_id();
    $ip = wplp_get_user_ip();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

    $key = "wplp_rl_{$user_id}_{$ip}_{$post_id}";

    if (get_transient($key)) {
        wp_send_json_error(['message' => 'Please wait before liking again'], 429);
    }

    set_transient($key, 1, WPLP_RATE_LIMIT_SECONDS);

    if (wplp_has_user_liked($post_id)) {
        wp_send_json_error(['message' => 'You have already liked this post'], 403);
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
        wp_send_json_error(['message' => 'Failed to save like'], 500);
    }

    $total_likes = wplp_get_like_count($post_id);

    $title = get_the_title($post_id);
    $permalink = get_permalink($post_id);
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

    $user_info = $user_id > 0
        ? 'User: ' . wp_get_current_user()->display_name . " (ID: {$user_id})"
        : "Guest (IP: {$ip})";

    $subject = "[{$site_name}] New Like: {$title}";
    $body = "A post has just been liked!\n\n"
          . "Title: {$title}\n"
          . "URL: {$permalink}\n"
          . "Total likes: {$total_likes}\n"
          . "{$user_info}\n"
          . 'Time: ' . wp_date('Y-m-d H:i:s') . "\n";

    $headers = ["From: {$site_name} <no-reply@" . parse_url(home_url(), PHP_URL_HOST) . '>'];

    wp_mail(WPLP_MAIL_TO, $subject, $body, $headers);

    wp_send_json_success([
        'message' => 'Liked successfully',
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
        wp_send_json_error(['message' => 'Invalid post'], 400);
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
    $button_text = $has_liked ? 'Liked' : 'Like';

    echo sprintf(
        '<button class="%s" data-post-id="%d"><span class="wplp-like-icon">❤️</span><span class="wplp-like-text">%s</span><span class="wplp-like-count">%d</span></button>',
        esc_attr($button_class),
        esc_attr($post_id),
        esc_html($button_text),
        $likes
    );
}
