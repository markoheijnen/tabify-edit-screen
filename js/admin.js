/** global: wp, tabify_l10 */
jQuery(function($) {
	// Remove nojs support
	$( ".tabify_control_tabs .item-order" ).remove();
	$( "#tabify_edit_screen_nojs" ).remove();

	$( ".tabify_control" ).sortable({
		scroll : false,
		update: function() {
			tabify_admin_fix_sortable();
		}
	});

	// Initialize sortables
	initialize_sortable_ul();
	function initialize_sortable_ul() {
		$( ".tabify_control_tabs ul" ).sortable({
			//items : ".steps",
			connectWith: ".tabify_control_tabs ul",
			scroll : false,
			disableSelection: true,
			receive: function(event, ui) {
				var item   = $( ui.item );
				var holder = item.closest( 'div' );
				var index  = holder.index();

				if ( holder.data('id') ) {
					index = holder.data('id');
				}

				var parts = $( 'input', ui.item ).attr('name').split( '][' );
				parts[3]  = index;
				$( 'input', ui.item ).attr( 'name', parts.join( '][' ) );
			}
		});
	}

	$( "#create_tab" ).on("click", function(evt) {
		evt.preventDefault();

		$('.tabifybox:visible .tabify-remove-tab').fadeIn();

		var posttype = $( '#tabify-submenu .nav-tab-active' ).attr( 'id' );
		posttype = posttype.replace( 'tab-', "");

		var counter = $( '.tabifybox-' + posttype + ' .tabify_control' ).children().length;

		var template = wp.template('new-tab');
		var options = {
			tab_id: counter,
			section: posttype,
		}

		$( '.tabifybox-' + posttype + ' .tabify_control' ).append( template( options ) );

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

		if( $(this).css('opacity') != 1 ) {
			return;
		}

		var parent = $( this ).closest('.tabify_tab');
		var sender_children = parent.find('.ui-sortable').children().length;

		if( 0 == sender_children ) {
			parent.hide( function(){
				$(this).remove();
				tabify_admin_hide_delete();
			});
		}
		else {
			$(this).hide();

			var html = '<div class="tabify-remove-accept">' + tabify_l10.move_meta_boxes + ' <select>';

			$('.tabifybox:visible .tabify_tab').each(function(index) {
				if( ! $(this).is(parent) ) {
					html += '<option value="' + index + '">' + $(this).find('h2').text() + '</option>';
				}
			});

			html += '</select>';
			html += ' <input type="button" class="button" value="' + tabify_l10.remove + '" />';
			html += ' &nbsp; <a href="">' + tabify_l10.cancel + '</a>';
			html += '</div>';

			$(html).insertAfter(this).show('blind');
		}
	});

	$( document ).on( "click", ".tabify-remove-accept input", function() {
		var parent = $( this ).closest('.tabify_tab');
		var tab_holder = $( this ).closest('.tabify_control').children().eq( parent.find('select option:selected').val() ).find('.ui-sortable');

		parent.hide( function(){
			parent.find('.ui-sortable').children().each(function() {
				$(this).hide();
				tab_holder.append(this);
				$(this).show('blind');
			});

			$(this).remove();
			tabify_admin_fix_sortable();
			tabify_admin_hide_delete();
		});
	});

	$( document ).on( "click", ".tabify-remove-accept a", function( evt ) {
		evt.preventDefault();

		var parent = $(this).closest('.tabify_tab');

		parent.find('.tabify-remove-accept').hide('blind', function() {
			$(this).remove();
			parent.find('.tabify-remove-tab').fadeIn();

			$( parent ).trigger( 'tabify-declined-remove-tab' );
		});
	});

	function tabify_admin_fix_sortable() {
		$('.tabifybox:visible .tabify_tab').each(function() {
			var parent_index = $(this).index();

			var parts = $( 'h2 input', this ).attr('name').split( '][' );
			parts[3]  = parent_index;
			$( 'h2 input', this ).attr( 'name', parts.join( '][' ) );

			$(this).find('.ui-sortable').children().each(function() {
				var parts = $( 'input', this ).attr('name').split( '][' );
				parts[3]  = parent_index;
				$( 'input', this ).attr( 'name', parts.join( '][' ) );
			});
		});
	}

	function tabify_admin_hide_delete() {
		var amount = $('.tabifybox:visible .tabify_control').children().length;

		if( 1 >= amount) {
			$('.tabifybox:visible .tabify-remove-tab').fadeOut();
		}
	}
});