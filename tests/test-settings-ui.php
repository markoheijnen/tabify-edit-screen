<?php

require_once __DIR__ . '/test-compatibility.php';

class Tabify_Tests_Settings_UI extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'post' );
	}

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_controls_are_available_above_and_below_tabs() {
		$settings = new Tabify_Tests_Compatibility_Settings( 'posttypes' );
		ob_start();
		$settings->get_sections_box();
		$html = ob_get_clean();
		$this->assertSame( 2, substr_count( $html, 'name="create_tab"' ) );
		$this->assertLessThan( strpos( $html, 'class="tabifybox ' ), strpos( $html, 'id="create_tab"' ) );
		$this->assertSame( 1, substr_count( $html, 'id="create_tab"' ) );
	}
}
