<?php
/*
	Plugin Name: Tabify Edit Screen
	Description: Enables tabs in the edit screen and manage them from the back-end
	Version: 0.9.5

	Plugin URI: https://codekitchen.eu/products/tabify-edit-screen/

	Author: Marko Heijnen
	Author URI: https://codekitchen.eu
	Donate link: https://codekitchen.eu/donate

	Text Domain: tabify-edit-screen
	Domain Path: /languages
*/

/*  Copyright 2013-2016 Tabify Edit Screen (email : info@markoheijnen.com)

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

class Tabify_Edit_Screen {

	public  $version = '0.9.4';

	private $loaded_features = array();

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( $this, 'load' ) );
			add_action( 'plugins_loaded', array( $this, 'load_translation' ) );
		}
	}


	public function load() {
		include 'inc/edit-screen.php';
		include 'inc/settings-page.php';

		new Tabify_Edit_Screen_Edit_Screen();
		new Tabify_Edit_Screen_Settings_Page();

		add_action( 'admin_init', array( $this, 'load_features' ), 1 );
	}

	public function load_translation() {
		load_plugin_textdomain( 'tabify-edit-screen', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	public function load_features( $screen ) {
		$features = array(
			'detection',
			'permissions'
		);

		if ( apply_filters( 'tabify_plugin_support', false ) ) {
			$features[] = 'plugin-support';
		}

		foreach ( $features as $feature ) {
			$this->loaded_features[] = $feature;

			$class_name = 'Tabify_Edit_Screen_Feature_' . str_replace( '-', '_', $feature );

			include 'features/' . $feature . '/' . $feature . '.php';
			new $class_name;
		}
	}

}

$GLOBALS['tabify_edit_screen'] = new Tabify_Edit_Screen();