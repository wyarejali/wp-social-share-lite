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
        // Suppress WP admin notices inside our page header
        add_action( 'in_admin_header', [ $this, 'suppress_notices' ], 1 );
    }

    /**
     * Removes admin_notices hooks only on our pages so they don't bleed into header.
     * We re-print them manually below the header.
     */
    public function suppress_notices() {
        $screen = get_current_screen();
        if ( ! $screen ) return;
        if ( strpos( $screen->id, 'wp-social-share-lite' ) === false &&
             strpos( $screen->id, 'wpssl-stats' ) === false ) return;

        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
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

        wp_enqueue_style( 'wpssl-admin', WPSSL_PLUGIN_URL . 'admin/css/wpssl-admin.css', [], WPSSL_VERSION );
        wp_enqueue_style( 'wpssl-public-preview', WPSSL_PLUGIN_URL . 'public/css/wpssl-public.css', [], WPSSL_VERSION );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'wpssl-admin', WPSSL_PLUGIN_URL . 'admin/js/wpssl-admin.js', [ 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ], WPSSL_VERSION, true );

        wp_localize_script( 'wpssl-admin', 'WPSSLAdmin', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'wpssl_nonce' ),
            'plugin_url' => WPSSL_PLUGIN_URL,
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

        $opts    = $_POST['opts'] ?? [];
        $preview = [
            'networks'            => array_map( 'sanitize_text_field', (array)( $opts['networks'] ?? [] ) ),
            'icon_style'          => sanitize_text_field( $opts['icon_style'] ?? 'icon_label' ),
            'show_share_count'    => sanitize_text_field( $opts['show_share_count'] ?? '1' ),
            'show_total_count'    => sanitize_text_field( $opts['show_total_count'] ?? '1' ),
            'show_label'          => sanitize_text_field( $opts['show_label'] ?? '1' ),
            'label_text'          => sanitize_text_field( $opts['label_text'] ?? 'Share this:' ),
            'button_shape'        => sanitize_text_field( $opts['button_shape'] ?? 'rounded' ),
            'button_size'         => sanitize_text_field( $opts['button_size'] ?? 'medium' ),
            'color_scheme'        => sanitize_text_field( $opts['color_scheme'] ?? 'brand' ),
            'custom_bg'           => sanitize_hex_color( $opts['custom_bg'] ?? '#333333' ),
            'custom_text'         => sanitize_hex_color( $opts['custom_text'] ?? '#ffffff' ),
            'hide_on_mobile'      => '0',
            'custom_link_enabled' => sanitize_text_field( $opts['custom_link_enabled'] ?? '0' ),
            'custom_link_label'   => sanitize_text_field( $opts['custom_link_label'] ?? 'Visit' ),
            'custom_link_url'     => esc_url_raw( $opts['custom_link_url'] ?? '' ),
            'custom_link_color'   => sanitize_hex_color( $opts['custom_link_color'] ?? '#6366f1' ),
        ];

        $posts   = get_posts( [ 'numberposts' => 1, 'post_status' => 'publish' ] );
        $post_id = $posts ? $posts[0]->ID : 0;

        wp_send_json_success( [ 'html' => WPSSL_Renderer::render( $post_id, $preview ) ] );
    }

    private function render_save_button( $id = '' ) {
        $id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
        echo '<button type="submit" form="wpssl-settings-form" class="wpssl-save-btn"' . $id_attr . '>'
           . '<span class="dashicons dashicons-yes-alt"></span> '
           . esc_html__( 'Save Settings', 'wp-social-share-lite' )
           . '</button>';
    }

    private function header( $active_tab = 'settings' ) {
        ?>
        <div class="wpssl-header">
            <div class="wpssl-header-inner">
                <div class="wpssl-header-top">
                    <div class="wpssl-brand">
                        <span class="dashicons dashicons-share wpssl-brand-icon"></span>
                        <div class="wpssl-brand-text">
                            <h1 class="wpssl-brand-name">WP Social Share Lite</h1>
                            <p class="wpssl-brand-desc">Effortless social sharing for WordPress &mdash; By <strong>Wyarej Ali</strong></p>
                        </div>
                        <span class="wpssl-version-badge">v<?php echo WPSSL_VERSION; ?></span>
                    </div>
                    <?php if ( $active_tab === 'settings' ) : ?>
                    <div class="wpssl-header-actions">
                        <?php $this->render_save_button( 'wpssl-top-save' ); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <nav class="wpssl-tabs">
                    <a href="<?php echo admin_url('admin.php?page=wp-social-share-lite'); ?>"
                       class="wpssl-tab<?php echo $active_tab === 'settings' ? ' wpssl-tab--active' : ''; ?>">
                        <span class="dashicons dashicons-admin-settings"></span> <?php _e('Settings', 'wp-social-share-lite'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wpssl-stats'); ?>"
                       class="wpssl-tab<?php echo $active_tab === 'stats' ? ' wpssl-tab--active' : ''; ?>">
                        <span class="dashicons dashicons-chart-bar"></span> <?php _e('Share Stats', 'wp-social-share-lite'); ?>
                    </a>
                </nav>
            </div>
        </div>
        <?php
    }

    public function render_page() {
        $opts       = WPSSL_Options::instance();
        $saved      = isset( $_GET['saved'] );
        $all_pts    = get_post_types( [ 'public' => true ], 'objects' );
        $all_nets   = WPSSL_Networks::all();
        $has_aioseo = defined('AIOSEO_VERSION') || class_exists('AIOSEO\Plugin\AIOSEO');
        ?>
        <div class="wpssl-page-wrap">

            <?php $this->header( 'settings' ); ?>

            <div class="wpssl-notices-area">
                <?php if ( $saved ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php _e( 'Settings saved successfully!', 'wp-social-share-lite' ); ?></p></div>
                <?php endif; ?>
                <?php if ( $has_aioseo ) : ?>
                    <div class="notice notice-info"><p><?php printf( __( '<strong>WP Social Share Lite:</strong> All in One SEO detected — Open Graph tags have been automatically disabled to prevent conflicts.', 'wp-social-share-lite' ) ); ?></p></div>
                <?php endif; ?>
            </div>

            <div class="wpssl-admin-layout">

                <div class="wpssl-settings-panel">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="wpssl-settings-form">
                        <?php wp_nonce_field( 'wpssl_settings_save' ); ?>
                        <input type="hidden" name="action" value="wpssl_save">

                        <!-- GENERAL -->
                        <div class="wpssl-card">
                            <h2 class="wpssl-card-title"><?php _e( 'General', 'wp-social-share-lite' ); ?></h2>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Enable Plugin', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="enabled" value="1" <?php checked( $opts->get('enabled'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Post Types', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <?php foreach ( $all_pts as $pt ) : ?>
                                    <label class="wpssl-checkbox-label">
                                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
                                            <?php checked( in_array( $pt->name, (array) $opts->get('post_types') ) ); ?>>
                                        <?php echo esc_html( $pt->label ); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Display Position', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <?php
                                    $positions = [
                                        'before'         => 'Before content',
                                        'after'          => 'After content',
                                        'both'           => 'Before &amp; After content',
                                        'floating_left'  => 'Floating — Left sidebar',
                                        'floating_right' => 'Floating — Right sidebar',
                                        'sticky_bottom'  => 'Sticky bottom bar',
                                    ];
                                    foreach ( $positions as $val => $label ) : ?>
                                    <label class="wpssl-radio-label">
                                        <input type="radio" name="position" value="<?php echo esc_attr( $val ); ?>" <?php checked( $opts->get('position'), $val ); ?>>
                                        <?php echo $label; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Exclude Post IDs', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <input type="text" name="exclude_ids" value="<?php echo esc_attr( $opts->get('exclude_ids') ); ?>" class="regular-text" placeholder="e.g. 12, 45, 78">
                                    <p class="description"><?php _e( 'Comma-separated post/page IDs to exclude.', 'wp-social-share-lite' ); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- NETWORKS -->
                        <div class="wpssl-card">
                            <h2 class="wpssl-card-title"><?php _e( 'Social Networks', 'wp-social-share-lite' ); ?></h2>
                            <p class="wpssl-card-desc"><?php _e( 'Drag to reorder. Check to enable.', 'wp-social-share-lite' ); ?></p>
                            <ul id="wpssl-networks-list" class="wpssl-networks-list">
                                <?php
                                $active_nets = (array) $opts->get('networks');
                                $ordered = array_merge(
                                    array_filter( array_keys($all_nets), fn($k) => in_array($k, $active_nets) ),
                                    array_filter( array_keys($all_nets), fn($k) => !in_array($k, $active_nets) )
                                );
                                foreach ( $ordered as $key ) :
                                    $net     = $all_nets[$key];
                                    $checked = in_array( $key, $active_nets );
                                ?>
                                <li class="wpssl-network-item" data-key="<?php echo esc_attr($key); ?>">
                                    <span class="wpssl-drag-handle" title="Drag to reorder">
                                        <svg width="14" height="14" viewBox="0 0 16 16" fill="#999"><circle cx="5" cy="4" r="1.5"/><circle cx="11" cy="4" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="12" r="1.5"/><circle cx="11" cy="12" r="1.5"/></svg>
                                    </span>
                                    <input type="checkbox" class="wpssl-net-checkbox" name="networks[]" value="<?php echo esc_attr($key); ?>" <?php checked($checked); ?>>
                                    <span class="wpssl-net-dot" style="background:<?php echo esc_attr($net['color']); ?>"></span>
                                    <span class="wpssl-net-name"><?php echo esc_html($net['label']); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="wpssl-divider"></div>
                            <h3 class="wpssl-subcard-title"><?php _e( 'Custom Link Button', 'wp-social-share-lite' ); ?></h3>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Enable Custom Button', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="custom_link_enabled" value="1" <?php checked( $opts->get('custom_link_enabled'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Button Label', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <input type="text" name="custom_link_label" value="<?php echo esc_attr( $opts->get('custom_link_label', 'Visit') ); ?>" class="regular-text" placeholder="Visit">
                                </div>
                            </div>
                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'URL', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <input type="url" name="custom_link_url" value="<?php echo esc_url( $opts->get('custom_link_url', '') ); ?>" class="regular-text" placeholder="https://example.com">
                                    <p class="description"><?php _e( 'Leave empty to use the current post URL.', 'wp-social-share-lite' ); ?></p>
                                </div>
                            </div>
                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Button Color', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <input type="text" name="custom_link_color" value="<?php echo esc_attr( $opts->get('custom_link_color', '#6366f1') ); ?>" class="wpssl-color-picker">
                                </div>
                            </div>
                        </div>

                        <!-- DISPLAY -->
                        <div class="wpssl-card">
                            <h2 class="wpssl-card-title"><?php _e( 'Display Options', 'wp-social-share-lite' ); ?></h2>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Icon Style', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-radio-row">
                                    <label class="wpssl-radio-label"><input type="radio" name="icon_style" value="icon_only" <?php checked( $opts->get('icon_style'), 'icon_only' ); ?>> <?php _e('Icon only','wp-social-share-lite'); ?></label>
                                    <label class="wpssl-radio-label"><input type="radio" name="icon_style" value="icon_label" <?php checked( $opts->get('icon_style'), 'icon_label' ); ?>> <?php _e('Icon + Label','wp-social-share-lite'); ?></label>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Share Label', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-inline-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="show_label" value="1" <?php checked( $opts->get('show_label'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                    <input type="text" name="label_text" value="<?php echo esc_attr( $opts->get('label_text') ); ?>" class="regular-text" placeholder="Share this:">
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Per-button Count', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-inline-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="show_share_count" value="1" <?php checked( $opts->get('show_share_count'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                    <span class="wpssl-field-hint"><?php _e('Show share count on each button','wp-social-share-lite'); ?></span>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Total Count', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-inline-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="show_total_count" value="1" <?php checked( $opts->get('show_total_count'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                    <span class="wpssl-field-hint"><?php _e('Show total share count above the buttons','wp-social-share-lite'); ?></span>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Hide on Mobile', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="hide_on_mobile" value="1" <?php checked( $opts->get('hide_on_mobile'), '1' ); ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- DESIGN -->
                        <div class="wpssl-card">
                            <h2 class="wpssl-card-title"><?php _e( 'Design', 'wp-social-share-lite' ); ?></h2>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Button Shape', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-radio-row">
                                    <?php foreach ( ['square'=>'Square','rounded'=>'Rounded','pill'=>'Pill'] as $v=>$l ) : ?>
                                    <label class="wpssl-radio-label"><input type="radio" name="button_shape" value="<?php echo $v; ?>" <?php checked($opts->get('button_shape'),$v); ?>> <?php echo $l; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Button Size', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-radio-row">
                                    <?php foreach ( ['small'=>'Small','medium'=>'Medium','large'=>'Large'] as $v=>$l ) : ?>
                                    <label class="wpssl-radio-label"><input type="radio" name="button_size" value="<?php echo $v; ?>" <?php checked($opts->get('button_size'),$v); ?>> <?php echo $l; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Color Scheme', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control">
                                    <div class="wpssl-radio-row">
                                        <label class="wpssl-radio-label"><input type="radio" name="color_scheme" value="brand" <?php checked($opts->get('color_scheme'),'brand'); ?>> <?php _e('Brand colors','wp-social-share-lite'); ?></label>
                                        <label class="wpssl-radio-label"><input type="radio" name="color_scheme" value="monochrome" <?php checked($opts->get('color_scheme'),'monochrome'); ?>> <?php _e('Monochrome','wp-social-share-lite'); ?></label>
                                        <label class="wpssl-radio-label"><input type="radio" name="color_scheme" value="custom" <?php checked($opts->get('color_scheme'),'custom'); ?>> <?php _e('Custom','wp-social-share-lite'); ?></label>
                                    </div>
                                    <div id="wpssl-custom-colors" style="margin-top:12px;<?php echo $opts->get('color_scheme') !== 'custom' ? 'display:none' : ''; ?>">
                                        <div class="wpssl-color-row">
                                            <label class="wpssl-color-label"><?php _e('Background:','wp-social-share-lite'); ?>
                                                <input type="text" name="custom_bg" value="<?php echo esc_attr($opts->get('custom_bg')); ?>" class="wpssl-color-picker">
                                            </label>
                                            <label class="wpssl-color-label"><?php _e('Text color:','wp-social-share-lite'); ?>
                                                <input type="text" name="custom_text" value="<?php echo esc_attr($opts->get('custom_text')); ?>" class="wpssl-color-picker">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OG -->
                        <div class="wpssl-card">
                            <h2 class="wpssl-card-title"><?php _e( 'Open Graph / SEO', 'wp-social-share-lite' ); ?></h2>
                            <div class="wpssl-field">
                                <label class="wpssl-field-label"><?php _e( 'Enable OG Tags', 'wp-social-share-lite' ); ?></label>
                                <div class="wpssl-field-control wpssl-inline-control">
                                    <label class="wpssl-toggle">
                                        <input type="checkbox" name="og_enabled" value="1" <?php checked($opts->get('og_enabled'),'1'); ?> <?php echo $has_aioseo ? 'disabled' : ''; ?>>
                                        <span class="wpssl-toggle-slider"></span>
                                    </label>
                                    <?php if ($has_aioseo) : ?>
                                        <span class="wpssl-badge wpssl-badge--warning"><?php _e('Disabled — AIOSEO detected','wp-social-share-lite'); ?></span>
                                    <?php else : ?>
                                        <span class="wpssl-field-hint"><?php _e('Disable if using Yoast, RankMath, AIOSEO, etc.','wp-social-share-lite'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="wpssl-form-footer">
                            <?php $this->render_save_button(); ?>
                        </div>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="wpssl-preview-panel">
                    <div class="wpssl-preview-card">
                        <div class="wpssl-preview-header">
                            <h3><?php _e('Live Preview','wp-social-share-lite'); ?></h3>
                            <button type="button" id="wpssl-preview-btn" class="wpssl-preview-refresh-btn">
                                <span class="dashicons dashicons-update"></span> <?php _e('Refresh','wp-social-share-lite'); ?>
                            </button>
                        </div>
                        <div class="wpssl-preview-box">
                            <div class="wpssl-mock-article">
                                <div class="wpssl-mock-bar" style="width:65%;height:14px;margin-bottom:10px;"></div>
                                <div class="wpssl-mock-bar" style="width:100%;height:8px;margin-bottom:5px;"></div>
                                <div class="wpssl-mock-bar" style="width:90%;height:8px;margin-bottom:5px;"></div>
                                <div class="wpssl-mock-bar" style="width:80%;height:8px;"></div>
                            </div>
                            <div id="wpssl-preview-output">
                                <p class="wpssl-preview-placeholder"><?php _e('Click Refresh to see your share bar.','wp-social-share-lite'); ?></p>
                            </div>
                            <div class="wpssl-mock-article">
                                <div class="wpssl-mock-bar" style="width:100%;height:8px;margin-bottom:5px;"></div>
                                <div class="wpssl-mock-bar" style="width:75%;height:8px;"></div>
                            </div>
                        </div>
                        <p class="wpssl-preview-note"><?php _e('Showing share counts from your most recent post.','wp-social-share-lite'); ?></p>
                    </div>
                </div>

            </div><!-- /.wpssl-admin-layout -->
        </div><!-- /.wpssl-page-wrap -->
        <?php
    }

    public function render_stats() {
        $network_totals = WPSSL_Share_Count::get_network_totals();
        $top_posts      = WPSSL_Share_Count::get_top_posts(15);
        $all_nets       = WPSSL_Networks::all();
        $grand_total    = array_sum( $network_totals );
        ?>
        <div class="wpssl-page-wrap">
            <?php $this->header('stats'); ?>
            <div class="wpssl-notices-area"></div>
            <div class="wpssl-stats-page">
                <div class="wpssl-stats-grid">
                    <div class="wpssl-stat-card wpssl-stat-card--hero">
                        <div class="wpssl-stat-number"><?php echo number_format($grand_total); ?></div>
                        <div class="wpssl-stat-label"><?php _e('Total Shares','wp-social-share-lite'); ?></div>
                    </div>
                    <?php foreach ($network_totals as $net => $count) :
                        if (!isset($all_nets[$net]) || $count === 0) continue;
                        $nd = $all_nets[$net];
                    ?>
                    <div class="wpssl-stat-card">
                        <div class="wpssl-stat-icon" style="color:<?php echo esc_attr($nd['color']); ?>"><?php echo WPSSL_Networks::get_svg($net); ?></div>
                        <div class="wpssl-stat-number"><?php echo number_format($count); ?></div>
                        <div class="wpssl-stat-label"><?php echo esc_html($nd['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="wpssl-card" style="margin-top:24px">
                    <h2 class="wpssl-card-title"><?php _e('Top Shared Posts','wp-social-share-lite'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr>
                            <th width="30">#</th>
                            <th><?php _e('Post Title','wp-social-share-lite'); ?></th>
                            <th width="100"><?php _e('Type','wp-social-share-lite'); ?></th>
                            <th width="100"><?php _e('Shares','wp-social-share-lite'); ?></th>
                            <th><?php _e('Breakdown','wp-social-share-lite'); ?></th>
                        </tr></thead>
                        <tbody>
                            <?php if (empty($top_posts)) : ?>
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:#9ca3af"><?php _e('No share data yet.','wp-social-share-lite'); ?></td></tr>
                            <?php else : foreach ($top_posts as $i => $row) :
                                $post = get_post($row->post_id);
                                if (!$post) continue;
                            ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" target="_blank"><?php echo esc_html($post->post_title); ?></a>
                                    <br><small><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php _e('Edit','wp-social-share-lite'); ?></a></small>
                                </td>
                                <td><?php echo esc_html(get_post_type($post->ID)); ?></td>
                                <td><strong><?php echo number_format($row->total_shares); ?></strong></td>
                                <td><?php foreach ($all_nets as $net => $nd) :
                                    $c = WPSSL_Share_Count::get($post->ID, $net);
                                    if ($c === 0) continue;
                                ?>
                                <span class="wpssl-net-badge" style="background:<?php echo esc_attr($nd['color']); ?>"><?php echo esc_html($nd['label']); ?>: <?php echo $c; ?></span>
                                <?php endforeach; ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
