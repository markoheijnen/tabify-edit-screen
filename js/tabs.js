jQuery(function($) {
	tabify_show_tab( $( '#current_tab' ).val() );

	$( ".tabify-tab" ).on("click", function( evt ) {
		evt.preventDefault();
		$( ".tabify-tab" ).removeClass( 'nav-tab-active' );
		$( this ).addClass( 'nav-tab-active' );

		var id = evt.target.id.replace( 'tab-', "");
		tabify_show_tab( id );
	});

	function tabify_show_tab( id ) {
		if( id && id.length != 0 ) {
			$( ".tabifybox" ).hide();
			$( ".tabifybox-" + id ).show();
			$( "#current_tab" ).val( id );
		}
	}
});