<?php
/**
 * Role restrictions for tabs.
 *
 * @package Tabify_Edit_Screen
 */

/**
 * Control tab visibility and render role restrictions.
 */
class Tabify_Edit_Screen_Feature_Permissions {
	/**
	 * Set hooks
	 *
	 * @since 0.9.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'tabify_settings_tab_title_box', array( $this, 'settings_tab_title_box' ) );
		add_action( 'tabify_settings_tab_title_after', array( $this, 'settings_tab_title_after' ), 10, 3 );

		// Remove meta boxes when no permissions.
		add_filter( 'tabify_tab_posttype_tabs', array( $this, 'posttype_tabs' ), 10, 2 );
	}

	/**
	 * Enqueue script for the settings page
	 *
	 * @since 0.9.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! $screen || 'settings_page_tabify-edit-screen' !== $screen->base ) {
			return;
		}

		wp_register_script( 'tabify-edit-screen-permissions', plugins_url( '/permissions.js', __FILE__ ), array( 'jquery' ), '1.0', false );
		wp_enqueue_script( 'tabify-edit-screen-permissions' );

		$data = array(
			'everyone'  => __( 'Everyone', 'tabify-edit-screen' ),
			'onerole'   => __( '1 role', 'tabify-edit-screen' ),
			/* translators: %s: Number of selected roles. */
			'multirole' => __( '%s role', 'tabify-edit-screen' ),
		);
		wp_localize_script( 'tabify-edit-screen-permissions', 'tabify_permissions', $data );
	}

	/**
	 * Add button next tot title of a tab
	 *
	 * @since 0.9.0
	 *
	 * @param array $tab Tab configuration.
	 */
	public function settings_tab_title_box( $tab ) {
		if ( ! empty( $tab['permissions'] ) && is_array( $tab['permissions'] ) ) {
			$count = count( $tab['permissions'] );
			/* translators: %s: Number of selected roles. */
			$btn_permissions = sprintf( _n( '%s role', '%s roles', $count, 'tabify-edit-screen' ), number_format_i18n( $count ) );
		} else {
			$btn_permissions = __( 'Everyone', 'tabify-edit-screen' );
		}

		echo '<button class="tabify-tab-permissions button button-secondary hide-if-no-js" type="button">' . esc_html( $btn_permissions ) . '</button>';
	}

	/**
	 * Display the settings after the title
	 *
	 * @since 0.9.0
	 *
	 * @param array  $tab Tab configuration.
	 * @param string $section Section name.
	 * @param string $type Settings group.
	 */
	public function settings_tab_title_after( $tab, $section, $type ) {
		echo '<div class="tabify-tab-permission-box">';

		$all_roles   = $this->get_roles();
		$permissions = isset( $tab['permissions'] ) && is_array( $tab['permissions'] ) ? $tab['permissions'] : array();
		foreach ( $all_roles as $key => $role ) {
			$name    = 'tabify[' . $type . '][' . $section . '][tabs][' . $tab['id'] . '][permissions][]';
			$allowed = in_array( $key, $permissions, true );

			echo '<label>';
			echo '<input  name="' . esc_attr( $name ) . '" type="checkbox" value="' . esc_attr( $key ) . '"' . checked( $allowed, true, false ) . '/>';
			echo esc_html( translate_user_role( $role['name'] ) );
			echo '</label>';
		}

		echo '</div>';
	}

	/**
	 * Remove meta boxes when no permissions
	 *
	 * @since 0.9.0
	 *
	 * @param mixed  $tabs Configured tabs.
	 * @param string $post_type Post-type name.
	 * @return array Accessible tabs.
	 */
	public function posttype_tabs( $tabs, $post_type ) {
		if ( ! is_array( $tabs ) ) {
			return array();
		}

		foreach ( $tabs as $index => $tab ) {
			if ( ! is_array( $tab ) ) {
				unset( $tabs[ $index ] );
				continue;
			}

			if ( ! isset( $tab['permissions'] ) ) {
				continue;
			}

			// Invalid restrictions must not grant access to a tab.
			$permissions  = is_array( $tab['permissions'] ) ? $tab['permissions'] : array();
			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role ) {
				if ( in_array( $role, $permissions, true ) ) {
					continue 2;
				}
			}

			$items = isset( $tab['items'] ) && is_array( $tab['items'] ) ? $tab['items'] : array();
			foreach ( $items as $item ) {
				if ( 'titlediv' === $item ) {
					remove_post_type_support( $post_type, 'title' );
				} elseif ( 'postdivrich' === $item ) {
					remove_post_type_support( $post_type, 'editor' );
				} else {
					// Just guess the context of the meta box.
					remove_meta_box( $item, $post_type, 'normal' );
					remove_meta_box( $item, $post_type, 'advanced' );
					remove_meta_box( $item, $post_type, 'side' );
				}
			}

			unset( $tabs[ $index ] );
		}

		return $tabs;
	}

	/**
	 * Get the roles of WordPress
	 *
	 * @since 0.9.0
	 */
	private function get_roles() {
		if ( function_exists( 'wp_roles' ) ) {
			$roles = wp_roles();
		} else {
			$roles = isset( $GLOBALS['wp_roles'] ) ? $GLOBALS['wp_roles'] : new WP_Roles();
		}

		return $roles->roles;
	}
}
