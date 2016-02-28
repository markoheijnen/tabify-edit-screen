<?php

class Tabify_Edit_Screen_Feature_Detection {

	private $allowed_request_errors = 3;

	public function __construct() {
		// Actions to return JSON output on post type new/edit screen
		add_action( 'current_screen', array( $this, 'head_action_begin' ) );

		// Hook for requesting missing hooks
		add_action( 'tabify_add_meta_boxes', array( $this, 'add_missing_meta_boxes' ) );

		// Checking & storing unattached meta boxes on the edit page
		add_action( 'tabify_unattached_metaboxes', array( $this, 'unattached_metaboxes' ) );
	}


	public function head_action_begin( $screen ) {
		if ( ( 'post' == $screen->base || 'media' == $screen->base ) && isset( $_GET['tes_metaboxes'] ) ) {
			ob_end_clean(); // For when warnings are displayed
			ob_start();

			add_filter( 'tabify_tab_posttype_show', '__return_true', 1000 );
			add_action( 'admin_head', array( $this, 'head_action' ), 110 );
		}
		else if ( 'settings_page_tabify-edit-screen' == $screen->base ) {
			$this->enqueue_script();
		}
	}

	private function enqueue_script() {
		wp_register_script( 'tabify-edit-screen-detection', plugins_url( '/detection.js', __FILE__ ), array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-detection' );

		$posttype_links = array();

		$args = array(
			'show_ui' => true
		);
		$posttypes_objects = get_post_types( $args, 'objects' );
		$posttypes_objects = apply_filters( 'tabify_posttypes', $posttypes_objects );

		foreach ( $posttypes_objects as $posttype ) {
			if ( get_transient( 'tabify_detection_' . $posttype->name ) !== false ) {
				continue;
			}

			$args = array(
				'post_type'      => $posttype->name,
				'orderby'        => 'rand',
				'posts_per_page' => '1',
				'post_status'    => 'any'
			);
			$post = get_posts( $args );

			if ( ! empty( $post ) ) {
				$url = get_edit_post_link( $post[0], 'raw' );
				$url = add_query_arg( 'tes_metaboxes', 'true', $url );
				$posttype_links[ $posttype->name ] = $url;
			}
		}

		wp_localize_script( 'tabify-edit-screen-detection', 'tabify_detection', array(
			'posttype_links' => $posttype_links
		) );
	}

	public function head_action() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		ob_end_clean();

		echo wp_json_encode( get_transient( 'tabify_detection_' . $screen->post_type ) );

		exit;
	}


	public function add_missing_meta_boxes( $post_type ) {
		if ( is_array( $metaboxes = get_transient( 'tabify_detection_' . $post_type ) ) ) {
			foreach ( $metaboxes as $id => $metabox ) {
				if ( ! isset( $wp_meta_boxes[ $post_type ][ $metabox->context ][ $metabox->priority ][ $id ] ) ) {
					add_meta_box( $id, $metabox->title, '__return_false', $post_type, $metabox->priority, $metabox->context );
				}
			}
		}
	}

	public function unattached_metaboxes( $unattached_metaboxes ) {
		global $wp_meta_boxes;

		$all_metaboxes = array();
		$screen        = get_current_screen();

		if ( get_transient( 'tabify_detection_' . $screen->post_type ) !== false ) {
			return;
		}

		if ( $unattached_metaboxes ) {
			$locations     = $wp_meta_boxes[ $screen->post_type ];

			foreach ( $locations as $context => $priorities ) {
				foreach ( $priorities as $priority => $_metaboxes ) {
					foreach ( $_metaboxes as $metabox ) {
						// Metabox has been removed
						if ( ! isset( $metabox['id'] ) ) {
							continue;
						}

						$all_metaboxes[ $metabox['id'] ] = (object) array(
							'title'    => $metabox['title'],
							'priority' => $priority,
							'context'  => $context
						);
					}
				}
			}

			foreach ( $all_metaboxes as $metabox_id => $metabox ) {
				if ( ! isset( $unattached_metaboxes[ $metabox_id ] )  ) {
					unset( $all_metaboxes[ $metabox_id ] );
				}
			}
		}

		set_transient( 'tabify_detection_' . $screen->post_type, $all_metaboxes );
	}

}