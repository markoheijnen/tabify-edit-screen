<?php

require_once __DIR__ . '/test-compatibility.php';

class Tabify_Detection_Response extends Exception {}

class Tabify_Tests_Detection extends WP_UnitTestCase {
	private $detection;
	private $previous_get;
	private $previous_boxes;

	public function set_up() {
		parent::set_up();
		$this->previous_get = $_GET;
		$this->previous_boxes = isset( $GLOBALS['wp_meta_boxes'] ) ? $GLOBALS['wp_meta_boxes'] : array();
		$GLOBALS['wp_meta_boxes'] = array();
		$_GET = array();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'post' );
		$this->detection = new Tabify_Edit_Screen_Feature_Detection();
	}

	public function tear_down() {
		$_GET = $this->previous_get;
		$GLOBALS['wp_meta_boxes'] = $this->previous_boxes;
		set_current_screen( 'front' );
		parent::tear_down();
	}

	private function invoke_private( $method, $arguments = array() ) {
		$reflection = new ReflectionMethod( $this->detection, $method );
		if ( PHP_VERSION_ID < 80100 ) {
			$reflection->setAccessible( true );
		}
		return $reflection->invokeArgs( $this->detection, $arguments );
	}

	public function stop_response() {
		throw new Tabify_Detection_Response();
	}

	private function response( $nonce = null ) {
		$_GET = array(
			'test_metaboxes' => 'true',
			'_tabify_nonce' => null === $nonce ? wp_create_nonce( 'tabify_detect_post' ) : $nonce,
		);
		$handler = function() {
			return array( $this, 'stop_response' );
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $handler );
		$level = ob_get_level();
		ob_start();
		try {
			$this->detection->head_action_begin_edit_page( get_current_screen() );
			ob_start();
			echo '<p>Discard the edit screen HTML.</p>';
			$this->detection->head_action();
		} catch ( Tabify_Detection_Response $exception ) {
			return json_decode( ob_get_contents(), true );
		} finally {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', $handler );
		}
	}

	public function test_fresh_detection_works_before_tabs_are_configured() {
		update_option( 'tabify-edit-screen', array() );
		add_meta_box( 'fresh', '<b>Fresh box</b>', '__return_false', 'post', 'side', 'high' );

		$response = $this->response();
		$this->assertSame( array( 'fresh' ), array_keys( $response ) );
		$this->assertSame( array( 'title' => 'Fresh box', 'priority' => 'high', 'context' => 'side' ), $response['fresh'] );
		$this->assertFalse( apply_filters( 'tabify_tab_posttype_show', true ) );
		$this->assertSame( array(), get_option( 'tabify-edit-screen' ) );
		$cached = $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) );
		$this->assertSame( 'Fresh box', $cached['fresh']->title );
	}

	public function test_refresh_replaces_stale_results_with_live_boxes() {
		$this->invoke_private( 'cache_metaboxes', array( 'post', array(
			'old' => (object) array( 'title' => 'Old', 'context' => 'normal', 'priority' => 'default' ),
		) ) );
		add_meta_box( 'new', 'New', '__return_false', 'post', 'normal', 'default' );

		$this->assertSame( array( 'new' ), array_keys( $this->response() ) );
		$this->assertSame( array( 'new' ), array_keys( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) ) );
	}

	public function test_expired_cache_can_be_rebuilt() {
		$key = $this->invoke_private( 'get_cache_key', array( 'post' ) );
		set_transient( $key, array(), -1 );
		$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
		add_meta_box( 'after-expiry', 'After expiry', '__return_false', 'post' );
		$this->assertArrayHasKey( 'after-expiry', $this->response() );
		$this->assertArrayHasKey( 'after-expiry', $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
	}

	public function test_restoration_keeps_context_priority_and_live_callbacks() {
		$boxes = array(
			'cached' => (object) array( 'title' => 'Cached', 'context' => 'side', 'priority' => 'high' ),
			'live' => (object) array( 'title' => 'Cached title', 'context' => 'normal', 'priority' => 'default' ),
			'removed' => (object) array( 'title' => 'Removed', 'context' => 'normal', 'priority' => 'default' ),
		);
		$this->invoke_private( 'cache_metaboxes', array( 'post', $boxes ) );
		add_meta_box( 'live', 'Live title', '__return_true', 'post', 'normal', 'default' );
		remove_meta_box( 'removed', 'post', 'normal' );
		$this->detection->add_missing_meta_boxes( 'post' );

		$this->assertSame( 'Cached', $GLOBALS['wp_meta_boxes']['post']['side']['high']['cached']['title'] );
		$this->assertSame( '__return_true', $GLOBALS['wp_meta_boxes']['post']['normal']['default']['live']['callback'] );
		$this->assertFalse( $GLOBALS['wp_meta_boxes']['post']['normal']['default']['removed'] );
	}

	public function test_invalid_cached_metadata_requires_detection() {
		$key = $this->invoke_private( 'get_cache_key', array( 'post' ) );
		foreach ( array( 'invalid', array( 'bad' => false ), array( 'bad' => (object) array( 'title' => 'Missing location' ) ) ) as $value ) {
			set_transient( $key, $value, WEEK_IN_SECONDS );
			$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
		}
		$this->invoke_private( 'cache_metaboxes', array( 'post', array() ) );
		$this->assertSame( array(), $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
	}

	public function test_environment_changes_invalidate_cached_results() {
		$this->invoke_private( 'cache_metaboxes', array( 'post', array() ) );
		$this->assertSame( array(), $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
		do_action( 'activated_plugin', 'example/example.php', false );
		$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
		foreach ( array( 'deactivated_plugin', 'switch_theme', 'upgrader_process_complete' ) as $hook ) {
			$this->assertNotFalse( has_action( $hook, array( $GLOBALS['tabify_edit_screen'], 'invalidate_detection_cache' ) ) );
		}
	}

	public function test_cache_invalidation_is_registered_outside_admin_screens() {
		set_current_screen( 'front' );
		$this->assertFalse( is_admin() );
		$plugin = new Tabify_Edit_Screen();
		$key = $this->invoke_private( 'get_cache_key', array( 'post' ) );
		$this->invoke_private( 'cache_metaboxes', array( 'post', array() ) );
		$this->assertNotFalse( has_action( 'upgrader_process_complete', array( $plugin, 'invalidate_detection_cache' ) ) );
		do_action( 'activated_plugin', 'background/example.php', false );
		$this->assertNotSame( $key, $this->invoke_private( 'get_cache_key', array( 'post' ) ) );
		$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
	}

	public function test_cache_is_separate_for_users() {
		$this->invoke_private( 'cache_metaboxes', array( 'post', array() ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
	}

	public function test_detection_links_validate_nonce() {
		$post_id = self::factory()->post->create();
		$url = $this->invoke_private( 'get_detection_url', array( get_post_type_object( 'post' ) ) );
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$this->assertSame( (string) $post_id, $query['post'] );
		$this->assertNotFalse( wp_verify_nonce( $query['_tabify_nonce'], 'tabify_detect_post' ) );
	}

	public function test_invalid_nonce_and_non_admin_cannot_refresh() {
		foreach ( array( 'invalid', array( 'invalid' ) ) as $nonce ) {
			$response = $this->response( $nonce );
			$this->assertFalse( $response['success'] );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertFalse( $this->response()['success'] );
		$this->assertFalse( $this->invoke_private( 'get_cached_metaboxes', array( 'post' ) ) );
	}

	public function test_only_missing_or_expired_results_trigger_automatic_detection() {
		$this->invoke_private( 'cache_metaboxes', array( 'post', array() ) );
		$this->invoke_private( 'enqueue_script' );
		$data = wp_scripts()->get_data( 'tabify-edit-screen-detection', 'data' );
		$this->assertNotFalse( strpos( $data, '"post":false' ) );
		delete_transient( $this->invoke_private( 'get_cache_key', array( 'post' ) ) );
		$this->invoke_private( 'enqueue_script' );
		$this->assertNotFalse( strpos( wp_scripts()->get_data( 'tabify-edit-screen-detection', 'data' ), '"post":true' ) );
	}

	public function test_refresh_control_is_available_in_toolbar() {
		$settings = new Tabify_Tests_Compatibility_Settings( 'posttypes' );
		ob_start();
		$settings->get_sections_box();
		$html = ob_get_clean();
		$this->assertNotFalse( strpos( $html, 'tabify-refresh-metaboxes' ) );
	}
}
