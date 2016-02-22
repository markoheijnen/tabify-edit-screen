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
			ob_end_clean(); // For when warnings are displayed
			ob_start();

			add_action( 'admin_head', array( $this, 'head_action' ), 100 );
		}
		else if ( 'settings_page_tabify-edit-screen' == $screen->base ) {
			$this->enqueue_script();
		}
	}

	private function enqueue_script() {
		wp_register_script( 'tabify-edit-screen-detection', plugins_url( '/detection.js', __FILE__ ), array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-detection' );

		$post_type_links = array();

		$args = array(
			'show_ui' => true
		);
		$posttypes_objects = get_post_types( $args, 'objects' );
		$posttypes_objects = apply_filters( 'tabify_posttypes', $posttypes_objects );

		foreach ( $posttypes_objects as $posttype ) {
			$args = array(
				'post_type'      => $posttype->name,
				'orderby'        => 'rand',
				'posts_per_page' => '1'
			);
			$post = get_posts( $args );

			$url = get_edit_post_link( $post[0], 'raw' );
			$url = add_query_arg( 'tes_metaboxes', 'true', $url );

			$post_type_links[ $posttype->name ] = $url;
		}

		wp_localize_script( 'tabify-edit-screen-detection', 'tabify_detection', $post_type_links );
	}

	public function head_action() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		$list = array();

		$locations = $wp_meta_boxes[ $screen->post_type ];
		foreach ( $locations as $context => $priorities ) {
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

		ob_end_clean();
		
		echo wp_json_encode( $list );

		exit;
	}








	


	public function add_missing_meta_boxes( $post_type ) {
		if ( is_array( $metaboxes = get_transient( 'tabify_detection_' . $post_type ) ) ) {
			foreach( $metaboxes as $id => $metabox ) {
				if ( ! isset( $wp_meta_boxes[ $post_type ][ $metabox->context ][ $metabox->priority ][ $id ] ) ) {
					add_meta_box( $id, $metabox->title, '__return_false', $post_type, $metabox->priority, $metabox->context );
				}
			}
		}
	}

	public function unattached_metaboxes( $metaboxes ) {
		$ids = array_keys( $metaboxes );

	}

}