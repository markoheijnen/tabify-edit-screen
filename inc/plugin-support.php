<?php

class Tabify_Edit_Screen_Plugin_Support {

	public function __construct() {
		add_action( 'tabify_add_meta_boxes', array( $this, 'types' ) );
		add_action( 'tabify_add_meta_boxes', array( $this, 'members' ) );
		add_action( 'tabify_add_meta_boxes', array( $this, 'wpml' ) );

		add_filter( 'wpseo_always_register_metaboxes_on_admin', '__return_true' );
		add_action( 'tabify_add_meta_boxes', array( $this, 'wpseo' ) ); // The old way, will be removed in 1.0
	}

	/**
	 * Load widgets created by Types
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.4.0
	 */
	public function types( $posttype ) {
		if ( function_exists( 'wpcf_admin_post_page_load_hook' ) ) {
			$_GET['post_type'] = $posttype;
			wpcf_admin_post_page_load_hook();
			unset( $_GET['post_type'] );
		}
	}

	/**
	 * Load widgets created by WordPress SEO
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.4.0
	 */
	public function wpseo( $posttype ) {
		if ( defined( 'WPSEO_PATH' ) && is_file( WPSEO_PATH . 'admin/class-metabox.php' ) ) {
			include_once WPSEO_PATH . 'admin/class-metabox.php';
		}
	}
	

	/**
	 * Load widgets created by Members
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.4.0
	 */
	public function members( $posttype ) {
		if ( function_exists( 'members_admin_setup' ) && ! did_action( 'load-post.php' ) ) {
			do_action( 'load-post.php' );
		}
	}
	
	/**
	 * Load widgets created by Members
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from
	 * 
	 * @since 0.7.0
	 */
	public function wpml( $posttype ) {
		global $sitepress, $post;

		if ( defined('ICL_SITEPRESS_VERSION') && $sitepress && ! $post ) {
			$post = (object) array( 'post_type' => $posttype );
			$sitepress->post_edit_language_options();

			$post = null;
		}
	}

}
