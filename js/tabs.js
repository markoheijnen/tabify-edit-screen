/** global: ajaxurl, postboxes */
jQuery(function($) {
	//tabify_show_tab( $( '#current_tab' ).val() );
	$('.tabify-tabs').not('.js-disabled').tabify_tabs({});
});


(function($){
	$.fn.extend({ 
		tabify_tabs: function() {
			//Iterate over the current set of matched elements
			return this.each(function() {
				var obj = $(this);

				$( ".tabify-tab", obj ).on("click", function( evt ) {
					evt.preventDefault();

					$( ".tabify-tab", obj ).removeClass( 'nav-tab-active' );
					$( this, obj ).addClass( 'nav-tab-active' );

					var id = this.id.replace( 'tab-', "");
					tabify_show_tab( id, $( this ).closest('.tabify-tabs') );
				});

				function tabify_show_tab( id, holder ) {
					if( id && id.length != 0 ) {
						$( ".tabifybox" ).hide();
						$( ".current_tab", holder ).val( id );

						$( ".tabifybox-" + id ).each( function() {
							var checkbox = $( '#' + $(this).attr('id') + '-hide' );

							if( checkbox.attr('type') != 'checkbox' || checkbox.is(':checked') ) {
								$(this).show();
							}
						}).promise().done( function(){
							tabify_fix_editors();
							tabify_refresh_editor_layout();
						} );
					}
				}

				/**
				 * Ask WordPress to recalculate the editor layout once the meta boxes are visible.
				 *
				 * Core measures the editor toolbars while the meta box is still hidden, so
				 * .wp-editor-wrap keeps a padding-top computed from a hidden element (197px instead
				 * of 65px). The result is an empty band between the media buttons and the editor
				 * toolbars. Core only recomputes that padding on a resize event
				 * (wp-admin/js/editor-expand.js), which a tab switch never triggers.
				 */
				function tabify_refresh_editor_layout() {
					window.setTimeout( function() {
						$( window ).trigger( 'resize' );
					}, 60 );
				}

				function tabify_fix_editors() {
					var editors = $('.wp-editor-tools');
					editors.each(function() {
						var editor = $( this );

						if ( editor.closest('.tabifybox').is(':visible') ) {
							if( ! editor.width() ) {
								$(document).trigger('postbox-toggled');

								return false;
							}
						}
					});

					return true;
				}
			});
		}
	});

	if( 'undefined' !== typeof postboxes ) {
		postboxes.save_state = function( page ) {
			var closed = $('.postbox').filter('.closed').map(function() { return this.id; }).get().join(','),
				hidden = $('.hide-postbox-tog').not(':checked').map(function() { return this.value; }).get().join(',');

			$.post(ajaxurl, {
				action: 'closed-postboxes',
				closed: closed,
				hidden: hidden,
				closedpostboxesnonce: jQuery('#closedpostboxesnonce').val(),
				page: page
			});
		}
	}
})(jQuery);
