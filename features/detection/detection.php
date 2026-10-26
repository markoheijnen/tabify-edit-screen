<?php

class Tabify_Edit_Screen_Feature_Detection {
	private $buffer_level;

	/**
	 * Set hooks.
	 *
	 * @since 0.9.0
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'head_action_begin_settings_page' ) );
		add_action( 'current_screen', array( $this, 'head_action_begin_edit_page' ) );
		add_action( 'tabify_settings_toolbar', array( $this, 'settings_toolbar' ) );
		add_action( 'tabify_add_meta_boxes', array( $this, 'add_missing_meta_boxes' ) );
		add_action( 'tabify_unattached_metaboxes', array( $this, 'unattached_metaboxes' ) );
	}

	public function head_action_begin_settings_page( $screen ) {
		if ( 'settings_page_tabify-edit-screen' === $screen->base && current_user_can( 'manage_options' ) ) {
			$this->enqueue_script();
		}
	}

	/**
	 * Collect boxes from a real edit screen, including when tabs are disabled.
	 */
	public function head_action_begin_edit_page( $screen ) {
		if ( ! in_array( $screen->base, array( 'post', 'media' ), true ) || ! isset( $_GET['test_metaboxes'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_tabify_nonce'] ) || ! is_string( $_GET['_tabify_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_tabify_nonce'] ), 'tabify_detect_' . $screen->post_type ) ) {
			wp_send_json_error( __( 'You are not allowed to refresh these meta boxes. Reload the settings page and try again.', 'tabify-edit-screen' ), 403 );
		}

		$this->buffer_level = ob_get_level();
		ob_start();

		// Detection must not depend on saved tabs or apply their role restrictions.
		add_filter( 'tabify_tab_posttype_show', '__return_false', PHP_INT_MAX );
		add_action( 'admin_head', array( $this, 'head_action' ), 110 );
	}

	private function enqueue_script() {
		wp_register_script( 'tabify-edit-screen-detection', plugins_url( '/detection.js', __FILE__ ), array( 'jquery' ), '1.1.0' );
		wp_enqueue_script( 'tabify-edit-screen-detection' );

		$posttype_links = array();
		$needs_detection = array();
		$posttypes = apply_filters( 'tabify_posttypes', get_post_types( array( 'show_ui' => true ), 'objects' ) );

		foreach ( $posttypes as $posttype ) {
			$url = $this->get_detection_url( $posttype );
			if ( $url ) {
				$posttype_links[ $posttype->name ] = $url;
				$needs_detection[ $posttype->name ] = false === $this->get_cached_metaboxes( $posttype->name );
			}
		}

		wp_localize_script( 'tabify-edit-screen-detection', 'tabify_detection', array(
			'posttype_links'  => $posttype_links,
			'needs_detection' => $needs_detection,
			'loading'        => __( 'Checking for meta boxes…', 'tabify-edit-screen' ),
			'complete'       => __( 'Meta boxes refreshed. Save your changes to keep new assignments.', 'tabify-edit-screen' ),
			'empty'          => __( 'No meta boxes were found on the edit screen.', 'tabify-edit-screen' ),
			'failed'         => __( 'Meta boxes could not be refreshed. Try again or reload this page.', 'tabify-edit-screen' ),
			'unavailable'    => __( 'No editable screen is available for this post type.', 'tabify-edit-screen' ),
		) );
	}

	private function get_detection_url( $posttype ) {
		if ( ! current_user_can( $posttype->cap->edit_posts ) ) {
			return false;
		}

		$posts = get_posts( array(
			'post_type'        => $posttype->name,
			'orderby'          => 'ID',
			'order'            => 'DESC',
			'posts_per_page'   => 1,
			'post_status'      => 'any',
			'suppress_filters' => false,
		) );
		$url = $posts ? get_edit_post_link( $posts[0], 'raw' ) : false;

		if ( ! $url ) {
			if ( 'attachment' === $posttype->name ) {
				return false;
			}
			$create_cap = isset( $posttype->cap->create_posts ) ? $posttype->cap->create_posts : $posttype->cap->edit_posts;
			if ( ! current_user_can( $create_cap ) ) {
				return false;
			}
			$url = add_query_arg( 'post_type', $posttype->name, admin_url( 'post-new.php' ) );
		}

		$language = apply_filters( 'wpml_current_language', null );
		if ( is_string( $language ) && '' !== $language ) {
			$url = add_query_arg( 'lang', $language, $url );
		}

		return add_query_arg( array(
			'test_metaboxes' => 'true',
			'_tabify_nonce' => wp_create_nonce( 'tabify_detect_' . $posttype->name ),
		), $url );
	}

	public function settings_toolbar( $type ) {
		if ( 'posttypes' !== $type ) {
			return;
		}

		echo '<button type="button" class="button tabify-refresh-metaboxes hide-if-no-js">' . esc_html__( 'Refresh meta boxes', 'tabify-edit-screen' ) . '</button>';
		echo '<span class="tabify-detection-status" role="status" aria-live="polite"></span>';
	}

	/**
	 * Return freshly registered boxes instead of replaying an expired transient.
	 */
	public function head_action() {
		$screen = get_current_screen();
		$metaboxes = $this->get_metaboxes( $screen->post_type );
		$this->cache_metaboxes( $screen->post_type, $metaboxes );

		while ( null !== $this->buffer_level && ob_get_level() > $this->buffer_level ) {
			ob_end_clean();
		}

		wp_send_json( $metaboxes );
	}

	private function get_cache_key( $post_type ) {
		$context = array(
			'generation' => get_option( 'tabify_detection_generation', '1' ),
			'locale'     => get_locale(),
			'language'   => apply_filters( 'wpml_current_language', null ),
			'user'       => get_current_user_id(),
		);
		$context = apply_filters( 'tabify_detection_context', $context, $post_type );

		return 'tabify_detection_' . $post_type . '_' . md5( wp_json_encode( $context ) );
	}

	private function get_cached_metaboxes( $post_type ) {
		$metaboxes = get_transient( $this->get_cache_key( $post_type ) );
		if ( ! is_array( $metaboxes ) ) {
			return false;
		}

		foreach ( $metaboxes as $metabox ) {
			if ( ! is_object( $metabox ) || ! isset( $metabox->title, $metabox->context, $metabox->priority ) || ! is_string( $metabox->title ) || ! is_string( $metabox->context ) || ! is_string( $metabox->priority ) ) {
				return false;
			}
		}

		return $metaboxes;
	}

	private function cache_metaboxes( $post_type, $metaboxes ) {
		set_transient( $this->get_cache_key( $post_type ), $metaboxes, WEEK_IN_SECONDS );
	}

	/**
	 * Restore cached boxes on settings screens without replacing live callbacks.
	 */
	public function add_missing_meta_boxes( $post_type ) {
		global $wp_meta_boxes;

		$metaboxes = $this->get_cached_metaboxes( $post_type );
		if ( false === $metaboxes ) {
			return;
		}

		foreach ( $metaboxes as $id => $metabox ) {
			if ( ! isset( $wp_meta_boxes[ $post_type ][ $metabox->context ][ $metabox->priority ][ $id ] ) ) {
				add_meta_box( $id, $metabox->title, '__return_false', $post_type, $metabox->context, $metabox->priority );
			}
		}
	}

	/**
	 * Keep detection current when a configured edit screen is visited normally.
	 */
	public function unattached_metaboxes( $unattached_metaboxes ) {
		$screen = get_current_screen();
		if ( $screen && $screen->post_type ) {
			$this->cache_metaboxes( $screen->post_type, $this->get_metaboxes( $screen->post_type ) );
		}
	}

	private function get_metaboxes( $post_type ) {
		global $wp_meta_boxes;

		$metaboxes = array();
		if ( ! isset( $wp_meta_boxes[ $post_type ] ) || ! is_array( $wp_meta_boxes[ $post_type ] ) ) {
			return $metaboxes;
		}

		foreach ( $wp_meta_boxes[ $post_type ] as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( $boxes as $metabox ) {
					if ( ! isset( $metabox['id'], $metabox['title'] ) || ! is_scalar( $metabox['title'] ) ) {
						continue;
					}

					$metaboxes[ $metabox['id'] ] = (object) array(
						'title'    => wp_strip_all_tags( (string) $metabox['title'] ),
						'priority' => $priority,
						'context'  => $context,
					);
				}
			}
		}

		return $metaboxes;
	}
}
