<?php

class Tabify_Edit_Screen_Tab_Permissions {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'tabify_settings_tab_title_box', array( $this, 'settings_tab_title_box' ) );
		add_action( 'tabify_settings_tab_title_after', array( $this, 'settings_tab_title_after' ), 10, 3 );
	}


	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'settings_page_tabify-edit-screen' != $screen->base ) {
			return;
		}

		wp_register_script( 'tabify-edit-screen-permissions', plugins_url( '/permissions.js', __FILE__ ), array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-permissions' );
	}


	public function settings_tab_title_box( $tab ) {
		if ( $tab['permissions'] && is_array( $tab['permissions'] ) ) {
			$count = count( $tab['permissions'] );
			$btn_permissions = sprintf( _n( '%s role', '%s roles', $count, 'tabift-edit-screen' ), $count );
		}
		else {
			$btn_permissions = __( 'Everyone', 'tabify-edit-screen' );
		}

		echo '<button class="tabify-tab-permissions button button-secondary" type="button">' . $btn_permissions . '</button>';
	}

	public function settings_tab_title_after( $tab, $section, $type ) {
		echo '<div class="tabify-tab-permission-box">';

		$all_roles = wp_roles()->roles;
		foreach ( $all_roles as $key => $role ) {
			$name    = 'tabify[' . $type . '][' . $section . '][tabs][' . $tab['id'] . '][permissions][]';
			$checked = in_array( $key, $tab['permissions'] ) ? ' checked="checked"' : '';

			echo '<span><input name="' . $name . '" type="checkbox" value="' . $key . '"' . $checked . '/> ' . $role['name'] . '</span>';
		}

		echo '</div>';
	}

}
