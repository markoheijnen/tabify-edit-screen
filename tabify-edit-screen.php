<?php
/*
	Plugin Name: Tabify edit screen
	Description: Enables tabs in the edit screen and manage them from the back-end
	Version: 0.9.2

	Plugin URI: http://rocksta.rs/plugin/tabify-edit-screen

	Author: Marko Heijnen
	Author URI: https://markoheijnen.com
	Donate link: https://markoheijnen.com/donate

	Text Domain: tabify-edit-screen
	Domain Path: /languages
*/

/*  Copyright 2013-2014 Tabify Edit Screen  (email : info@markoheijnen.com)

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

	public  $version = '0.9.2';

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

		add_action( 'current_screen', array( $this, 'load_features' ), 1 );
	}

	public function load_translation() {
		load_plugin_textdomain( 'tabify-edit-screen', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	public function load_features( $screen ) {
		$valid_screens = array(
			'settings_page_tabify-edit-screen',
			'post',
			'media'
		);

		if ( ! in_array( $screen->base, $valid_screens ) ) {
			return;
		}

		$features = array(
			'detection',
			'permissions'
		);

		foreach ( $features as $feature ) {
			$class_name = 'Tabify_Edit_Screen_Feature_' . $feature;

			include 'features/' . $feature . '/' . $feature . '.php';
			new $class_name;
		}
	}

}

$GLOBALS['tabify_edit_screen'] = new Tabify_Edit_Screen();