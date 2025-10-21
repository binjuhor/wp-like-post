jQuery(document).ready(function ($) {
    /**
     * Handle like button click
     */
    $(document).on('click', '.wplp-like', function (e) {
        e.preventDefault();

        const $button = $(this);
        const postId = $button.data('post-id');

        if ($button.hasClass('wplp-loading') || $button.hasClass('wplp-liked')) {
            return;
        }

        $button.addClass('wplp-loading').prop('disabled', true);

        $.ajax({
            url: WPLP_AJAX.url,
            type: 'POST',
            data: {
                action: 'wplp_send_like',
                nonce: WPLP_AJAX.nonce,
                post_id: postId,
            },
            success: function (response) {
                if (response.success) {
                    $button
                        .addClass('wplp-liked')
                        .removeClass('wplp-loading')
                        .find('.wplp-like-text')
                        .text('Liked');

                    $button.find('.wplp-like-count').text(response.data.likes);

                    showMessage('success', response.data.message);
                } else {
                    $button.removeClass('wplp-loading').prop('disabled', false);

                    showMessage('error', response.data.message);
                }
            },
            error: function (xhr) {
                $button.removeClass('wplp-loading').prop('disabled', false);

                let errorMessage = 'An error occurred. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }

                showMessage('error', errorMessage);
            },
        });
    });

    /**
     * Initialize like buttons on page load
     */
    function initializeLikeButtons() {
        $('.wplp-like').each(function () {
            const $button = $(this);
            const postId = $button.data('post-id');

            if (!postId) {
                return;
            }

            $.ajax({
                url: WPLP_AJAX.url,
                type: 'POST',
                data: {
                    action: 'wplp_get_like_status',
                    nonce: WPLP_AJAX.nonce,
                    post_id: postId,
                },
                success: function (response) {
                    if (response.success) {
                        $button.find('.wplp-like-count').text(response.data.likes);

                        if (response.data.has_liked) {
                            $button
                                .addClass('wplp-liked')
                                .prop('disabled', true)
                                .find('.wplp-like-text')
                                .text('Liked');
                        }
                    }
                },
            });
        });
    }

    /**
     * Show notification message
     */
    function showMessage(type, message) {
        const $notification = $('<div>', {
            class: `wplp-notification wplp-notification-${type}`,
            text: message,
        });

        $('body').append($notification);

        setTimeout(function () {
            $notification.addClass('wplp-notification-show');
        }, 10);

        setTimeout(function () {
            $notification.removeClass('wplp-notification-show');
            setTimeout(function () {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    initializeLikeButtons();
});
