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

					var id = evt.target.id.replace( 'tab-', "");
					tabify_show_tab( id, $( this ).closest('.tabify-tabs') );
				});

				function tabify_show_tab( id, holder ) {
					if( id && id.length != 0 ) {
						$( ".tabifybox" ).hide();
						$( ".current_tab", holder ).val( id );

						$( ".tabifybox-" + id ).each( function( index ) {
							var checkbox = $( '#' + $(this).attr('id') + '-hide' );

							if( checkbox.attr('type') != 'checkbox' || checkbox.is(':checked') ) {
								$(this).show();
							}
						}).promise().done( function(){ tabify_fix_editors() } );
					}
				}

				function tabify_fix_editors() {
					var editors = $('.wp-editor-tools');
					editors.each(function( index ) {
						editor = $( this );

						if ( editor.closest('.tabifybox').is(':visible') ) {
							if( ! editor.width() ) {
								$(document).trigger('postbox-toggled');

								return false;
							}
						}
					});
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