jQuery(function($) {

	// Select permissions of a tab
	$( document ).on( 'click', '.tabify-tab-permissions', function( evt ) {
		var parent = $( this ).closest('.tabify_tab');

		parent.find('.tabify-tab-permission-box').stop().slideToggle(400);
	});

	// Delete a tab when it is empty
	$( document ).on( 'click', '.tabify-remove-tab', function( evt ) {
		evt.preventDefault();

		if( $(this).css('opacity') != 1 ) {
			return;
		}

		var parent = $( this ).closest('.tabify_tab');
		var sender_children = parent.find('.ui-sortable').children().length;

		if( 0 !== sender_children ) {
			$(this).parent().find('.tabify-tab-permissions').hide();
		}
	});

	$( document ).on( 'tabify-declined-remove-tab', function( evt ) {
		$(evt.target).find('.tabify-tab-permissions').fadeIn();
	});
	

});