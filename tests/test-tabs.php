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
			$tabs                 = new Tabify_Edit_Screen_Tabs( array( 'main' => 'Main' ) );
			$tabs->get_tabs_with_container();
			$styles = wp_styles()->get_data( 'tabify-edit-screen', 'after' );
			$this->assertNotFalse( strpos( end( $styles ), 'background: #333333' ) );
		} finally {
			$_wp_admin_css_colors = $previous;
		}
	}

	public function test_classic_tabs_do_not_run_in_the_block_editor() {
		get_current_screen()->is_block_editor( true );
		update_option(
			'tabify-edit-screen',
			array(
				'posttypes' => array(
					'post' => array(
						'show' => true,
						'tabs' => array(),
					),
				),
			)
		);
		$screen = new Tabify_Edit_Screen_Edit_Screen();
		$this->assertNull( $screen->show_tabs() );
		$this->assertFalse( has_filter( 'admin_body_class', array( $screen, 'add_admin_body_class' ) ) );
	}

	public function test_numeric_zero_tab_is_selected_from_the_request() {
		$previous_request = $_REQUEST;
		try {
			$_REQUEST['tab'] = '0';
			$tabs            = new Tabify_Edit_Screen_Tabs(
				array(
					2 => 'Other',
					0 => '<strong>Zero</strong>',
				)
			);
			$this->assertSame( '0', $tabs->get_current_tab() );
			$html = $tabs->get_tabs_with_container();
			$this->assertStringContainsString( 'id="tab-0"', $html );
			$this->assertStringContainsString( 'class="tabify-tab nav-tab nav-tab-active"><strong>Zero</strong>', $html );
		} finally {
			$_REQUEST = $previous_request;
		}
	}

	public function test_array_tab_request_is_ignored() {
		$previous_request = $_REQUEST;
		try {
			$_REQUEST['tab'] = array( 'invalid' );
			$tabs            = new Tabify_Edit_Screen_Tabs( array( 'main' => 'Main' ) );
			$this->assertSame( 'main', $tabs->get_current_tab() );
			$screen = new Tabify_Edit_Screen_Edit_Screen();
			$this->assertSame( 'https://example.org/edit', $screen->redirect_add_current_tab( 'https://example.org/edit' ) );
		} finally {
			$_REQUEST = $previous_request;
		}
	}

	public function test_rendered_tab_titles_retain_safe_html_and_remove_scripts() {
		$tabs = new Tabify_Edit_Screen_Tabs( array( 'main' => '<strong onclick="alert(1)">Main</strong><script>alert(1)</script>' ) );
		$html = $tabs->get_tabs_with_container();
		$this->assertStringContainsString( '<strong>Main</strong>', $html );
		$this->assertStringNotContainsString( 'onclick=', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}
	public function test_malformed_request_uri_is_ignored() {
		$previous_server = $_SERVER;
		try {
			$_SERVER['REQUEST_URI'] = array( 'invalid' );
			$tabs                   = new Tabify_Edit_Screen_Tabs( array( 'main' => 'Main' ) );
			$this->assertStringContainsString( 'id="tab-main"', $tabs->get_tabs_with_container() );
		} finally {
			$_SERVER = $previous_server;
		}
	}

	public function test_invalid_items_keep_explicit_display_settings() {
		$tabs = new Tabify_Edit_Screen_Tabs( null, 'vertical', 'section', false );
		$html = $tabs->get_tabs_with_container();
		$this->assertStringContainsString( 'class="tabify-tabs tab-vertical js-disabled"', $html );
		$this->assertStringContainsString( 'name="section"', $html );
	}

	public function test_slashed_string_tab_is_preserved_in_navigation() {
		$previous_request = $_REQUEST;
		try {
			$tab_name        = "writer's-tab";
			$_REQUEST['tab'] = wp_slash( $tab_name );
			$tabs            = new Tabify_Edit_Screen_Tabs(
				array(
					'main'    => 'Main',
					$tab_name => 'Writer',
				)
			);
			$this->assertSame( $tab_name, $tabs->get_current_tab() );
			$screen = new Tabify_Edit_Screen_Edit_Screen();
			$url    = $screen->redirect_add_current_tab( 'https://example.org/edit' );
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$this->assertSame( $tab_name, $query['tab'] );
		} finally {
			$_REQUEST = $previous_request;
		}
	}
}
