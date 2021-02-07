<?php

class Tabify_Edit_Screen_Feature_Detection {
	/**
	 * Set hooks
	 *
	 * @since 0.9.0
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'head_action_begin_settings_page' ) );
		add_action( 'current_screen', array( $this, 'head_action_begin_edit_page' ) );

		// Hook for requesting missing hooks
		add_action( 'tabify_add_meta_boxes', array( $this, 'add_missing_meta_boxes' ) );

		// Checking & storing unattached meta boxes on the edit page
		add_action( 'tabify_unattached_metaboxes', array( $this, 'unattached_metaboxes' ) );
	}

	/**
	 * Checks if script for edit page need to be enqueued
	 *
	 * @since 1.0.0
	 */
	public function head_action_begin_settings_page( $screen ) {
		if ( 'settings_page_tabify-edit-screen' == $screen->base ) {
			$this->enqueue_script();
		}
	}

	/**
	 * Actions to return JSON output on post type new/edit screen
	 *
	 * @since 1.0.0
	 */
	public function head_action_begin_edit_page( $screen ) {
		if ( ( 'post' == $screen->base || 'media' == $screen->base ) && isset( $_GET['test_metaboxes'] ) ) {
			ob_end_clean(); // For when warnings are displayed
			ob_start();

			add_filter( 'tabify_tab_posttype_show', '__return_true', 1000 );
			add_action( 'admin_head', array( $this, 'head_action' ), 110 );
		}
	}

	/**
	 * Enqueue script to load detected meta boxes and display them in the settings page
	 *
	 * @since 0.9.0
	 */
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
				$url = add_query_arg( 'test_metaboxes', 'true', $url );
				$posttype_links[ $posttype->name ] = $url;
			}
			else {
				$url = admin_url('post-new.php');
				$url = add_query_arg( 'post_type', $posttype->name, $url );
				$url = add_query_arg( 'test_metaboxes', 'true', $url );
				$posttype_links[ $posttype->name ] = $url;
			}
		}

		wp_localize_script( 'tabify-edit-screen-detection', 'tabify_detection', array(
			'posttype_links' => $posttype_links
		) );
	}

	/**
	 * Load the JSON data for the settings page
	 *
	 * @since 0.9.0
	 */
	public function head_action() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		ob_end_clean();

		echo wp_json_encode( get_transient( 'tabify_detection_' . $screen->post_type ) );

		exit;
	}

	/**
	 * Add missing meta boxes as meta boxes on the edit screen so it doesn't disappear on first load
	 *
	 * @since 0.9.0
	 */
	public function add_missing_meta_boxes( $post_type ) {
		if ( is_array( $metaboxes = get_transient( 'tabify_detection_' . $post_type ) ) ) {
			foreach ( $metaboxes as $id => $metabox ) {
				if ( ! isset( $wp_meta_boxes[ $post_type ][ $metabox->context ][ $metabox->priority ][ $id ] ) ) {
					add_meta_box( $id, $metabox->title, '__return_false', $post_type, $metabox->priority, $metabox->context );
				}
			}
		}
	}

	/**
	 * Check and save missing 
	 *
	 * @since 0.9.0
	 */
	public function unattached_metaboxes( $unattached_metaboxes ) {
		$screen = get_current_screen();
		$previous_metaboxes = get_transient( 'tabify_detection_' . $screen->post_type );

		$metaboxes = array();

		if ( $unattached_metaboxes ) {
			$metaboxes = $this->get_metaboxes( $screen->post_type );

			foreach ( $metaboxes as $metabox_id => $metabox ) {
				if ( ! isset( $unattached_metaboxes[ $metabox_id ] )  ) {
					unset( $metaboxes[ $metabox_id ] );
				}
			}
		}

		if ($metaboxes != $previous_metaboxes) {
			set_transient( 'tabify_detection_' . $screen->post_type, $metaboxes, WEEK_IN_SECONDS );
		}
	}

	/**
	 * 
	 *
	 * @since 0.9.0
	 */
	private function get_metaboxes( $post_type ) {
		global $wp_meta_boxes;

		$metaboxes = array();

		foreach ( $wp_meta_boxes[ $post_type ] as $context => $priorities ) {
			foreach ( $priorities as $priority => $_metaboxes ) {
				foreach ( $_metaboxes as $metabox ) {
					// Metabox has been removed
					if ( ! isset( $metabox['id'] ) ) {
						continue;
					}

					$metaboxes[ $metabox['id'] ] = (object) array(
						'title'    => $metabox['title'],
						'priority' => $priority,
						'context'  => $context
					);
				}
			}
		}

		return $metaboxes;
	}

}