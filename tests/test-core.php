<?php
/**
 * Tests to test that that testing framework is testing tests. Meta, huh?
 * @package wordpress-plugins-tests
 */
class Tabify_Tests_Core extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		set_current_screen( 'post' );
	}

	function tearDown() {
		parent::tearDown();
		set_current_screen( 'front' );
	}

	function test_Tabify_Edit_Screen__redirect_add_current_tab() {
		global $tabify_edit_screen;

		$this->assertEquals( $tabify_edit_screen->redirect_add_current_tab( 'my-location', 1 ), 'my-location' );
	}

	function test_Tabify_Edit_Screen__show_tabs() {
		global $tabify_edit_screen;

		$this->assertNull( $tabify_edit_screen->show_tabs() );
	}
}
