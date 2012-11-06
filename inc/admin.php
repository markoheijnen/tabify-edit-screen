<?php

class Tabify_Edit_Screen_Admin {
	private $tabs;
	private $options;

	/**
	 * Adds a option page to manage all the tabs
	 *
	 * @since 0.1
	 */
	public function admin_menu() {
		add_options_page( __( 'Tabify edit screen', 'tabify-edit-screen' ), __( 'Tabify edit screen', 'tabify-edit-screen' ), 'manage_options', 'tabify-edit-screen', array( &$this, 'edit_screen' ) );
	}

	/**
	 * Option page that handles the form request
	 *
	 * @since 0.1
	 */
	public function edit_screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
		}

		$this->load_plugin_support();

		$this->update_settings();

		wp_register_script( 'tabify-edit-screen-admin', plugins_url( '/js/admin.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui-sortable' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-admin' );

		$data = array( 'remove' => __( 'Remove', 'tabify-edit-screen' ), 'choose_title' => __( 'Choose title', 'tabify-edit-screen' ) );
		wp_localize_script( 'tabify-edit-screen-admin', 'tabify_l10', $data );

		if( ! wp_script_is( 'jquery-touch-punch', 'registered' ) ) {
			wp_register_script( 'jquery-touch-punch', plugins_url( '/js/jquery.ui.touch-punch.js', dirname( __FILE__ ) ), array( 'jquery-ui-widget', 'jquery-ui-mouse' ), '0.2.2', 1 ); 
		}
		wp_enqueue_script( 'jquery-touch-punch' );
		
		echo '<div class="wrap">';

		screen_icon();
		echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';

		echo '<form id="tabify-form" method="post">';
		wp_nonce_field( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' );

		echo '<input type="hidden" id="tabify_edit_screen_nojs" name="tabify_edit_screen_nojs" value="1" />';

		include 'settings-base.php';
		include 'settings-posttype.php';


		$tabs = array(
			'posttypes' => array( 'title' => __('Post types'), 'class' => 'Tabify_Edit_Screen_Settings_Posttypes' )
		);
		$tabs = apply_filters( 'tabify-edit-screen-settings-tabs', $tabs );

		$this->tabs = new Tabify_Edit_Screen_Tabs( $tabs, 'horizontal', 'tab', false );

		if( count( $tabs ) > 0 ) {
			echo $this->tabs->get_tabs_with_container();
		}

		if( isset( $tabs[ $this->tabs->get_current_tab() ] ) ) {
			$settings_posttype = new $tabs[ $this->tabs->get_current_tab() ]['class']();

			echo '<div id="tabify-settings">';
				echo '<div id="tabifyboxes">';
				echo $settings_posttype->get_section();
				echo '</div>';

				echo '<div id="tabify-submenu">';
				echo $settings_posttype->get_sections_menu();
				echo '</div>';
			echo '</div>';

			echo '</form>';
			echo '</div>';
		}
	}

	/**
	 * Updates settings
	 *
	 * @since 0.2
	 *
	 */
	private function update_settings() {
		if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['tabify'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' ) ) {
			$options = $_POST['tabify'];
			$options = $this->escape( $options );

			$options = apply_filters( 'tabify-settings-update', $options );

			update_option( 'tabify-edit-screen', $options );
		}
	}

	/**
	 * Sanitize the options array to be how we expect it to be
	 *
	 * @since 0.2
	 *
	 * @param array $posttypes Raw options array
	 * @return array filtered options array
	 */
	private function escape( $posttypes ) {
		$posttypes_keys = array_keys( $posttypes );
		$amount_posttypes = count( $posttypes );
		for( $i = 0; $i < $amount_posttypes; $i++ ) {
			$key = $posttypes_keys[ $i ];

			if( isset( $posttypes[ $key ]['show'] ) && $posttypes[ $key ]['show'] == 1 ) {
				$posttypes[ $key ]['show'] = intval( $posttypes[ $key ]['show'] );
			}
			else {
				$posttypes[ $key ]['show'] = 0;
			}

			$amount_tabs = count( $posttypes[ $key ]['tabs'] );
			for( $j = 0; $j < $amount_tabs; $j++ ) {
				$posttypes[ $key ]['tabs'][ $j ]['title'] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['title'] ) );

				if( !isset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] ) || count( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] ) == 0 ) {
					if( $posttypes[ $key ]['tabs'][ $j ]['title'] == '' ) {
						unset( $posttypes[ $key ]['tabs'][ $j ] );
					}
					continue;
				}

				$amount_metaboxes = count( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] );
				for( $k = 0; $k < $amount_metaboxes; $k++ ) {
					// Should the metabox be moved. Only applies when browser doesn't support Javascript
					if(
						isset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] ) &&
						$posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] != $j &&
						isset( $posttypes[ $key ]['tabs'][ intval( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] ) ] )
					) {
						$new_tab_key = intval( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] );

						if( ! isset( $posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] ) ) {
							$posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] = array();
							$metaboxes_in_new_tab = 0;
						}
						else { 
							$metaboxes_in_new_tab = count( $posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] );
						}

						$posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'][ $k ] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] ) );
						unset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] );
					}
					else {
						$posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] ) );
					}
				}
				$posttypes[ $key ]['tabs'][ $j ]['metaboxes'] = array_values( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] );
			}
		}

		return $posttypes;
	}

	/**
	 * Load additional support for plugins that have unique code
	 *
	 * @since 0.4
	 */
	private function load_plugin_support() {
		if( apply_filters( 'tabify_plugin_support', false ) ) {
			include 'plugin-support.php';
			new Tabify_Edit_Screen_Plugin_Support();
		}
	}
}