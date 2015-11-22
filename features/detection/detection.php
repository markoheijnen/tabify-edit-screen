<?php

class Tabify_Edit_Screen_Feature_Detection {

	private $allowed_request_errors = 3;

	public function __construct() {
		// Actions to return JSON output on post type new/edit screen
		add_action( 'current_screen', array( $this, 'head_action_begin' ) );

		// Hook for requesting missing hooks
		add_action( 'tabify_add_meta_boxes', array( $this, 'add_missing_meta_boxes' ) );

		// Possible hook for checking unattached meta boxes
		add_action( 'tabify_unattached_metaboxes', array( $this, 'unattached_metaboxes' ) );
	}


	public function head_action_begin( $screen ) {
		if ( ( 'post' == $screen->base || 'media' == $screen->base ) && isset( $_GET['tes_metaboxes'] ) ) {
			ob_start();

			add_action( 'admin_head', array( $this, 'head_action' ), 100 );
		}
	}

	public function head_action( $screen ) {
		global $wp_meta_boxes;

		$list = array();

		foreach ( $wp_meta_boxes as $posttype => $items ) {
			foreach ( $items as $context => $priorities ) {
				foreach ( $priorities as $priority => $_metaboxes ) {
					foreach ( $_metaboxes as $metabox ) {
						$list[ $metabox['id'] ] = array(
							'title'    => $metabox['title'],
							'priority' => $priority,
							'context'  => $context
						);
					}
				}
			}
		}

		ob_end_clean();
		
		echo wp_json_encode( $list );

		exit;
	}


	public function add_missing_meta_boxes( $post_type ) {
		if ( get_transient( 'tabify_detection_stop_detecting' ) ) {
			return;
		}

		if ( false === ( $data = get_transient( 'tabify_detectdion_' . $post_type ) ) ) {
			global $wp_meta_boxes;

			if ( $this->allowed_request_errors <= 0 ) {
				return;
			}

			$args = array(
				'post_type'      => $post_type,
				'orderby'        => 'rand',
				'posts_per_page' => '1'
			);
			$post = get_posts( $args );

			$url = get_edit_post_link( $post[0], 'raw' );
			$url = add_query_arg( 'tes_metaboxes', 'true', $url );


			$cookies      = array();
			foreach( $_COOKIE as $name => $value ) {
				$cookies[] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
			}
			$response = wp_remote_get( esc_url_raw( $url ), array( 'cookies' => $cookies, 'timeout' => 2 ) );

			if ( is_wp_error( $response ) ) {
				$this->allowed_request_errors--;

				set_transient( 'tabify_detection_stop_detecting', true, WEEK_IN_SECONDS );
				return;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( ! $body ) {
				set_transient( 'tabify_detection_' . $post_type, array(), DAY_IN_SECONDS );
				return;
			}
			
			$data = json_decode( $body );

			// Store data
			set_transient( 'tabify_detection_' . $post_type, $data, DAY_IN_SECONDS );
		}

		$this->load_metaboxes( $data, $post_type );
	}

	public function load_metaboxes( $metaboxes, $post_type ) {
		foreach( $metaboxes as $id => $metabox ) {
			if ( ! isset( $wp_meta_boxes[ $post_type ][ $metabox->context ][ $metabox->priority ][ $id ] ) ) {
				add_meta_box( $id, $metabox->title, '__return_false', $post_type, $metabox->priority, $metabox->context );
			}
		}
	}


	public function unattached_metaboxes( $metaboxes ) {
		$ids = array_keys( $metaboxes );

	}

}