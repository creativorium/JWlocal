/**
 * JW WooCommerce Google Sheet Sync - Admin JS
 */
(function($) {
	'use strict';

	$(function() {
		$('#jw-gsheet-resend-btn').on('click', function() {
			var $btn = $(this);
			var $metabox = $('#jw-gsheet-sync-metabox');
			var $spinner = $('#jw-gsheet-resend-spinner');
			var $message = $('#jw-gsheet-resend-message');
			var orderId = $metabox.data('order-id');

			if (!orderId) {
				return;
			}

			$btn.prop('disabled', true);
			$spinner.show();
			$message.hide().removeClass('notice-success notice-error');

			$.ajax({
				url: jwGsheetSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'jw_gsheet_resend_order',
					nonce: jwGsheetSync.nonce,
					order_id: orderId
				},
				success: function(response) {
					if (response.success) {
						$message.addClass('notice notice-success is-dismissible')
							.html('<p>' + (response.data.message || 'Sent successfully.') + '</p>')
							.show();
						// Update status display
						$metabox.find('.jw-gsheet-status')
							.removeClass('jw-gsheet-pending')
							.addClass('jw-gsheet-success')
							.text('Sent');
						// Update sent_at and response rows
						if (response.data.sent_at) {
							$metabox.find('.jw-gsheet-sent-at').text(response.data.sent_at);
							$metabox.find('.jw-gsheet-sent-at-row').show();
						}
						if (response.data.response) {
							$metabox.find('.jw-gsheet-response').text(response.data.response);
							$metabox.find('.jw-gsheet-response-row').show();
						}
					} else {
						$message.addClass('notice notice-error is-dismissible')
							.html('<p>' + (response.data && response.data.message ? response.data.message : 'An error occurred.') + '</p>')
							.show();
					}
				},
				error: function(xhr, status, err) {
					$message.addClass('notice notice-error is-dismissible')
						.html('<p>Request failed. Please try again.</p>')
						.show();
				},
				complete: function() {
					$btn.prop('disabled', false);
					$spinner.hide();
				}
			});
		});
	});
})(jQuery);
