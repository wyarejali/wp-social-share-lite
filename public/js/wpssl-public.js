/* WP Social Share Lite - Public JS v2.0.0 */
(function($) {
    'use strict';

    $(function() {

        // Track share clicks & copy link
        $(document).on('click', '.wpssl-btn', function(e) {
            var $btn     = $(this);
            var platform = $btn.data('platform');
            var action   = $btn.data('action');
            var postId   = $btn.closest('.wpssl-wrap').data('post-id') || WPSSLData.post_id;

            if (!platform || !postId) return;

            // Copy link
            if (action === 'copy') {
                e.preventDefault();
                var url = $btn.data('url') || window.location.href;
                copyToClipboard(url);
                return;
            }

            // Track share
            $.post(WPSSLData.ajax_url, {
                action:   'wpssl_share',
                nonce:    WPSSLData.nonce,
                platform: platform,
                post_id:  postId
            }, function(res) {
                if (res.success) {
                    // Update per-button count
                    $btn.find('.wpssl-count').text(res.data.count);
                    // Update total count
                    $('.wpssl-wrap[data-post-id="' + postId + '"] .wpssl-total-count strong').text(res.data.total);
                }
            });
        });

        // Copy to clipboard
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast(WPSSLData.i18n.copied);
                }).catch(function() {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        }

        function fallbackCopy(text) {
            var $tmp = $('<textarea>').val(text).appendTo('body').select();
            try {
                document.execCommand('copy');
                showToast(WPSSLData.i18n.copied);
            } catch(e) {
                showToast(WPSSLData.i18n.copy_error);
            }
            $tmp.remove();
        }

        function showToast(msg) {
            var $toast = $('<div class="wpssl-copy-toast">').text(msg).appendTo('body');
            setTimeout(function() { $toast.addClass('wpssl-toast-show'); }, 10);
            setTimeout(function() {
                $toast.removeClass('wpssl-toast-show');
                setTimeout(function() { $toast.remove(); }, 300);
            }, 2500);
        }

    });

})(jQuery);
