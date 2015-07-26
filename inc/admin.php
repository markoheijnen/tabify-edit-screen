<?php

include 'settings-base.php';
include 'settings-posttype.php';

class Tabify_Edit_Screen_Admin {
	private $tabs;
	private $options;

	/**
	 * Load helper methods
	 *
	 * @since 0.8.2
	 */
	public function __construct() {
		$this->load_plugin_support();
	}

	/**
	 * Adds a option page to manage all the tabs
	 *
	 * @since 0.1.0
	 */
	public function admin_menu() {
		add_options_page( __( 'Tabify edit screen', 'tabify-edit-screen' ), __( 'Tabify edit screen', 'tabify-edit-screen' ), 'manage_options', 'tabify-edit-screen', array( $this, 'edit_screen' ) );
	}

	/**
	 * Option page that handles the form request
	 *
	 * @since 0.1.0
	 */
	public function edit_screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'tabify-edit-screen-admin', plugins_url( '/js/admin' . $suffix . '.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui-sortable', 'jquery-touch-punch' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-admin' );

		$data = array(
			'remove' => __( 'Remove', 'tabify-edit-screen' ),
			'cancel' => __( 'Cancel', 'tabify-edit-screen' ),
			'choose_title' => __( 'Choose title', 'tabify-edit-screen' ),
			'move_meta_boxes' => __( 'Move meta boxes to', 'tabify-edit-screen' )
		);
		wp_localize_script( 'tabify-edit-screen-admin', 'tabify_l10', $data );
		
		echo '<div class="wrap">';

		screen_icon('options-general');
		echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';

		echo '<form id="tabify-form" method="post">';
		wp_nonce_field( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' );

		echo '<input type="hidden" id="tabify_edit_screen_nojs" name="tabify_edit_screen_nojs" value="1" />';

		$tabs = array(
			'posttypes' => array(
				'title' => __('Post types', 'tabify-edit-screen' ),
				'class' => 'Tabify_Edit_Screen_Settings_Posttypes'
			)
		);
		$tabs = apply_filters( 'tabify_settings_tabs', $tabs );

		$this->tabs = new Tabify_Edit_Screen_Tabs( $tabs, 'horizontal', 'tab', false );

		if ( count( $tabs ) > 1 ) {
			echo $this->tabs->get_tabs_with_container();
		}

		if ( isset( $tabs[ $this->tabs->get_current_tab() ] ) ) {
			$class_name = $tabs[ $this->tabs->get_current_tab() ]['class'];
			$settings_screen = new $class_name();

			$this->update_settings();

			echo '<div id="tabify-settings">';
				echo '<div id="tabify-submenu">';
				echo $settings_screen->get_sections_menu();
				echo '</div>';

				echo '<div id="tabifyboxes">';
				echo $settings_screen->get_section();
				echo '</div>';
			echo '</div>';

			echo '</form>';
			echo '</div>';
		}
	}

	/**
	 * Updates settings
	 *
	 * @since 0.2.0
	 *
	 */
	private function update_settings() {
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['tabify'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' ) ) {
			$options = $_POST['tabify'];

			$options = apply_filters( 'tabify_settings_update', $options );

			update_option( 'tabify-edit-screen', $options );
		}
	}

	/**
	 * Load additional support for plugins that have unique code
	 *
	 * @since 0.4.0
	 */
	private function load_plugin_support() {
		global $pagenow;

		// Only load this when the user needs it.
		if ( ! apply_filters( 'tabify_plugin_support', false ) ) {
			return;
		}

		// Only load on our page.
		if ( 'options-general.php' != $pagenow || ! isset( $_GET['page'] ) || 'tabify-edit-screen' != $_GET['page'] ) {
			return;
		}

		include 'plugin-support.php';
		new Tabify_Edit_Screen_Plugin_Support();
	}

}
