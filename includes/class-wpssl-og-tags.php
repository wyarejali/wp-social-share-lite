<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_OG_Tags {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 1 );
    }

    public function output() {
        $opts = WPSSL_Options::instance();
        if ( $opts->get('og_enabled') !== '1' ) return;
        if ( ! is_singular() ) return;

        global $post;
        if ( ! $post ) return;

        $title       = esc_attr( get_the_title( $post ) );
        $url         = esc_url( get_permalink( $post ) );
        $description = esc_attr( $this->get_description( $post ) );
        $image       = esc_url( $this->get_image( $post ) );
        $site_name   = esc_attr( get_bloginfo('name') );
        $type        = is_single() ? 'article' : 'website';

        echo "\n<!-- WP Social Share Lite OG Tags -->\n";
        echo "<meta property=\"og:title\" content=\"{$title}\" />\n";
        echo "<meta property=\"og:url\" content=\"{$url}\" />\n";
        echo "<meta property=\"og:description\" content=\"{$description}\" />\n";
        echo "<meta property=\"og:type\" content=\"{$type}\" />\n";
        echo "<meta property=\"og:site_name\" content=\"{$site_name}\" />\n";
        if ( $image ) {
            echo "<meta property=\"og:image\" content=\"{$image}\" />\n";
        }
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
        echo "<meta name=\"twitter:title\" content=\"{$title}\" />\n";
        echo "<meta name=\"twitter:description\" content=\"{$description}\" />\n";
        if ( $image ) {
            echo "<meta name=\"twitter:image\" content=\"{$image}\" />\n";
        }
        echo "<!-- / WP Social Share Lite OG Tags -->\n\n";
    }

    private function get_description( $post ) {
        if ( $post->post_excerpt ) {
            return wp_strip_all_tags( $post->post_excerpt );
        }
        return wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
    }

    private function get_image( $post ) {
        if ( has_post_thumbnail( $post->ID ) ) {
            return get_the_post_thumbnail_url( $post->ID, 'large' );
        }
        // Fallback: find first image in content
        preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $matches );
        return $matches[1] ?? '';
    }
}
