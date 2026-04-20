<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Meta_Box {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register' ] );
        add_action( 'save_post',      [ $this, 'save' ] );
    }

    public function register() {
        $opts       = WPSSL_Options::instance();
        $post_types = $opts->get('post_types') ?: [ 'post' ];

        foreach ( (array) $post_types as $pt ) {
            add_meta_box(
                'wpssl_meta_box',
                __( 'Social Share Settings', 'wp-social-share-lite' ),
                [ $this, 'render' ],
                $pt,
                'side',
                'default'
            );
        }
    }

    public function render( $post ) {
        wp_nonce_field( 'wpssl_meta_nonce', 'wpssl_meta_nonce' );

        $override = get_post_meta( $post->ID, '_wpssl_override', true );
        $total    = WPSSL_Share_Count::get_total( $post->ID );
        $net_data = WPSSL_Networks::all();
        ?>
        <p>
            <label for="wpssl_override"><strong><?php _e( 'Display Override', 'wp-social-share-lite' ); ?></strong></label><br>
            <select name="wpssl_override" id="wpssl_override" style="width:100%">
                <option value="" <?php selected( $override, '' ); ?>><?php _e( 'Use global settings', 'wp-social-share-lite' ); ?></option>
                <option value="show" <?php selected( $override, 'show' ); ?>><?php _e( 'Force show', 'wp-social-share-lite' ); ?></option>
                <option value="hide" <?php selected( $override, 'hide' ); ?>><?php _e( 'Force hide', 'wp-social-share-lite' ); ?></option>
            </select>
        </p>
        <hr>
        <p><strong><?php _e( 'Share Counts', 'wp-social-share-lite' ); ?></strong></p>
        <table style="width:100%;font-size:12px;">
            <?php foreach ( $net_data as $key => $net ) :
                $count = WPSSL_Share_Count::get( $post->ID, $key );
                if ( $count === 0 ) continue;
            ?>
            <tr>
                <td><?php echo esc_html( $net['label'] ); ?></td>
                <td style="text-align:right"><strong><?php echo esc_html( $count ); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <tr style="border-top:1px solid #ddd">
                <td><strong><?php _e( 'Total', 'wp-social-share-lite' ); ?></strong></td>
                <td style="text-align:right"><strong><?php echo esc_html( $total ); ?></strong></td>
            </tr>
        </table>
        <?php
    }

    public function save( $post_id ) {
        if ( ! isset( $_POST['wpssl_meta_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['wpssl_meta_nonce'], 'wpssl_meta_nonce' ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $override = sanitize_text_field( $_POST['wpssl_override'] ?? '' );
        update_post_meta( $post_id, '_wpssl_override', $override );
    }
}
