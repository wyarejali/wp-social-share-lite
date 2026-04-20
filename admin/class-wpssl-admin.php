<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',             [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue' ] );
        add_action( 'admin_post_wpssl_save',  [ $this, 'save_settings' ] );
        add_action( 'wp_ajax_wpssl_preview',  [ $this, 'ajax_preview' ] );
    }

    public function register_menu() {
        add_menu_page(
            __( 'WP Social Share Lite', 'wp-social-share-lite' ),
            __( 'Social Share', 'wp-social-share-lite' ),
            'manage_options',
            'wp-social-share-lite',
            [ $this, 'render_page' ],
            'dashicons-share',
            80
        );

        add_submenu_page(
            'wp-social-share-lite',
            __( 'Settings', 'wp-social-share-lite' ),
            __( 'Settings', 'wp-social-share-lite' ),
            'manage_options',
            'wp-social-share-lite',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'wp-social-share-lite',
            __( 'Share Stats', 'wp-social-share-lite' ),
            __( 'Share Stats', 'wp-social-share-lite' ),
            'manage_options',
            'wpssl-stats',
            [ $this, 'render_stats' ]
        );
    }

    public function enqueue( $hook ) {
        if ( strpos( $hook, 'wp-social-share-lite' ) === false && strpos( $hook, 'wpssl-stats' ) === false ) return;

        wp_enqueue_style(
            'wpssl-admin',
            WPSSL_PLUGIN_URL . 'admin/css/wpssl-admin.css',
            [],
            WPSSL_VERSION
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_script(
            'wpssl-admin',
            WPSSL_PLUGIN_URL . 'admin/js/wpssl-admin.js',
            [ 'jquery', 'wp-color-picker' ],
            WPSSL_VERSION,
            true
        );

        wp_localize_script( 'wpssl-admin', 'WPSSLAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpssl_nonce' ),
        ] );
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'wpssl_settings_save' );

        WPSSL_Options::save( $_POST );

        wp_redirect( admin_url( 'admin.php?page=wp-social-share-lite&saved=1' ) );
        exit;
    }

    public function ajax_preview() {
        check_ajax_referer( 'wpssl_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $opts = $_POST['opts'] ?? [];
        // Sanitize minimal for preview
        $preview = [
            'networks'         => array_map( 'sanitize_text_field', (array) ( $opts['networks'] ?? [] ) ),
            'icon_style'       => sanitize_text_field( $opts['icon_style'] ?? 'icon_label' ),
            'show_share_count' => sanitize_text_field( $opts['show_share_count'] ?? '1' ),
            'show_total_count' => sanitize_text_field( $opts['show_total_count'] ?? '1' ),
            'show_label'       => sanitize_text_field( $opts['show_label'] ?? '1' ),
            'label_text'       => sanitize_text_field( $opts['label_text'] ?? 'Share this:' ),
            'button_shape'     => sanitize_text_field( $opts['button_shape'] ?? 'rounded' ),
            'button_size'      => sanitize_text_field( $opts['button_size'] ?? 'medium' ),
            'color_scheme'     => sanitize_text_field( $opts['color_scheme'] ?? 'brand' ),
            'custom_bg'        => sanitize_hex_color( $opts['custom_bg'] ?? '#333333' ),
            'custom_text'      => sanitize_hex_color( $opts['custom_text'] ?? '#ffffff' ),
            'hide_on_mobile'   => '0',
        ];

        // Use a recent post for preview
        $posts   = get_posts( [ 'numberposts' => 1 ] );
        $post_id = $posts ? $posts[0]->ID : 0;

        wp_send_json_success( [ 'html' => WPSSL_Renderer::render( $post_id, $preview ) ] );
    }

    public function render_page() {
        $opts    = WPSSL_Options::instance();
        $saved   = isset( $_GET['saved'] );
        $all_pts = get_post_types( [ 'public' => true ], 'objects' );
        $all_nets= WPSSL_Networks::all();
        ?>
        <div class="wrap wpssl-admin-wrap">
            <div class="wpssl-admin-header">
                <h1><?php _e( 'WP Social Share Lite', 'wp-social-share-lite' ); ?></h1>
                <span class="wpssl-version">v<?php echo WPSSL_VERSION; ?></span>
            </div>

            <?php if ( $saved ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php _e( 'Settings saved!', 'wp-social-share-lite' ); ?></p></div>
            <?php endif; ?>

            <div class="wpssl-admin-layout">
                <!-- Settings Form -->
                <div class="wpssl-settings-panel">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="wpssl-settings-form">
                        <?php wp_nonce_field( 'wpssl_settings_save' ); ?>
                        <input type="hidden" name="action" value="wpssl_save">

                        <!-- GENERAL -->
                        <div class="wpssl-section">
                            <h2 class="wpssl-section-title"><?php _e( 'General', 'wp-social-share-lite' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Enable Plugin', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="enabled" value="1" <?php checked( $opts->get('enabled'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Post Types', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <?php foreach ( $all_pts as $pt ) : ?>
                                        <label style="display:block;margin-bottom:5px;">
                                            <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
                                                <?php checked( in_array( $pt->name, (array) $opts->get('post_types') ) ); ?>>
                                            <?php echo esc_html( $pt->label ); ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Display Position', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <?php
                                        $positions = [
                                            'before'         => __( 'Before content', 'wp-social-share-lite' ),
                                            'after'          => __( 'After content', 'wp-social-share-lite' ),
                                            'both'           => __( 'Before & After content', 'wp-social-share-lite' ),
                                            'floating_left'  => __( 'Floating — Left sidebar', 'wp-social-share-lite' ),
                                            'floating_right' => __( 'Floating — Right sidebar', 'wp-social-share-lite' ),
                                            'sticky_bottom'  => __( 'Sticky bottom bar', 'wp-social-share-lite' ),
                                        ];
                                        foreach ( $positions as $val => $label ) : ?>
                                        <label style="display:block;margin-bottom:5px;">
                                            <input type="radio" name="position" value="<?php echo esc_attr( $val ); ?>"
                                                <?php checked( $opts->get('position'), $val ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Exclude Post IDs', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <input type="text" name="exclude_ids" value="<?php echo esc_attr( $opts->get('exclude_ids') ); ?>" class="regular-text" placeholder="e.g. 12, 45, 78">
                                        <p class="description"><?php _e( 'Comma-separated post/page IDs to exclude.', 'wp-social-share-lite' ); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- NETWORKS -->
                        <div class="wpssl-section">
                            <h2 class="wpssl-section-title"><?php _e( 'Social Networks', 'wp-social-share-lite' ); ?></h2>
                            <p class="description" style="margin-bottom:12px"><?php _e( 'Drag to reorder. Check to enable.', 'wp-social-share-lite' ); ?></p>
                            <ul id="wpssl-networks-list" class="wpssl-networks-list">
                                <?php
                                $active_nets = (array) $opts->get('networks');
                                // Show active first, then inactive
                                $ordered = array_merge(
                                    array_filter( array_keys( $all_nets ), fn($k) => in_array( $k, $active_nets ) ),
                                    array_filter( array_keys( $all_nets ), fn($k) => ! in_array( $k, $active_nets ) )
                                );
                                foreach ( $ordered as $key ) :
                                    $net = $all_nets[ $key ];
                                    $checked = in_array( $key, $active_nets );
                                ?>
                                <li class="wpssl-network-item" data-key="<?php echo esc_attr( $key ); ?>">
                                    <span class="wpssl-drag-handle dashicons dashicons-move"></span>
                                    <label>
                                        <input type="checkbox" name="networks[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?>>
                                        <span class="wpssl-net-dot" style="background:<?php echo esc_attr( $net['color'] ); ?>"></span>
                                        <?php echo esc_html( $net['label'] ); ?>
                                    </label>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- DISPLAY -->
                        <div class="wpssl-section">
                            <h2 class="wpssl-section-title"><?php _e( 'Display Options', 'wp-social-share-lite' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Icon Style', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label><input type="radio" name="icon_style" value="icon_only" <?php checked( $opts->get('icon_style'), 'icon_only' ); ?>> <?php _e( 'Icon only', 'wp-social-share-lite' ); ?></label>
                                        &nbsp;&nbsp;
                                        <label><input type="radio" name="icon_style" value="icon_label" <?php checked( $opts->get('icon_style'), 'icon_label' ); ?>> <?php _e( 'Icon + Label', 'wp-social-share-lite' ); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Show Share Label', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="show_label" value="1" <?php checked( $opts->get('show_label'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                        <input type="text" name="label_text" value="<?php echo esc_attr( $opts->get('label_text') ); ?>" class="regular-text" placeholder="Share this:">
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Show Share Count', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="show_share_count" value="1" <?php checked( $opts->get('show_share_count'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                        <span class="description"><?php _e( 'Show per-network count on buttons', 'wp-social-share-lite' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Show Total Count', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="show_total_count" value="1" <?php checked( $opts->get('show_total_count'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                        <span class="description"><?php _e( 'Show total share count above buttons', 'wp-social-share-lite' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Hide on Mobile', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="hide_on_mobile" value="1" <?php checked( $opts->get('hide_on_mobile'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- DESIGN -->
                        <div class="wpssl-section">
                            <h2 class="wpssl-section-title"><?php _e( 'Design', 'wp-social-share-lite' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Button Shape', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <?php foreach ( [ 'square' => 'Square', 'rounded' => 'Rounded', 'pill' => 'Pill' ] as $val => $lbl ) : ?>
                                        <label style="margin-right:15px">
                                            <input type="radio" name="button_shape" value="<?php echo $val; ?>" <?php checked( $opts->get('button_shape'), $val ); ?>>
                                            <?php echo $lbl; ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Button Size', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <?php foreach ( [ 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large' ] as $val => $lbl ) : ?>
                                        <label style="margin-right:15px">
                                            <input type="radio" name="button_size" value="<?php echo $val; ?>" <?php checked( $opts->get('button_size'), $val ); ?>>
                                            <?php echo $lbl; ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Color Scheme', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label style="margin-right:15px"><input type="radio" name="color_scheme" value="brand" <?php checked( $opts->get('color_scheme'), 'brand' ); ?>> <?php _e( 'Brand colors', 'wp-social-share-lite' ); ?></label>
                                        <label style="margin-right:15px"><input type="radio" name="color_scheme" value="monochrome" <?php checked( $opts->get('color_scheme'), 'monochrome' ); ?>> <?php _e( 'Monochrome', 'wp-social-share-lite' ); ?></label>
                                        <label><input type="radio" name="color_scheme" value="custom" <?php checked( $opts->get('color_scheme'), 'custom' ); ?>> <?php _e( 'Custom', 'wp-social-share-lite' ); ?></label>
                                        <div id="wpssl-custom-colors" style="margin-top:10px;<?php echo $opts->get('color_scheme') !== 'custom' ? 'display:none' : ''; ?>">
                                            <label><?php _e( 'Background:', 'wp-social-share-lite' ); ?>
                                                <input type="text" name="custom_bg" value="<?php echo esc_attr( $opts->get('custom_bg') ); ?>" class="wpssl-color-picker">
                                            </label>
                                            &nbsp;&nbsp;
                                            <label><?php _e( 'Text:', 'wp-social-share-lite' ); ?>
                                                <input type="text" name="custom_text" value="<?php echo esc_attr( $opts->get('custom_text') ); ?>" class="wpssl-color-picker">
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- OPEN GRAPH -->
                        <div class="wpssl-section">
                            <h2 class="wpssl-section-title"><?php _e( 'Open Graph / SEO', 'wp-social-share-lite' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Enable OG Tags', 'wp-social-share-lite' ); ?></th>
                                    <td>
                                        <label class="wpssl-toggle">
                                            <input type="checkbox" name="og_enabled" value="1" <?php checked( $opts->get('og_enabled'), '1' ); ?>>
                                            <span class="wpssl-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e( 'Auto-injects Open Graph and Twitter Card meta tags for better social previews. Disable if using a dedicated SEO plugin (Yoast, RankMath, etc.).', 'wp-social-share-lite' ); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p class="submit">
                            <button type="submit" class="button button-primary button-large"><?php _e( 'Save Settings', 'wp-social-share-lite' ); ?></button>
                        </p>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="wpssl-preview-panel">
                    <div class="wpssl-preview-header">
                        <h3><?php _e( 'Live Preview', 'wp-social-share-lite' ); ?></h3>
                        <button type="button" id="wpssl-preview-btn" class="button"><?php _e( 'Refresh Preview', 'wp-social-share-lite' ); ?></button>
                    </div>
                    <div class="wpssl-preview-box">
                        <div class="wpssl-preview-mock-content">
                            <div class="wpssl-mock-title"></div>
                            <div class="wpssl-mock-text"></div>
                            <div class="wpssl-mock-text wpssl-mock-text--short"></div>
                        </div>
                        <div id="wpssl-preview-output">
                            <p class="wpssl-preview-placeholder"><?php _e( 'Click "Refresh Preview" to see how the share bar will look.', 'wp-social-share-lite' ); ?></p>
                        </div>
                        <div class="wpssl-preview-mock-content">
                            <div class="wpssl-mock-text"></div>
                            <div class="wpssl-mock-text wpssl-mock-text--short"></div>
                        </div>
                    </div>
                    <p class="description" style="margin-top:8px"><?php _e( 'Share counts shown are from your most recent post.', 'wp-social-share-lite' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_stats() {
        $network_totals = WPSSL_Share_Count::get_network_totals();
        $top_posts      = WPSSL_Share_Count::get_top_posts( 15 );
        $all_nets       = WPSSL_Networks::all();
        $grand_total    = array_sum( $network_totals );
        ?>
        <div class="wrap wpssl-admin-wrap">
            <div class="wpssl-admin-header">
                <h1><?php _e( 'Share Stats', 'wp-social-share-lite' ); ?></h1>
                <span class="wpssl-version">v<?php echo WPSSL_VERSION; ?></span>
            </div>

            <div class="wpssl-stats-grid">
                <!-- Grand Total -->
                <div class="wpssl-stat-card wpssl-stat-card--big">
                    <div class="wpssl-stat-number"><?php echo esc_html( number_format( $grand_total ) ); ?></div>
                    <div class="wpssl-stat-label"><?php _e( 'Total Shares', 'wp-social-share-lite' ); ?></div>
                </div>

                <!-- Per Network -->
                <?php foreach ( $network_totals as $net => $count ) :
                    if ( ! isset( $all_nets[ $net ] ) || $count === 0 ) continue;
                    $net_data = $all_nets[ $net ];
                ?>
                <div class="wpssl-stat-card">
                    <div class="wpssl-stat-icon" style="color:<?php echo esc_attr( $net_data['color'] ); ?>"><?php echo WPSSL_Networks::get_svg( $net ); ?></div>
                    <div class="wpssl-stat-number"><?php echo esc_html( number_format( $count ) ); ?></div>
                    <div class="wpssl-stat-label"><?php echo esc_html( $net_data['label'] ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Top Posts Table -->
            <div class="wpssl-section" style="margin-top:30px">
                <h2 class="wpssl-section-title"><?php _e( 'Top Shared Posts', 'wp-social-share-lite' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php _e( 'Post Title', 'wp-social-share-lite' ); ?></th>
                            <th><?php _e( 'Type', 'wp-social-share-lite' ); ?></th>
                            <th><?php _e( 'Total Shares', 'wp-social-share-lite' ); ?></th>
                            <th><?php _e( 'Network Breakdown', 'wp-social-share-lite' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $top_posts ) ) : ?>
                        <tr><td colspan="5"><?php _e( 'No share data yet.', 'wp-social-share-lite' ); ?></td></tr>
                        <?php else :
                            foreach ( $top_posts as $i => $row ) :
                                $post = get_post( $row->post_id );
                                if ( ! $post ) continue;
                        ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank"><?php echo esc_html( $post->post_title ); ?></a>
                                <br><small><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php _e( 'Edit', 'wp-social-share-lite' ); ?></a></small>
                            </td>
                            <td><?php echo esc_html( get_post_type( $post->ID ) ); ?></td>
                            <td><strong><?php echo number_format( $row->total_shares ); ?></strong></td>
                            <td>
                                <?php foreach ( $all_nets as $net => $nd ) :
                                    $c = WPSSL_Share_Count::get( $post->ID, $net );
                                    if ( $c === 0 ) continue;
                                ?>
                                <span style="display:inline-block;margin:2px;font-size:11px;padding:2px 6px;border-radius:3px;background:<?php echo esc_attr( $nd['color'] ); ?>;color:#fff;">
                                    <?php echo esc_html( $nd['label'] ) . ': ' . $c; ?>
                                </span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
