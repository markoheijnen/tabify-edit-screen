<?php
/**
 * Legacy third-party plugin integrations.
 *
 * @package Tabify_Edit_Screen
 */

/**
 * Register meta boxes supplied by supported legacy plugins.
 */
class Tabify_Edit_Screen_Feature_Plugin_Support {
	/**
	 * Set hooks
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		$screen = get_current_screen();

		if ( ! $screen || 'settings_page_tabify-edit-screen' !== $screen->base ) {
			return;
		}

		add_action( 'tabify_add_meta_boxes', array( $this, 'types' ) );
		add_action( 'tabify_add_meta_boxes', array( $this, 'members' ) );
		add_action( 'tabify_add_meta_boxes', array( $this, 'wpml' ) );

		add_filter( 'wpseo_always_register_metaboxes_on_admin', '__return_true' );
		add_action( 'tabify_add_meta_boxes', array( $this, 'wpseo' ) ); // Legacy WordPress SEO integration.
	}

	/**
	 * Load widgets created by Types
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function types( $posttype ) {
	}

	/**
	 * Load widgets created by WordPress SEO
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function wpseo() {
		if ( defined( 'WPSEO_PATH' ) && is_file( WPSEO_PATH . 'admin/class-metabox.php' ) ) {
			include_once WPSEO_PATH . 'admin/class-metabox.php';
		}
	}


	/**
	 * Load widgets created by Members
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function members() {
		if ( function_exists( 'members_admin_setup' ) && ! did_action( 'load-post.php' ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- This is a WordPress core hook name.
			do_action( 'load-post.php' );
		}
	}

	/**
	 * Load widgets created by Members
	 *
	 * @param string $posttype The posttype the metaboxes should be loaded from.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function wpml( $posttype ) {
		global $sitepress, $post;

		if ( defined( 'ICL_SITEPRESS_VERSION' ) && $sitepress && ! $post ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The legacy WPML callback requires temporary post context.
			$post = (object) array( 'post_type' => $posttype );
			$sitepress->post_edit_language_options();

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the original empty context after rendering.
			$post = null;
		}
	}
}
