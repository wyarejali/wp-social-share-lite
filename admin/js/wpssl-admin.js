/* WP Social Share Lite — Admin JS v2.0.1 */
(function($) {
    'use strict';

    $(function() {

        // ── Color Pickers ──────────────────────────────────
        $('.wpssl-color-picker').wpColorPicker({
            change: function() {
                clearTimeout(window.wpssl_preview_timer);
                window.wpssl_preview_timer = setTimeout(function(){ doPreview(); }, 600);
            }
        });

        // ── Custom colors toggle ───────────────────────────
        $('input[name="color_scheme"]').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#wpssl-custom-colors').slideDown(200);
            } else {
                $('#wpssl-custom-colors').slideUp(200);
            }
            clearTimeout(window.wpssl_preview_timer);
            window.wpssl_preview_timer = setTimeout(function(){ doPreview(); }, 400);
        });

        // ── Sortable Networks ──────────────────────────────
        if ($.fn.sortable && $('#wpssl-networks-list').length) {
            $('#wpssl-networks-list').sortable({
                handle:      '.wpssl-drag-handle',
                axis:        'y',
                placeholder: 'wpssl-network-item wpssl-sortable-placeholder',
                forcePlaceholderSize: true,
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.outerHeight());
                },
                update: function() {
                    clearTimeout(window.wpssl_preview_timer);
                    window.wpssl_preview_timer = setTimeout(function(){ doPreview(); }, 400);
                }
            }).disableSelection();
        }

        // ── Preview button ─────────────────────────────────
        $('#wpssl-preview-btn').on('click', function() {
            doPreview();
        });

        // ── Auto-preview on any form change ───────────────
        $('#wpssl-settings-form').on('change', 'input, select, textarea', function() {
            clearTimeout(window.wpssl_preview_timer);
            window.wpssl_preview_timer = setTimeout(function(){ doPreview(); }, 700);
        });

        // ── Trigger initial preview on load ───────────────
        setTimeout(function(){ doPreview(); }, 500);

        // ── Core preview function ──────────────────────────
        function doPreview() {
            var $btn = $('#wpssl-preview-btn');
            var $out = $('#wpssl-preview-output');

            $btn.addClass('updating-message');
            $out.css({ opacity: 0.5, transition: 'opacity 0.2s' });

            $.post(WPSSLAdmin.ajax_url, {
                action: 'wpssl_preview',
                nonce:  WPSSLAdmin.nonce,
                opts:   gatherOpts()
            }, function(res) {
                if (res.success && res.data.html) {
                    $out.html(res.data.html);
                } else {
                    $out.html('<p style="color:#94a3b8;text-align:center;font-size:12px;padding:20px 0">Could not load preview.</p>');
                }
            }).fail(function() {
                $out.html('<p style="color:#ef4444;text-align:center;font-size:12px;padding:20px 0">Preview request failed.</p>');
            }).always(function() {
                $btn.removeClass('updating-message');
                $out.css('opacity', 1);
            });
        }

        // ── Gather current form values ─────────────────────
        function gatherOpts() {
            var opts = {};

            // Networks in current sorted order, only checked ones
            var nets = [];
            $('#wpssl-networks-list .wpssl-network-item').each(function() {
                var $cb = $(this).find('.wpssl-net-checkbox');
                if ($cb.is(':checked')) {
                    nets.push($(this).data('key'));
                }
            });
            opts.networks = nets;

            opts.icon_style          = $('input[name="icon_style"]:checked').val() || 'icon_label';
            opts.show_share_count    = $('input[name="show_share_count"]').is(':checked') ? '1' : '0';
            opts.show_total_count    = $('input[name="show_total_count"]').is(':checked') ? '1' : '0';
            opts.show_label          = $('input[name="show_label"]').is(':checked') ? '1' : '0';
            opts.label_text          = $('input[name="label_text"]').val() || 'Share this:';
            opts.button_shape        = $('input[name="button_shape"]:checked').val() || 'rounded';
            opts.button_size         = $('input[name="button_size"]:checked').val() || 'medium';
            opts.color_scheme        = $('input[name="color_scheme"]:checked').val() || 'brand';
            opts.custom_bg           = $('input[name="custom_bg"]').val() || '#333333';
            opts.custom_text         = $('input[name="custom_text"]').val() || '#ffffff';
            opts.custom_link_enabled = $('input[name="custom_link_enabled"]').is(':checked') ? '1' : '0';
            opts.custom_link_label   = $('input[name="custom_link_label"]').val() || 'Visit';
            opts.custom_link_url     = $('input[name="custom_link_url"]').val() || '';
            opts.custom_link_color   = $('input[name="custom_link_color"]').val() || '#6366f1';

            return opts;
        }

    });

})(jQuery);
