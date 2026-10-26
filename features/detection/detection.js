/** global: tabify_detection */
jQuery(function($) {
	var pending = {};
	var messages = {};
	var loaded = {};

	function current_posttype() {
		return $('#tabify-submenu .current_tab').val();
	}

	function show_status() {
		var posttype = current_posttype();
		$('.tabify-detection-status').text(messages[posttype] || '');
		$('.tabify-refresh-metaboxes').prop('disabled', !!pending[posttype]);
	}

	function detect(posttype, force) {
		if (!posttype || pending[posttype]) {
			return;
		}
		if (!tabify_detection.posttype_links[posttype]) {
			messages[posttype] = tabify_detection.unavailable;
			show_status();
			return;
		}
		if (!force && (loaded[posttype] || !tabify_detection.needs_detection[posttype])) {
			show_status();
			return;
		}

		pending[posttype] = true;
		messages[posttype] = tabify_detection.loading;
		show_status();

		$.ajax({
			url: tabify_detection.posttype_links[posttype],
			dataType: 'json',
			timeout: 15000
		}).done(function(data) {
			if ((!$.isPlainObject(data) && !(Array.isArray(data) && data.length === 0)) || data.success === false) {
				messages[posttype] = tabify_detection.failed;
				return;
			}

			var section = $('.tabifybox-' + posttype);
			var tab = section.find('.tabify_tab').last();
			var index = tab.index();
			var list = tab.children('ul');
			var count = 0;

			$.each(data, function(id, metabox) {
				if (!metabox || typeof metabox.title !== 'string') {
					return;
				}
				count++;
				if (!document.getElementById(posttype + '-' + id)) {
					var item = $('<li>').attr('id', posttype + '-' + id);
					var handle = $('<div>').addClass('menu-item-bar').appendTo(item);
					handle = $('<div>').addClass('menu-item-handle').appendTo(handle);
					$('<span>').addClass('item-title').text(metabox.title).appendTo(handle);
					$('<input>', {
						type: 'hidden',
						name: 'tabify[posttypes][' + posttype + '][tabs][' + index + '][items][]',
						value: id
					}).appendTo(handle);
					list.append(item);
				}
			});

			$(document).trigger('tabify:metaboxes-updated');
			loaded[posttype] = true;
			tabify_detection.needs_detection[posttype] = false;
			messages[posttype] = count ? tabify_detection.complete : tabify_detection.empty;
		}).fail(function() {
			messages[posttype] = tabify_detection.failed;
		}).always(function() {
			delete pending[posttype];
			show_status();
		});
	}

	$('#tabify-submenu').on('click', '.tabify-tab', function() {
		var posttype = this.id.replace('tab-', '');
		detect(posttype, false);
		// The tabs handler updates the hidden field during the same click event.
		show_status();
	});

	$('.tabify-refresh-metaboxes').on('click', function() {
		detect(current_posttype(), true);
	});

	detect(current_posttype(), false);
});
