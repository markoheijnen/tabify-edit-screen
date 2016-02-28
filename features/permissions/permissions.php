<?php

class Tabify_Edit_Screen_Feature_Permissions {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'tabify_settings_tab_title_box', array( $this, 'settings_tab_title_box' ) );
		add_action( 'tabify_settings_tab_title_after', array( $this, 'settings_tab_title_after' ), 10, 3 );

		// Remove meta boxes when no permissions
		add_filter( 'tabify_tab_posttype_tabs', array( $this, 'posttype_tabs' ), 10, 2 );
	}


	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'settings_page_tabify-edit-screen' != $screen->base ) {
			return;
		}

		wp_register_script( 'tabify-edit-screen-permissions', plugins_url( '/permissions.js', __FILE__ ), array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-permissions' );

		$data = array(
			'everyone'  => __( 'Everyone', 'tabify-edit-screen' ),
			'onerole'   => __( '1 role', 'tabify-edit-screen' ),
			'multirole' => __( '%s role', 'tabify-edit-screen' )
		);
		wp_localize_script( 'tabify-edit-screen-permissions', 'tabify_permissions', $data );
	}


	public function settings_tab_title_box( $tab ) {
		if ( $tab['permissions'] && is_array( $tab['permissions'] ) ) {
			$count = count( $tab['permissions'] );
			$btn_permissions = sprintf( _n( '1 role', '%s roles', $count, 'tabify-edit-screen' ), $count );
		}
		else {
			$btn_permissions = __( 'Everyone', 'tabify-edit-screen' );
		}

		echo '<button class="tabify-tab-permissions button button-secondary hide-if-no-js" type="button">' . $btn_permissions . '</button>';
	}

	public function settings_tab_title_after( $tab, $section, $type ) {
		echo '<div class="tabify-tab-permission-box">';

		$all_roles = $this->get_roles();
		foreach ( $all_roles as $key => $role ) {
			$name    = 'tabify[' . $type . '][' . $section . '][tabs][' . $tab['id'] . '][permissions][]';
			$checked = in_array( $key, $tab['permissions'] ) ? ' checked="checked"' : '';

			echo '<label>';
			echo '<input  name="' . $name . '" type="checkbox" value="' . $key . '"' . $checked . '/>';
			echo $role['name'];
			echo '</label>';
		}

		echo '</div>';
	}


	public function posttype_tabs( $tabs, $post_type ) {
		foreach( $tabs as $index => $tab ) {
			if ( ! isset( $tab['permissions'] ) ) {
				continue;
			}

			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role ) {
				if ( in_array( $role, $tab['permissions'] ) ) {
					continue 2;
				}
			}

			foreach ( $tab['items'] as $item ) {
				if ( 'titlediv' == $item ) {
					remove_post_type_support( $post_type, 'title' );
				}
				elseif ( 'postdivrich' == $item ) {
					remove_post_type_support( $post_type, 'editor' );
				}
				else {
					// Just guess the context of the meta box
					remove_meta_box( $item, $post_type, 'normal' );
					remove_meta_box( $item, $post_type, 'advanced' );
					remove_meta_box( $item, $post_type, 'side' );
				}
			}

			unset( $tabs[ $index ] );
		}

		return $tabs;
	}


	private function get_roles() {
		if ( ! function_exists( 'wp_roles' ) ) {
			global $wp_roles;

			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}
		else {
			$wp_roles = wp_roles();
		}

		return $wp_roles->roles;
	}

}
