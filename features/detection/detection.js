jQuery(function($) {

	tabify_detection_get( $('.current_tab').val() );

	$( ".tabify-tab", '.tab-vertical' ).on("click", function( evt ) {
		evt.preventDefault();

		var id = evt.target.id.replace( 'tab-', "");
		
		tabify_detection_get(id);
	});

	function tabify_detection_get( posttype ) {
		$.getJSON( tabify_detection[ posttype ], function( data ) {
			$.each( data, function( key, metabox ) {
				if ( ! document.getElementById( posttype + '-' + key ) ) {
					var tab     = $('.tabify_tab', '.tabifybox-' + posttype).last();
					var counter = $( '.tabifybox-' + posttype + ' .tabify_control' ).children().length - 1;

					$('ul', tab).append('<li id="' + posttype + '-' + key + '"><div class="menu-item-bar"><div class="menu-item-handle"><span class="item-title">' + metabox.title + '</span><input type="hidden" name="tabify[posttypes][' + posttype + '][tabs][' + counter + '][items][]" value="' + key + '"></div></div></li>');
				}
			});

		});
	}

});