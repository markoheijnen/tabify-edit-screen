<?php

class Tabify_Edit_Screen_Feature_Detection {

	public function __construct() {
		// Actions to return JSON output on post type new/edit screen
		add_action( 'current_screen', array( $this, 'head_action_begin' ) );
		add_action( 'admin_head', array( $this, 'head_action' ), 100 );

		// Hook for requesting missing hooks
		add_action( 'tabify_add_meta_boxes', array( $this, 'add_missing_meta_boxes' ) );

		// Possible hook for checking unattached meta boxes
		add_action( 'tabify_unattached_metaboxes', array( $this, 'unattached_metaboxes' ) );
	}


	public function head_action_begin( $screen ) {
		if ( ( 'post' == $screen->base || 'media' == $screen->base ) && isset( $_GET['tes_metaboxes'] ) ) {
			ob_start();
		}
	}

	public function head_action( $screen ) {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		if ( ( 'post' == $screen->base || 'media' == $screen->base ) && isset( $_GET['tes_metaboxes'] ) ) {
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
	}


	public function add_missing_meta_boxes( $post_type ) {
		if ( false === ( $data = get_transient( 'tabify_detection_' . $post_type ) ) ) {
			global $wp_meta_boxes;

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
			$response = wp_remote_get( esc_url_raw( $url ), array( 'cookies' => $cookies ) );
			$body     = wp_remote_retrieve_body( $response );

			if ( ! $body ) {
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
