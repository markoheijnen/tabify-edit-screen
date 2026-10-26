<?php
/**
 * Plugin entry point.
 *
 * @package Tabify_Edit_Screen
 *
 * Plugin Name: Tabify Edit Screen
 * Description: Enables tabs in the edit screen and manage them from the back-end
 * Version: 1.0.0
 *
 * Plugin URI: https://codekitchen.eu/products/tabify-edit-screen/
 *
 * Author: CodeKitchen B.V.
 * Author URI: https://codekitchen.eu
 * Donate link: https://codekitchen.eu/donate
 *
 * Text Domain: tabify-edit-screen
 * Domain Path: /languages
 */

/*
	Copyright 2013-2016 Tabify Edit Screen (email : info@markoheijnen.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Load the plugin and its optional features.
 */
class Tabify_Edit_Screen {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Register cache invalidation and admin hooks.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		foreach ( array( 'activated_plugin', 'deactivated_plugin', 'switch_theme', 'upgrader_process_complete' ) as $hook ) {
			add_action( $hook, array( $this, 'invalidate_detection_cache' ) );
		}

		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( $this, 'load' ) );
			add_action( 'plugins_loaded', array( $this, 'load_translation' ) );
		}
	}

	/**
	 * Invalidate every detection variant, including during CLI/background updates.
	 */
	public function invalidate_detection_cache() {
		update_option( 'tabify_detection_generation', wp_generate_password( 12, false ), false );
	}


	/**
	 * Load functionality
	 *
	 * @since 0.9.0
	 */
	public function load() {
		require __DIR__ . '/inc/edit-screen.php';
		require __DIR__ . '/inc/settings-page.php';

		new Tabify_Edit_Screen_Edit_Screen();
		new Tabify_Edit_Screen_Settings_Page();

		add_action( 'admin_init', array( $this, 'load_features' ), 1 );
	}

	/**
	 * Load Translations
	 *
	 * @since 0.4.0
	 */
	public function load_translation() {
		load_plugin_textdomain( 'tabify-edit-screen', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Load features
	 *
	 * @since 0.9.0
	 */
	public function load_features() {
		$feature_files = array(
			'detection'      => 'features/detection/detection.php',
			'permissions'    => 'features/permissions/permissions.php',
			'plugin-support' => 'features/plugin-support/plugin-support.php',
		);
		$features      = array( 'detection', 'permissions' );

		if ( apply_filters( 'tabify_plugin_support', false ) ) {
			$features[] = 'plugin-support';
		}

		foreach ( $features as $feature ) {
			$class_name = 'Tabify_Edit_Screen_Feature_' . str_replace( '-', '_', $feature );

			require __DIR__ . '/' . $feature_files[ $feature ];
			new $class_name();
		}
	}
}

$GLOBALS['tabify_edit_screen'] = new Tabify_Edit_Screen();
