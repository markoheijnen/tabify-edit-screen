<?php

require_once __DIR__ . '/test-compatibility.php';

class Tabify_Tests_Edit_Screen extends WP_UnitTestCase {
	public function test_generated_javascript_contains_valid_json_and_assigns_unattached_boxes() {
		$previous_post  = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$previous_boxes = isset( $GLOBALS['wp_meta_boxes'] ) ? $GLOBALS['wp_meta_boxes'] : array();
		try {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			$GLOBALS['post']          = self::factory()->post->create_and_get();
			$GLOBALS['wp_meta_boxes'] = array();
			set_current_screen( 'post' );
			get_current_screen()->is_block_editor( false );
			add_meta_box( 'sample', 'Sample', '__return_false', 'post' );
			add_meta_box( 'extra', 'Extra', '__return_false', 'post' );
			add_filter(
				'tabify_default_metaboxes',
				function () {
					return array( 'submitdiv' );
				}
			);
			update_option(
				'tabify-edit-screen',
				array(
					'posttypes' => array(
						'post' => array(
							'show' => true,
							'tabs' => array(
								array(
									'title' => "O'Reilly <strong>Details</strong>",
									'items' => array( 'sample', 'titlediv' ),
								),
							),
						),
					),
				)
			);

			$screen = new Tabify_Edit_Screen_Edit_Screen();
			$screen->show_tabs();
			ob_start();
			try {
				$screen->generate_javascript();
				$script = ob_get_contents();
			} finally {
				ob_end_clean();
			}

			$this->assertSame( 1, preg_match( '~\.prepend\((.+?)\);~s', $script, $matches ), $script );
			$markup = json_decode( $matches[1], true );
			$this->assertSame( JSON_ERROR_NONE, json_last_error() );
			$this->assertStringContainsString( "O'Reilly <strong>Details</strong>", $markup );
			$this->assertStringContainsString( '\u003Cstrong\u003E', $script );
			$this->assertStringContainsString( 'jQuery("#titlediv").addClass("tabifybox tabifybox-0");', $script );
			$this->assertContains( 'tabifybox tabifybox-0', apply_filters( 'postbox_classes_post_extra', array() ) );
		} finally {
			$GLOBALS['post']          = $previous_post;
			$GLOBALS['wp_meta_boxes'] = $previous_boxes;
			set_current_screen( 'front' );
		}
	}
}
