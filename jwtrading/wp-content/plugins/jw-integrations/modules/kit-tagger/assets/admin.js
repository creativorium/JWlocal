/**
 * JW Kit Auto Tagger - Admin scripts
 */
(function($) {
	'use strict';

	$(function() {
		// Test connection
		$('#jw-kit-test-connection').on('click', function() {
			var $btn = $(this);
			var $result = $('#jw-kit-test-result');
			$btn.prop('disabled', true);
			$result.removeClass('jw-kit-success jw-kit-error').text(jwKitAdmin.i18n.testing);

			$.post(jwKitAdmin.ajaxUrl, {
				action: 'jw_kit_test_connection',
				nonce: jwKitAdmin.nonce
			})
			.done(function(response) {
				if (response.success) {
					$result.addClass('jw-kit-success').text(jwKitAdmin.i18n.success);
				} else {
					$result.addClass('jw-kit-error').text(jwKitAdmin.i18n.error + (response.data && response.data.message ? response.data.message : 'Unknown error'));
				}
			})
			.fail(function(xhr) {
				$result.addClass('jw-kit-error').text(jwKitAdmin.i18n.error + (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Request failed'));
			})
			.always(function() {
				$btn.prop('disabled', false);
			});
		});

		// Re-sync order
		$('#jw-kit-resync-order').on('click', function() {
			var $btn = $(this);
			var $result = $('#jw-kit-resync-result');
			var orderId = $('#jw_kit_resync_order_id').val();
			if (!orderId) {
				$result.removeClass('jw-kit-success jw-kit-error').addClass('jw-kit-error').text(jwKitAdmin.i18n.orderId);
				return;
			}
			$btn.prop('disabled', true);
			$result.removeClass('jw-kit-success jw-kit-error').text(jwKitAdmin.i18n.resync);

			$.post(jwKitAdmin.ajaxUrl, {
				action: 'jw_kit_resync_order',
				nonce: jwKitAdmin.nonce,
				order_id: orderId
			})
			.done(function(response) {
				if (response.success) {
					$result.addClass('jw-kit-success').text(jwKitAdmin.i18n.resyncOk);
				} else {
					$result.addClass('jw-kit-error').text(jwKitAdmin.i18n.resyncErr + (response.data && response.data.message ? response.data.message : 'Unknown error'));
				}
			})
			.fail(function(xhr) {
				$result.addClass('jw-kit-error').text(jwKitAdmin.i18n.resyncErr + (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Request failed'));
			})
			.always(function() {
				$btn.prop('disabled', false);
			});
		});

		// Pull the tag catalogue from Kit. Read-only: it lists tags, it never
		// creates or edits anything in the account.
		$('#jw-kit-sync-tags').on('click', function() {
			var $btn = $(this);
			var $result = $('#jw-kit-sync-tags-result');

			$btn.prop('disabled', true);
			$result.removeClass('jw-kit-success jw-kit-error').text('Syncing...');

			$.post(jwKitAdmin.ajaxUrl, {
				action: 'jw_kit_sync_tags',
				nonce: jwKitAdmin.nonce
			})
			.done(function(response) {
				if (response.success) {
					$result.addClass('jw-kit-success').text(response.data.message);
					// Reload so the cached count and every tag dropdown refresh.
					setTimeout(function() { window.location.reload(); }, 900);
				} else {
					$result.addClass('jw-kit-error').text((response.data && response.data.message) ? response.data.message : 'Sync failed');
				}
			})
			.fail(function(xhr) {
				$result.addClass('jw-kit-error').text((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Request failed');
			})
			.always(function() {
				$btn.prop('disabled', false);
			});
		});
	});
})(jQuery);
