<?php

require_once dirname( __DIR__ ) . '/inc/edit-screen.php';
require_once dirname( __DIR__ ) . '/inc/settings-base.php';
require_once dirname( __DIR__ ) . '/inc/settings-posttype.php';
require_once dirname( __DIR__ ) . '/features/detection/detection.php';
require_once dirname( __DIR__ ) . '/features/permissions/permissions.php';

class Tabify_Tests_Compatibility_Settings extends Tabify_Edit_Screen_Settings_Base {
	protected function load_sections() {
		return array( 'post' => 'Posts' );
	}

	protected function load_items() {
		if ( ! $this->items ) {
			$this->items = array( 'post' => array( 'sample' => 'Sample meta box' ) );
		}
	}
}

class Tabify_Tests_Compatibility extends WP_UnitTestCase {
	private function invoke_private( $object, $method, $arguments = array() ) {
		$reflection = new ReflectionMethod( $object, $method );
		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}

		return $reflection->invokeArgs( $object, $arguments );
	}

	public function test_removed_meta_boxes_are_ignored() {
		global $wp_meta_boxes;
		$previous = $wp_meta_boxes;
		$wp_meta_boxes = array( 'post' => array( 'normal' => array( 'default' => array(
			'removed' => false,
			'sample'  => array( 'id' => 'sample', 'title' => 'Sample meta box' ),
			'submit'  => array( 'id' => 'submitdiv', 'title' => 'Publish' ),
		) ) ) );

		try {
			$screen = new Tabify_Edit_Screen_Edit_Screen();
			$this->assertSame( array( 'sample' => 'sample' ), $this->invoke_private( $screen, 'get_meta_boxes', array( 'post' ) ) );

			$detection = new Tabify_Edit_Screen_Feature_Detection();
			$detected = $this->invoke_private( $detection, 'get_metaboxes', array( 'post' ) );
			$this->assertSame( array( 'sample', 'submitdiv' ), array_keys( $detected ) );
		} finally {
			$wp_meta_boxes = $previous;
		}
	}

	public function test_missing_meta_box_registry_is_empty() {
		global $wp_meta_boxes;
		$previous = $wp_meta_boxes;

		try {
			foreach ( array( null, array() ) as $registry ) {
				$wp_meta_boxes = $registry;
				$screen = new Tabify_Edit_Screen_Edit_Screen();
				$detection = new Tabify_Edit_Screen_Feature_Detection();
				$this->assertSame( array(), $this->invoke_private( $screen, 'get_meta_boxes', array( 'post' ) ) );
				$this->assertSame( array(), $this->invoke_private( $detection, 'get_metaboxes', array( 'post' ) ) );
			}
		} finally {
			$wp_meta_boxes = $previous;
		}
	}

	public function test_missing_and_invalid_tabs_can_be_saved() {
		$settings = new Tabify_Edit_Screen_Settings_Posttypes();
		$cases = array( array( 'show' => 1 ) );
		foreach ( array( null, false, 'invalid', 42 ) as $tabs ) {
			$cases[] = array( 'show' => 1, 'tabs' => $tabs );
		}

		foreach ( $cases as $posttype ) {
			$saved = $settings->save_settings( array( 'posttypes' => array( 'post' => $posttype ) ) );
			$this->assertSame( array( 'show' => true, 'tabs' => array() ), $saved['posttypes']['post'] );
		}
	}

	public function test_invalid_items_can_be_saved() {
		$settings = new Tabify_Edit_Screen_Settings_Posttypes();
		foreach ( array( null, false, 'invalid', 42 ) as $items ) {
			$saved = $settings->save_settings( array( 'posttypes' => array( 'post' => array(
				'tabs' => array( array( 'title' => 'Main', 'items' => $items ) ),
			) ) ) );
			$this->assertSame( array( 'title' => 'Main', 'items' => array() ), $saved['posttypes']['post']['tabs'][0] );
		}
	}

	public function test_invalid_settings_containers_are_ignored() {
		$settings = new Tabify_Edit_Screen_Settings_Posttypes();
		$this->assertSame( array(), $settings->save_settings( false ) );
		$this->assertSame( array( 'other' => array() ), $settings->save_settings( array( 'other' => array() ) ) );
		$this->assertSame( array( 'posttypes' => array() ), $settings->save_settings( array( 'posttypes' => 'invalid' ) ) );
		$this->assertSame( array( 'posttypes' => array() ), $settings->save_settings( array( 'posttypes' => array( 'post' => false ) ) ) );
	}

	public function test_valid_settings_keep_titles_items_and_permissions() {
		$settings = new Tabify_Edit_Screen_Settings_Posttypes();
		$tab = array( 'title' => '<strong>Main</strong>', 'items' => array( 'sample' ), 'permissions' => array( 'editor' ) );
		$saved = $settings->save_settings( array( 'posttypes' => array( 'post' => array( 'show' => 1, 'tabs' => array( $tab ) ) ) ) );
		$this->assertSame( array( 'show' => true, 'tabs' => array( $tab ) ), $saved['posttypes']['post'] );
	}

	public function test_items_can_still_be_moved_without_javascript() {
		$settings = new Tabify_Edit_Screen_Settings_Posttypes();
		$saved = $settings->save_settings( array( 'posttypes' => array( 'post' => array( 'tabs' => array(
			array( 'title' => 'First', 'items' => array( 'sample' ), 'items_tab' => array( 1 ) ),
			array( 'title' => 'Second', 'items' => array() ),
		) ) ) ) );
		$this->assertSame( array(), $saved['posttypes']['post']['tabs'][0]['items'] );
		$this->assertSame( array( 'sample' ), $saved['posttypes']['post']['tabs'][1]['items'] );
	}

	public function test_invalid_permissions_remain_restricted() {
		global $wp_meta_boxes;
		$previous = $wp_meta_boxes;
		$user = wp_get_current_user();
		$roles = $user->roles;
		$user->roles = array( 'administrator' );

		try {
			$permissions = new Tabify_Edit_Screen_Feature_Permissions();
			foreach ( array( 'administrator', false, 42, array() ) as $restriction ) {
				$wp_meta_boxes = array();
				$tabs = array( array( 'permissions' => $restriction, 'items' => array( 'sample' ) ) );
				$this->assertSame( array(), $permissions->posttype_tabs( $tabs, 'post' ) );
				$this->assertFalse( $wp_meta_boxes['post']['normal']['default']['sample'] );
			}
		} finally {
			$user->roles = $roles;
			$wp_meta_boxes = $previous;
		}
	}

	public function test_valid_permissions_allow_only_matching_roles() {
		global $wp_meta_boxes;
		$previous = $wp_meta_boxes;
		$user = wp_get_current_user();
		$roles = $user->roles;
		$user->roles = array( 'administrator' );

		try {
			$wp_meta_boxes = array();
			$permissions = new Tabify_Edit_Screen_Feature_Permissions();
			$tabs = array(
				array( 'title' => 'Public', 'items' => array( 'public' ) ),
				array( 'title' => 'Admin', 'permissions' => array( 'administrator' ), 'items' => array( 'admin' ) ),
				array( 'title' => 'Editor', 'permissions' => array( 'editor' ), 'items' => array( 'editor' ) ),
			);
			$this->assertSame( array( $tabs[0], $tabs[1] ), $permissions->posttype_tabs( $tabs, 'post' ) );
			$this->assertFalse( $wp_meta_boxes['post']['normal']['default']['editor'] );
			$this->assertFalse( isset( $wp_meta_boxes['post']['normal']['default']['admin'] ) );
		} finally {
			$user->roles = $roles;
			$wp_meta_boxes = $previous;
		}
	}

	public function test_invalid_tab_items_are_filtered_out() {
		$screen = new Tabify_Edit_Screen_Edit_Screen();
		foreach ( array( null, false, 'invalid', array( 'items' => 'invalid' ) ) as $tab ) {
			$this->assertFalse( $screen->filter_empty_tabs( $tab ) );
		}
	}

	public function test_permissions_controls_accept_missing_and_invalid_permissions() {
		$permissions = new Tabify_Edit_Screen_Feature_Permissions();
		$tabs = array( array( 'id' => 0 ), array( 'id' => 0, 'permissions' => 'invalid' ) );
		foreach ( $tabs as $tab ) {
			ob_start();
			try {
				$permissions->settings_tab_title_box( $tab );
				$permissions->settings_tab_title_after( $tab, 'post', 'posttypes' );
				$html = ob_get_contents();
			} finally {
				ob_end_clean();
			}
			$this->assertNotFalse( strpos( $html, 'Everyone' ) );
			$this->assertFalse( strpos( $html, 'checked="checked"' ) );
		}
	}

	public function test_settings_page_recovers_missing_and_invalid_tabs() {
		$cases = array( 'invalid', array( 'posttypes' => array( 'post' => array() ) ) );
		foreach ( array( null, false, 'invalid', array() ) as $tabs ) {
			$cases[] = array( 'posttypes' => array( 'post' => array( 'show' => true, 'tabs' => $tabs ) ) );
		}

		foreach ( $cases as $options ) {
			$filter = function() use ( $options ) {
				return $options;
			};
			add_filter( 'pre_option_tabify-edit-screen', $filter );
			try {
				$settings = new Tabify_Tests_Compatibility_Settings( 'posttypes' );
				ob_start();
				try {
					$settings->get_sections_box();
					$html = ob_get_contents();
				} finally {
					ob_end_clean();
				}
				$this->assertNotFalse( strpos( $html, 'Sample meta box' ) );
				$this->assertNotFalse( strpos( $html, 'tabify[posttypes][post][tabs][0][title]' ) );
			} finally {
				remove_filter( 'pre_option_tabify-edit-screen', $filter );
			}
		}
	}
}
