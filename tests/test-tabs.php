<?php

require_once __DIR__ . '/test-compatibility.php';

class Tabify_Tests_Tabs extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'post' );
	}

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_short_admin_color_palettes_render_without_warnings() {
		global $_wp_admin_css_colors;
		$previous = $_wp_admin_css_colors;
		try {
			$_wp_admin_css_colors = array( 'fresh' => (object) array( 'colors' => array( '#111111', '#222222', '#333333' ) ) );
			$tabs = new Tabify_Edit_Screen_Tabs( array( 'main' => 'Main' ) );
			$tabs->get_tabs_with_container();
			$styles = wp_styles()->get_data( 'tabify-edit-screen', 'after' );
			$this->assertNotFalse( strpos( end( $styles ), 'background: #333333' ) );
		} finally {
			$_wp_admin_css_colors = $previous;
		}
	}

	public function test_classic_tabs_do_not_run_in_the_block_editor() {
		get_current_screen()->is_block_editor( true );
		update_option( 'tabify-edit-screen', array( 'posttypes' => array( 'post' => array( 'show' => true, 'tabs' => array() ) ) ) );
		$screen = new Tabify_Edit_Screen_Edit_Screen();
		$this->assertNull( $screen->show_tabs() );
		$this->assertFalse( has_filter( 'admin_body_class', array( $screen, 'add_admin_body_class' ) ) );
	}
}
