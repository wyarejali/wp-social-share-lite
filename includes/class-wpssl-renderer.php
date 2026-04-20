<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Renderer {

    /**
     * Render the share bar.
     * @param int|null   $post_id         Post ID, or null for current post.
     * @param array|null $preview_options Array of options for admin preview.
     * @return string HTML
     */
    public static function render( $post_id = null, $preview_options = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        // Helper to get a value from either preview array or live options
        $o = function( $key, $default = '' ) use ( $preview_options ) {
            if ( is_array( $preview_options ) ) {
                return isset( $preview_options[ $key ] ) ? $preview_options[ $key ] : $default;
            }
            return WPSSL_Options::instance()->get( $key, $default );
        };

        $networks     = (array) $o( 'networks', [] );
        $icon_style   = $o( 'icon_style',        'icon_label' );
        $show_count   = $o( 'show_share_count',  '1' );
        $show_total   = $o( 'show_total_count',  '1' );
        $show_label   = $o( 'show_label',        '1' );
        $label_text   = $o( 'label_text',        'Share this:' );
        $btn_shape    = $o( 'button_shape',      'rounded' );
        $btn_size     = $o( 'button_size',       'medium' );
        $color_scheme = $o( 'color_scheme',      'brand' );
        $custom_bg    = $o( 'custom_bg',         '#333333' );
        $custom_text  = $o( 'custom_text',       '#ffffff' );
        $hide_mobile  = $o( 'hide_on_mobile',    '0' );
        $cl_enabled   = $o( 'custom_link_enabled','0' );
        $cl_label     = $o( 'custom_link_label', 'Visit' );
        $cl_url       = $o( 'custom_link_url',   '' );
        $cl_color     = $o( 'custom_link_color', '#6366f1' );

        if ( empty( $networks ) && $cl_enabled !== '1' ) return '';

        $post_url   = get_permalink( $post_id ) ?: '';
        $post_title = get_the_title( $post_id ) ?: '';
        $thumbnail  = get_the_post_thumbnail_url( $post_id, 'large' ) ?: '';
        $all_nets   = WPSSL_Networks::all();
        $total      = WPSSL_Share_Count::get_total( $post_id );

        // Wrapper classes
        $classes = [
            'wpssl-wrap',
            'wpssl-shape-' . esc_attr( $btn_shape ),
            'wpssl-size-'  . esc_attr( $btn_size ),
            'wpssl-color-' . esc_attr( $color_scheme ),
        ];
        if ( $hide_mobile === '1' ) $classes[] = 'wpssl-hide-mobile';

        $inline_style = '';
        if ( $color_scheme === 'custom' ) {
            $inline_style = ' style="--wpssl-custom-bg:' . esc_attr( $custom_bg ) . ';--wpssl-custom-text:' . esc_attr( $custom_text ) . ';"';
        }

        $html  = '<div class="' . implode( ' ', $classes ) . '" data-post-id="' . esc_attr( $post_id ) . '"' . $inline_style . '>';

        if ( $show_label === '1' && $label_text ) {
            $html .= '<span class="wpssl-label">' . esc_html( $label_text ) . '</span>';
        }

        if ( $show_total === '1' ) {
            $html .= '<span class="wpssl-total-count"><strong>' . esc_html( $total ) . '</strong> shares</span>';
        }

        $html .= '<div class="wpssl-buttons">';

        // Social network buttons
        foreach ( $networks as $network ) {
            if ( ! isset( $all_nets[ $network ] ) ) continue;

            $nd        = $all_nets[ $network ];
            $share_url = WPSSL_Networks::get_share_url( $network, $post_url, $post_title, $thumbnail );
            $svg       = WPSSL_Networks::get_svg( $network );
            $count     = WPSSL_Share_Count::get( $post_id, $network );
            $target    = $network === 'email' ? '' : ' target="_blank" rel="noopener noreferrer"';
            $is_copy   = $network === 'copy_link'
                         ? ' data-action="copy" data-url="' . esc_url( $post_url ) . '"'
                         : '';

            $btn_style = '';
            if ( $color_scheme === 'brand' ) {
                $btn_style = ' style="--wpssl-brand:' . esc_attr( $nd['color'] ) . ';"';
            }

            $html .= '<a href="' . esc_url( $share_url ) . '"'
                   . ' class="wpssl-btn wpssl-net-' . esc_attr( $network ) . '"'
                   . $target . $is_copy . $btn_style
                   . ' data-platform="' . esc_attr( $network ) . '"'
                   . ' aria-label="Share on ' . esc_attr( $nd['label'] ) . '">';

            $html .= '<span class="wpssl-icon">' . $svg . '</span>';

            if ( $icon_style === 'icon_label' ) {
                $html .= '<span class="wpssl-net-label">' . esc_html( $nd['label'] ) . '</span>';
            }

            if ( $show_count === '1' ) {
                $html .= '<span class="wpssl-count" data-platform="' . esc_attr( $network ) . '">' . esc_html( $count ) . '</span>';
            }

            $html .= '</a>';
        }

        // Custom link button
        if ( $cl_enabled === '1' && $cl_label ) {
            $btn_url = $cl_url ?: $post_url;
            $html .= '<a href="' . esc_url( $btn_url ) . '"'
                   . ' class="wpssl-btn wpssl-btn-custom-link"'
                   . ' style="background:' . esc_attr( $cl_color ) . ';color:#fff;--wpssl-brand:' . esc_attr( $cl_color ) . ';"'
                   . ' target="_blank" rel="noopener noreferrer"'
                   . ' aria-label="' . esc_attr( $cl_label ) . '">';
            $html .= '<span class="wpssl-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6v2H5v11h11v-5h2v7H3V6h7zm11-3v8h-2V6.413l-7.793 7.794-1.414-1.414L17.585 5H13V3h8z"/></svg></span>';
            if ( $icon_style === 'icon_label' ) {
                $html .= '<span class="wpssl-net-label">' . esc_html( $cl_label ) . '</span>';
            }
            $html .= '</a>';
        }

        $html .= '</div>'; // .wpssl-buttons
        $html .= '</div>'; // .wpssl-wrap

        return $html;
    }

    public static function render_floating( $post_id = null ) {
        $opts     = WPSSL_Options::instance();
        $position = $opts->get('position');

        if ( ! in_array( $position, [ 'floating_left', 'floating_right', 'sticky_bottom' ] ) ) {
            return '';
        }

        $map = [
            'floating_left'  => 'left',
            'floating_right' => 'right',
            'sticky_bottom'  => 'sticky-bottom',
        ];
        $pos_class = 'wpssl-floating wpssl-floating-' . $map[ $position ];

        return '<div class="' . esc_attr( $pos_class ) . '">' . self::render( $post_id ) . '</div>';
    }
}
