/* WP Social Share Lite - Admin JS v2.0.0 */
(function($) {
    'use strict';

    $(function() {
        // Color pickers
        $('.wpssl-color-picker').wpColorPicker({
            change: function() { triggerPreview(); }
        });

        // Custom colors toggle
        $('input[name="color_scheme"]').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#wpssl-custom-colors').slideDown();
            } else {
                $('#wpssl-custom-colors').slideUp();
            }
        });

        // Sortable networks
        if ($.fn.sortable) {
            $('#wpssl-networks-list').sortable({
                handle: '.wpssl-drag-handle',
                placeholder: 'wpssl-network-item wpssl-sortable-placeholder',
                update: function() { triggerPreview(); }
            });
        }

        // Preview button
        $('#wpssl-preview-btn').on('click', function() {
            triggerPreview(true);
        });

        // Auto-preview on change
        $('#wpssl-settings-form input, #wpssl-settings-form select').on('change', function() {
            clearTimeout(window.wpssl_preview_timer);
            window.wpssl_preview_timer = setTimeout(triggerPreview, 600);
        });

        function triggerPreview(immediate) {
            var $btn = $('#wpssl-preview-btn');
            var $out = $('#wpssl-preview-output');

            $btn.text('Loading...').prop('disabled', true);
            $out.css('opacity', '0.5');

            var opts = gatherOpts();

            $.post(WPSSLAdmin.ajax_url, {
                action: 'wpssl_preview',
                nonce:  WPSSLAdmin.nonce,
                opts:   opts
            }, function(res) {
                if (res.success) {
                    $out.html(res.data.html);
                    // Inject public CSS into preview
                    injectPreviewStyles();
                }
            }).always(function() {
                $btn.text('Refresh Preview').prop('disabled', false);
                $out.css('opacity', '1');
            });
        }

        function gatherOpts() {
            var opts = {};

            // Networks (ordered from list)
            var nets = [];
            $('#wpssl-networks-list .wpssl-network-item').each(function() {
                var $cb = $(this).find('input[type=checkbox]');
                if ($cb.is(':checked')) {
                    nets.push($(this).data('key'));
                }
            });
            opts.networks = nets;

            opts.icon_style       = $('input[name="icon_style"]:checked').val();
            opts.show_share_count = $('input[name="show_share_count"]').is(':checked') ? '1' : '0';
            opts.show_total_count = $('input[name="show_total_count"]').is(':checked') ? '1' : '0';
            opts.show_label       = $('input[name="show_label"]').is(':checked') ? '1' : '0';
            opts.label_text       = $('input[name="label_text"]').val();
            opts.button_shape     = $('input[name="button_shape"]:checked').val();
            opts.button_size      = $('input[name="button_size"]:checked').val();
            opts.color_scheme     = $('input[name="color_scheme"]:checked').val();
            opts.custom_bg        = $('input[name="custom_bg"]').val();
            opts.custom_text      = $('input[name="custom_text"]').val();

            return opts;
        }

        function injectPreviewStyles() {
            if ($('#wpssl-preview-inline-styles').length) return;
            var link = $('<link>', {
                id:   'wpssl-preview-inline-styles',
                rel:  'stylesheet',
                href: WPSSLAdmin.plugin_url + 'public/css/wpssl-public.css'
            });
            $('head').append(link);
        }
    });

})(jQuery);
