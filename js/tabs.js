jQuery(function($) {
	//tabify_show_tab( $( '#current_tab' ).val() );
	$('.tabify-tabs').not('.js-disabled').tabify_tabs({});
});


(function($){
	$.fn.extend({ 
		//This is where you write your plugin's name
		tabify_tabs: function() {
			//Iterate over the current set of matched elements
			return this.each(function() {
				var obj = $(this);

				$( ".tabify-tab", obj ).on("click", function( evt ) {
					evt.preventDefault();

					$( ".tabify-tab", obj ).removeClass( 'nav-tab-active' );
					$( this, obj ).addClass( 'nav-tab-active' );

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
		}
	});	
})(jQuery);