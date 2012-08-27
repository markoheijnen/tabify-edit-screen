jQuery(function($) {
	// Remove nojs support
	$( ".tabify_control .item-order" ).remove();
	$( "#tabify_edit_screen_nojs" ).remove();

	$( ".tabify_control" ).sortable({
		scroll : false
	});

	// Initialize sortables
	initialize_sortable_ul();
	function initialize_sortable_ul() {
		$( ".tabify_control ul" ).sortable({
			//items : ".steps",
			connectWith: ".tabify_control ul",
			scroll : false,
			disableSelection: true,
			receive: function(event, ui) {
				var item = $( ui.item );
				var parts = $( 'input', ui.item ).attr('name').split( '][' );
				parts[2] = item.closest( 'div' ).index();
				$( 'input', ui.item ).attr( 'name', parts.join( '][' ) );

				item.closest( 'div' ).find( '.tabify-remove-tab ' ).hide();

				var sender_children = $( ui.sender ).children().length;
				if( sender_children == 0 ) {
					ui.sender.closest( 'div' ).find( '.tabify-remove-tab ' ).show();
				}
			}
		});
	}

	$( "#create_tab" ).on("click", function() {
		var title = tabify_l10.choose_title;
		var posttype = $( '.nav-tab-active' ).attr( 'id' );
		posttype = posttype.replace( 'tab-', "");

		var counter = $( '.tabifybox-' + posttype + ' .tabify_control' ).children().length;

		var html = '<div class="menu-item-handle tabify_tab">';
		html += '<h2><span>' + title + '</span><input type="text" name="tabify[' + posttype + '][tabs][' + counter + '][title]" value="' + title + '" style="display: none;" /></h2>';
		html += '<a href="#" class="tabify-remove-tab">' + tabify_l10.remove + '</a><div class="clear"></div>';
		html += '<ul></ul></div>';

		$( '.tabifybox-' + posttype + ' .tabify_control' ).append( html );

		$( '.tabifybox-' + posttype + ' .tabify_control' ).sortable( "refresh" );
		initialize_sortable_ul();
	});

	// Make the h2 changeable by a click
	$( document ).on( "click", ".tabifybox h2", function() {
		$( 'span', this ).hide();
		$( 'input', this ).show();
		$( 'input', this ).focus();
	});

	$( document ).on( "focusout", ".tabifybox h2 input", function() {
		$( this ).hide();
		$( this ).closest( 'h2' ).find('span').html( $( this ).val() );
		$( this ).closest( 'h2' ).find('span').show();
	});

	// Delete a tab when it is empty
	$( document ).on( "click", ".tabify-remove-tab", function( evt ) {
		evt.preventDefault();
		$( this ).closest( 'div' ).remove();
	});
});