<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Share_Count {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_wpssl_share',        [ $this, 'handle_ajax' ] );
        add_action( 'wp_ajax_nopriv_wpssl_share', [ $this, 'handle_ajax' ] );
    }

    public function handle_ajax() {
        check_ajax_referer( 'wpssl_nonce', 'nonce' );

        $platform = sanitize_text_field( $_POST['platform'] ?? '' );
        $post_id  = intval( $_POST['post_id'] ?? 0 );

        if ( ! $post_id || ! $platform ) {
            wp_send_json_error( 'Invalid data' );
        }

        $key   = '_wpssl_count_' . $platform;
        $count = intval( get_post_meta( $post_id, $key, true ) );
        update_post_meta( $post_id, $key, $count + 1 );

        $total = $this->get_total( $post_id );
        wp_send_json_success( [ 'count' => $count + 1, 'total' => $total ] );
    }

    public static function get( $post_id, $platform ) {
        return intval( get_post_meta( $post_id, '_wpssl_count_' . $platform, true ) );
    }

    public static function get_total( $post_id ) {
        $total = 0;
        foreach ( array_keys( WPSSL_Networks::all() ) as $network ) {
            $total += self::get( $post_id, $network );
        }
        return $total;
    }

    /**
     * Get all share stats for dashboard display
     */
    public static function get_top_posts( $limit = 10 ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_id, SUM(CAST(meta_value AS UNSIGNED)) as total_shares
             FROM {$wpdb->postmeta}
             WHERE meta_key LIKE %s
             GROUP BY post_id
             ORDER BY total_shares DESC
             LIMIT %d",
            '_wpssl_count_%',
            $limit
        ) );
        return $results;
    }

    public static function get_network_totals() {
        global $wpdb;
        $networks = array_keys( WPSSL_Networks::all() );
        $totals   = [];
        foreach ( $networks as $network ) {
            $key   = '_wpssl_count_' . $network;
            $total = $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                $key
            ) );
            $totals[ $network ] = intval( $total );
        }
        return $totals;
    }
}
