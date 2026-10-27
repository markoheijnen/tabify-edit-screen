<?php

require_once __DIR__ . '/test-compatibility.php';
require_once dirname( __DIR__ ) . '/inc/settings-page.php';

class Tabify_Tests_Custom_Settings_Screen {
	public function get_sections_menu() {
		echo 'Custom settings menu';
	}

	public function get_sections_box() {
		echo 'Custom settings fields';
	}
}

class Tabify_Tests_Settings_UI extends WP_UnitTestCase {
	private $previous_title;

	public function set_up() {
		parent::set_up();
		$this->previous_title = isset( $GLOBALS['title'] ) ? $GLOBALS['title'] : null;
		$GLOBALS['title']     = 'Tabify edit screen';
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'post' );
	}

	public function tear_down() {
		$GLOBALS['title'] = $this->previous_title;
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

	public function test_invalid_settings_classes_do_not_break_the_page() {
		foreach ( array( 'stdClass', 'Tabify_Missing_Settings_Class' ) as $class_name ) {
			$filter = function ( $tabs ) use ( $class_name ) {
				$tabs['posttypes']['class'] = $class_name;
				return $tabs;
			};
			add_filter( 'tabify_settings_tabs', $filter );
			ob_start();
			try {
				$page = new Tabify_Edit_Screen_Settings_Page();
				$page->edit_screen();
				$html = ob_get_contents();
			} finally {
				ob_end_clean();
				remove_filter( 'tabify_settings_tabs', $filter );
			}
			$this->assertStringNotContainsString( 'id="tabify-settings"', $html );
			$this->assertStringEndsWith( '</form></div>', $html );
		}
	}

	public function test_custom_settings_class_can_render_without_extending_the_base() {
		$filter = function ( $tabs ) {
			$tabs['posttypes']['class'] = 'Tabify_Tests_Custom_Settings_Screen';
			return $tabs;
		};
		add_filter( 'tabify_settings_tabs', $filter );
		ob_start();
		try {
			$page = new Tabify_Edit_Screen_Settings_Page();
			$page->edit_screen();
			$html = ob_get_contents();
		} finally {
			ob_end_clean();
			remove_filter( 'tabify_settings_tabs', $filter );
		}
		$this->assertStringContainsString( 'Custom settings menu', $html );
		$this->assertStringContainsString( 'Custom settings fields', $html );
	}

	private function submit_settings( $options, $extra = array() ) {
		$previous_post    = $_POST;
		$previous_request = $_REQUEST;
		$previous_server  = $_SERVER;
		try {
			new Tabify_Edit_Screen_Settings_Posttypes();
			$page                                 = new Tabify_Edit_Screen_Settings_Page();
			$_SERVER['REQUEST_METHOD']            = 'POST';
			$_POST                                = array_merge( array( 'tabify' => wp_slash( $options ) ), $extra );
			$_REQUEST['tabify_edit_screen_nonce'] = wp_create_nonce( plugin_basename( dirname( __DIR__ ) . '/inc/settings-page.php' ) );
			$method                               = new ReflectionMethod( $page, 'update_settings' );
			if ( PHP_VERSION_ID < 80100 ) {
				$method->setAccessible( true );
			}
			$method->invoke( $page );
		} finally {
			$_POST    = $previous_post;
			$_REQUEST = $previous_request;
			$_SERVER  = $previous_server;
		}
	}

	public function test_settings_request_is_unslashed_exactly_once() {
		$title   = "O'Reilly \\ manuals";
		$options = array(
			'posttypes' => array(
				'post' => array(
					'show' => 1,
					'tabs' => array(
						array(
							'title' => $title,
							'items' => array( 'sample' ),
						),
					),
				),
			),
		);
		$this->submit_settings( $options );
		$saved = get_option( 'tabify-edit-screen' );
		$this->assertSame( $title, $saved['posttypes']['post']['tabs'][0]['title'] );
	}

	public function test_malformed_request_does_not_replace_existing_settings() {
		$previous = array( 'posttypes' => array() );
		update_option( 'tabify-edit-screen', $previous );
		$this->submit_settings( 'invalid' );
		$this->assertSame( $previous, get_option( 'tabify-edit-screen' ) );
	}

	public function test_array_section_cannot_create_a_tab() {
		$options = array(
			'posttypes' => array(
				'post' => array(
					'tabs' => array(
						array(
							'title' => 'Main',
							'items' => array( 'sample' ),
						),
					),
				),
			),
		);
		$this->submit_settings(
			$options,
			array(
				'create_tab' => '1',
				'subtab'     => array( 'post' ),
			)
		);
		$saved = get_option( 'tabify-edit-screen' );
		$this->assertCount( 1, $saved['posttypes']['post']['tabs'] );
	}
}
