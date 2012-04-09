<?php

class Tabify_Edit_Screen_Plugin_Support {
	function __construct() {
		add_action( 'tabify_add_meta_boxes', array( &$this, 'load_metaboxes' ) );
	}

	function load_metaboxes( $posttype ) {
		//Support for types
		if( function_exists( 'wpcf_admin_post_page_load_hook' ) ) {
			$_GET['post_type'] = $posttype;
			wpcf_admin_post_page_load_hook();
			unset( $_GET['post_type'] );
		}
	}
}