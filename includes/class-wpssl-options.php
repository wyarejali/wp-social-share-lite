<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Options {

    const OPTION_KEY = 'wpssl_settings';

    private static $instance = null;
    private $settings = [];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = get_option( self::OPTION_KEY, [] );
    }

    public static function set_defaults() {
        if ( ! get_option( self::OPTION_KEY ) ) {
            $defaults = self::defaults();
            update_option( self::OPTION_KEY, $defaults );
        }
    }

    public static function defaults() {
        return [
            // General
            'enabled'           => '1',
            'post_types'        => [ 'post' ],
            'position'          => 'after',   // before | after | both | floating_left | floating_right | sticky_bottom
            'exclude_ids'       => '',

            // Networks
            'networks'          => [ 'facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'pinterest', 'reddit', 'email', 'copy_link' ],

            // Display
            'icon_style'        => 'icon_label', // icon_only | icon_label
            'show_share_count'  => '1',
            'show_total_count'  => '1',
            'show_label'        => '1',
            'label_text'        => 'Share this:',
            'hide_on_mobile'    => '0',

            // Design
            'button_shape'      => 'rounded',  // square | rounded | pill
            'button_size'       => 'medium',   // small | medium | large
            'color_scheme'      => 'brand',    // brand | custom | monochrome
            'custom_bg'         => '#333333',
            'custom_text'       => '#ffffff',
            'custom_hover_bg'   => '#555555',

            // Open Graph
            'og_enabled'        => '1',

            // Custom Link Button
            'custom_link_enabled' => '0',
            'custom_link_label'   => 'Visit',
            'custom_link_url'     => '',
            'custom_link_color'   => '#6366f1',
        ];
    }

    public function get( $key = null, $fallback = null ) {
        if ( null === $key ) {
            return $this->settings;
        }
        $defaults = self::defaults();
        $value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : $fallback );
        return $value;
    }

    public static function save( $data ) {
        $clean = [];

        $clean['enabled']          = isset( $data['enabled'] ) ? '1' : '0';
        $clean['post_types']       = isset( $data['post_types'] ) && is_array( $data['post_types'] ) ? array_map( 'sanitize_text_field', $data['post_types'] ) : [];
        $clean['position']         = sanitize_text_field( $data['position'] ?? 'after' );
        $clean['exclude_ids']      = sanitize_text_field( $data['exclude_ids'] ?? '' );

        $clean['networks']         = isset( $data['networks'] ) && is_array( $data['networks'] ) ? array_map( 'sanitize_text_field', $data['networks'] ) : [];

        $clean['icon_style']       = sanitize_text_field( $data['icon_style'] ?? 'icon_label' );
        $clean['show_share_count'] = isset( $data['show_share_count'] ) ? '1' : '0';
        $clean['show_total_count'] = isset( $data['show_total_count'] ) ? '1' : '0';
        $clean['show_label']       = isset( $data['show_label'] ) ? '1' : '0';
        $clean['label_text']       = sanitize_text_field( $data['label_text'] ?? 'Share this:' );
        $clean['hide_on_mobile']   = isset( $data['hide_on_mobile'] ) ? '1' : '0';

        $clean['button_shape']     = sanitize_text_field( $data['button_shape'] ?? 'rounded' );
        $clean['button_size']      = sanitize_text_field( $data['button_size'] ?? 'medium' );
        $clean['color_scheme']     = sanitize_text_field( $data['color_scheme'] ?? 'brand' );
        $clean['custom_bg']        = sanitize_hex_color( $data['custom_bg'] ?? '#333333' );
        $clean['custom_text']      = sanitize_hex_color( $data['custom_text'] ?? '#ffffff' );
        $clean['custom_hover_bg']  = sanitize_hex_color( $data['custom_hover_bg'] ?? '#555555' );

        $clean['og_enabled']          = isset( $data['og_enabled'] ) ? '1' : '0';

        $clean['custom_link_enabled'] = isset( $data['custom_link_enabled'] ) ? '1' : '0';
        $clean['custom_link_label']   = sanitize_text_field( $data['custom_link_label'] ?? 'Visit' );
        $clean['custom_link_url']     = esc_url_raw( $data['custom_link_url'] ?? '' );
        $clean['custom_link_color']   = sanitize_hex_color( $data['custom_link_color'] ?? '#6366f1' );

        update_option( self::OPTION_KEY, $clean );
        return $clean;
    }
}
