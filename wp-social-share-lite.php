<?php
/**
 * Plugin Name: WP Social Share Lite
 * Plugin URI:  https://wyarejali.com/plugins/wp-social-share-lite
 * Description: Auto-inject customizable social sharing buttons into posts, pages, and custom post types with share count tracking, floating bar, Open Graph tags, and a full settings dashboard.
 * Version:     2.0.1
 * Author:      Wyarej Ali
 * Author URI:  https://wyarejali.com
 * License:     GPL-2.0+
 * Text Domain: wp-social-share-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'WPSSL_VERSION',     '2.0.1' );
define( 'WPSSL_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPSSL_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPSSL_PLUGIN_FILE', __FILE__ );

// Load includes
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-options.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-og-tags.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-share-count.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-networks.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-renderer.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-injector.php';
require_once WPSSL_PLUGIN_DIR . 'includes/class-wpssl-meta-box.php';
require_once WPSSL_PLUGIN_DIR . 'admin/class-wpssl-admin.php';

/**
 * Main plugin class
 */
final class WP_Social_Share_Lite {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        register_activation_hook( WPSSL_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( WPSSL_PLUGIN_FILE, [ $this, 'deactivate' ] );
    }

    public function init() {
        // Init components
        WPSSL_Options::instance();
        WPSSL_OG_Tags::instance();
        WPSSL_Share_Count::instance();
        WPSSL_Injector::instance();
        WPSSL_Meta_Box::instance();

        if ( is_admin() ) {
            WPSSL_Admin::instance();
        }
    }

    public function activate() {
        WPSSL_Options::set_defaults();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

WP_Social_Share_Lite::instance();
