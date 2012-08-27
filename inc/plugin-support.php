<?php

class Tabify_Edit_Screen_Plugin_Support {
	function __construct() {
		add_action( 'tabify_add_meta_boxes', array( &$this, 'types' ) );
		add_action( 'tabify_add_meta_boxes', array( &$this, 'acf' ) );
	}

	/**
	 * Load widgets created by Types
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.4
	 */
	function types( $posttype ) {
		if( function_exists( 'wpcf_admin_post_page_load_hook' ) ) {
			$_GET['post_type'] = $posttype;
			wpcf_admin_post_page_load_hook();
			unset( $_GET['post_type'] );
		}
	}

	/**
	 * Load widgets created by ACF
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.4
	 */
	function acf() {
		global $acf;

		if ( is_object( $acf ) ) {
			$acfs_objects = $acf->get_field_groups();

			if( $acfs_objects )
			{
				foreach( $acfs_objects as $acf_object )
				{
					$metabox_ids = $acf->get_input_metabox_ids( array(), false );

					// hide / show
					$show = in_array( $acf_object['id'], $metabox_ids ) ? "true" : "false";

					// add meta box
					add_meta_box(
						'acf_' . $acf_object['id'], 
						$acf_object['title'], 
						array( $acf_object, 'meta_box_input' ), 
						$posttype, 
						$acf_object['options']['position'], 
						'high', 
						array( 'fields' => $acf_object['fields'], 'options' => $acf_object['options'], 'show' => $show )
					);
				}
			}
		}
	}
}