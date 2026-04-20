<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Injector {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'the_content', [ $this, 'inject' ], 20 );
        add_action( 'wp_footer',   [ $this, 'inject_floating' ], 10 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue() {
        if ( ! $this->should_display() ) return;

        wp_enqueue_style(
            'wpssl-public',
            WPSSL_PLUGIN_URL . 'public/css/wpssl-public.css',
            [],
            WPSSL_VERSION
        );

        wp_enqueue_script(
            'wpssl-public',
            WPSSL_PLUGIN_URL . 'public/js/wpssl-public.js',
            [ 'jquery' ],
            WPSSL_VERSION,
            true
        );

        wp_localize_script( 'wpssl-public', 'WPSSLData', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpssl_nonce' ),
            'post_id'  => get_the_ID(),
            'i18n'     => [
                'copied'     => __( 'Link copied!', 'wp-social-share-lite' ),
                'copy_error' => __( 'Could not copy link.', 'wp-social-share-lite' ),
            ],
        ] );
    }

    public function inject( $content ) {
        if ( ! $this->should_display() ) return $content;

        $opts     = WPSSL_Options::instance();
        $position = $opts->get('position');

        if ( in_array( $position, [ 'floating_left', 'floating_right', 'sticky_bottom' ] ) ) {
            return $content;
        }

        $bar = WPSSL_Renderer::render();

        if ( $position === 'before' ) {
            return $bar . $content;
        } elseif ( $position === 'both' ) {
            return $bar . $content . $bar;
        } else {
            return $content . $bar;
        }
    }

    public function inject_floating() {
        if ( ! $this->should_display() ) return;
        echo WPSSL_Renderer::render_floating();
    }

    private function should_display() {
        if ( ! is_singular() ) return false;

        $opts = WPSSL_Options::instance();

        if ( $opts->get('enabled') !== '1' ) return false;

        $post_types = $opts->get('post_types');
        if ( ! in_array( get_post_type(), (array) $post_types ) ) return false;

        // Per-post override: hidden
        $post_id = get_the_ID();
        $override = get_post_meta( $post_id, '_wpssl_override', true );
        if ( $override === 'hide' ) return false;

        // Exclude by ID
        $excluded = array_filter( array_map( 'trim', explode( ',', $opts->get('exclude_ids', '') ) ) );
        if ( in_array( (string) $post_id, $excluded ) ) return false;

        return true;
    }
}
