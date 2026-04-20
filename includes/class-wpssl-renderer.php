<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSSL_Renderer {

    public static function render( $post_id = null, $preview_options = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $opts = $preview_options ? $preview_options : WPSSL_Options::instance();

        $networks    = is_array( $opts ) ? ( $opts['networks'] ?? [] ) : $opts->get('networks');
        $icon_style  = is_array( $opts ) ? ( $opts['icon_style'] ?? 'icon_label' ) : $opts->get('icon_style');
        $show_count  = is_array( $opts ) ? ( $opts['show_share_count'] ?? '1' ) : $opts->get('show_share_count');
        $show_total  = is_array( $opts ) ? ( $opts['show_total_count'] ?? '1' ) : $opts->get('show_total_count');
        $show_label  = is_array( $opts ) ? ( $opts['show_label'] ?? '1' ) : $opts->get('show_label');
        $label_text  = is_array( $opts ) ? ( $opts['label_text'] ?? 'Share this:' ) : $opts->get('label_text');
        $btn_shape   = is_array( $opts ) ? ( $opts['button_shape'] ?? 'rounded' ) : $opts->get('button_shape');
        $btn_size    = is_array( $opts ) ? ( $opts['button_size'] ?? 'medium' ) : $opts->get('button_size');
        $color_scheme= is_array( $opts ) ? ( $opts['color_scheme'] ?? 'brand' ) : $opts->get('color_scheme');
        $custom_bg   = is_array( $opts ) ? ( $opts['custom_bg'] ?? '#333333' ) : $opts->get('custom_bg');
        $custom_text = is_array( $opts ) ? ( $opts['custom_text'] ?? '#ffffff' ) : $opts->get('custom_text');
        $hide_mobile = is_array( $opts ) ? ( $opts['hide_on_mobile'] ?? '0' ) : $opts->get('hide_on_mobile');

        if ( empty( $networks ) ) return '';

        $post      = get_post( $post_id );
        $post_url  = get_permalink( $post_id );
        $post_title= $post ? $post->post_title : '';
        $thumbnail = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
        $all_nets  = WPSSL_Networks::all();
        $total     = WPSSL_Share_Count::get_total( $post_id );

        $wrapper_classes = [
            'wpssl-wrap',
            'wpssl-shape-' . esc_attr( $btn_shape ),
            'wpssl-size-' . esc_attr( $btn_size ),
            'wpssl-color-' . esc_attr( $color_scheme ),
        ];
        if ( $hide_mobile === '1' ) $wrapper_classes[] = 'wpssl-hide-mobile';

        $custom_style = '';
        if ( $color_scheme === 'custom' ) {
            $custom_style = ' style="--wpssl-custom-bg:' . esc_attr( $custom_bg ) . ';--wpssl-custom-text:' . esc_attr( $custom_text ) . ';"';
        }

        $html  = '<div class="' . implode( ' ', $wrapper_classes ) . '" data-post-id="' . esc_attr( $post_id ) . '"' . $custom_style . '>';

        if ( $show_label === '1' ) {
            $html .= '<span class="wpssl-label">' . esc_html( $label_text ) . '</span>';
        }

        if ( $show_total === '1' ) {
            $html .= '<span class="wpssl-total-count"><strong>' . esc_html( $total ) . '</strong> shares</span>';
        }

        $html .= '<div class="wpssl-buttons">';

        foreach ( $networks as $network ) {
            if ( ! isset( $all_nets[ $network ] ) ) continue;

            $net_data  = $all_nets[ $network ];
            $share_url = WPSSL_Networks::get_share_url( $network, $post_url, $post_title, $thumbnail );
            $svg       = WPSSL_Networks::get_svg( $network );
            $count     = WPSSL_Share_Count::get( $post_id, $network );
            $target    = $network === 'email' ? '' : ' target="_blank" rel="noopener noreferrer"';
            $is_copy   = $network === 'copy_link' ? ' data-action="copy" data-url="' . esc_url( $post_url ) . '"' : '';
            $aria      = 'Share on ' . $net_data['label'];

            $btn_style = '';
            if ( $color_scheme === 'brand' ) {
                $btn_style = ' style="--wpssl-brand:' . esc_attr( $net_data['color'] ) . ';"';
            }

            $html .= '<a href="' . esc_url( $share_url ) . '" class="wpssl-btn wpssl-net-' . esc_attr( $network ) . '"'
                   . $target . $is_copy . $btn_style
                   . ' data-platform="' . esc_attr( $network ) . '"'
                   . ' aria-label="' . esc_attr( $aria ) . '">';

            $html .= '<span class="wpssl-icon">' . $svg . '</span>';

            if ( $icon_style === 'icon_label' ) {
                $html .= '<span class="wpssl-net-label">' . esc_html( $net_data['label'] ) . '</span>';
            }

            if ( $show_count === '1' ) {
                $html .= '<span class="wpssl-count" data-platform="' . esc_attr( $network ) . '">' . esc_html( $count ) . '</span>';
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

        $pos_class = 'wpssl-floating wpssl-floating-' . str_replace( 'floating_', '', str_replace( 'sticky_', 'sticky-', $position ) );
        $inner     = self::render( $post_id );

        return '<div class="' . esc_attr( $pos_class ) . '">' . $inner . '</div>';
    }
}
