/* global jQuery, wp */
(function ($) {
	'use strict';

	if (typeof wp === 'undefined' || !wp.updates || !wp.updates.ajax) {
		return;
	}

	var data = window.spbticAddonsNotice;
	if (!data || !data.slug) {
		return;
	}

	$(function () {
		$(document).on('click', '.spbtic-addons-install', function (event) {
			var $button = $(this);

			if ($button.data('spbticBusy')) {
				event.preventDefault();
				return;
			}

			event.preventDefault();

			$button.data('spbticBusy', true);

			var $spinner = $button.find('.spbtic-addons-spinner');
			var $label = $button.find('.spbtic-addons-label');
			$button.data('spbticOriginalLabel', $.trim($label.text()));
			$spinner.removeClass('hidden');

			var activate = function () {
				$label.text(data.activating);
				$.get(data.activationUrl)
					.done(function () {
						$label.text(data.done);
						window.location.reload();
					})
					.fail(function () {
						restore();
					});
			};

			var restore = function () {
				$spinner.addClass('hidden');
				$label.text($button.data('spbticOriginalLabel'));
				$button.data('spbticBusy', false);
			};

			if (data.pluginStatus === 'installed') {
				activate();
				return;
			}

			$label.text(data.installing);

			wp.updates.ajax('install-plugin', {
				slug: data.slug,
				success: function () {
					activate();
				},
				error: function (response) {
					if (response && response.errorCode === 'plugin_already_installed') {
						activate();
						return;
					}
					restore();
				}
			});
		});
	});
})(jQuery);
