<?php

class Tabify_Edit_Screen_Settings_Posttypes extends Tabify_Edit_Screen_Settings_Base {

	/**
	 * Loads the base with the type
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		parent::__construct('posttypes');

		add_filter( 'tabify_settings_update', array( $this, 'save_settings' ) );

		$posttypes = $this->get_sections();
		$metaboxes = $this->get_metaboxes();
		$this->items = apply_filters( 'tabify_metaboxes', $metaboxes, $posttypes );
	}

	/**
	 * Loads all the posttypes as sections
	 *
	 * @since 0.4.0
	 */
	protected function load_sections() {
		$args = array(
			'show_ui' => true
		);

		$posttypes_objects = get_post_types( $args, 'objects' );
		$posttypes_objects = apply_filters( 'tabify_posttypes', $posttypes_objects );

		$posttypes = array();
		foreach ( $posttypes_objects as $posttype_object ) {
			if ( is_object( $posttype_object ) ) {
				$posttypes[ $posttype_object->name ] = $posttype_object->label;
			}
		}

		return $posttypes;
	}

	/**
	 * Gets all the metaboxes that are registered
	 *
	 * @since 0.1.0
	 */
	private function get_metaboxes() {
		global $wp_meta_boxes;

		$metaboxes = array();
		$sections  = $this->get_sections();

		foreach ( $sections as $posttype => $label ) {
			$metaboxes[ $posttype ] = array();

			if ( post_type_supports( $posttype, 'title' ) ) {
				$metaboxes[ $posttype ][ 'titlediv'] = __( 'Title' );
			}

			if ( post_type_supports( $posttype, 'editor' ) ) {
				$metaboxes[ $posttype ][ 'postdivrich'] = __( 'Editor' );
			}

			$this->load_default_metaboxes( $posttype );
			do_action( 'add_meta_boxes', $posttype, null );
			do_action( 'add_meta_boxes_' . $posttype, null );

			do_action( 'tabify_add_meta_boxes', $posttype );
		}

		foreach ( $wp_meta_boxes as $posttype => $context ) {
			foreach ( $context as $priorities ) {
				foreach ( $priorities as $priority => $_metaboxes ) {
					foreach ( $_metaboxes as $metabox ) {
						// Metabox has been removed
						if ( ! isset( $metabox['id'] ) ) {
							continue;
						}

						$metaboxes[ $posttype ][ $metabox['id'] ] = $metabox['title'];
					}
				}
			}
		}


		return $metaboxes;
	}

	public function save_settings( $options ) {
		$options['posttypes'] = $this->escape( $options['posttypes'] );

		return $options;
	}

	/**
	 * Sanitize the options array to be how we expect it to be
	 *
	 * @since 0.2.0
	 *
	 * @param array $posttypes Raw options array
	 * @return array filtered options array
	 */
	private function escape( $posttypes ) {
		$posttypes_keys    = array_keys( $posttypes );
		$amount_posttypes  = count( $posttypes );

		$kses_allowed_html = array(
			'b'    => array(),
			'em'   => array(),
			'i'    => array(),
			'span' => array(
				'style' => true
			),
			'strong' => array(
				'style' => true
			)
		);
		$kses_allowed_html = apply_filters( 'tabify_posttype_escape_kses', $kses_allowed_html );

		for ( $i = 0; $i < $amount_posttypes; $i++ ) {
			$key = $posttypes_keys[ $i ];

			if ( isset( $posttypes[ $key ]['show'] ) && $posttypes[ $key ]['show'] == 1 ) {
				$posttypes[ $key ]['show'] = intval( $posttypes[ $key ]['show'] );
			}
			else {
				$posttypes[ $key ]['show'] = 0;
			}

			$amount_tabs = count( $posttypes[ $key ]['tabs'] );
			for ( $j = 0; $j < $amount_tabs; $j++ ) {
				if ( ! isset( $posttypes[ $key ]['tabs'][ $j ] ) ) {
					continue;
				}

				$posttypes[ $key ]['tabs'][ $j ]['title'] = stripslashes( $posttypes[ $key ]['tabs'][ $j ]['title'] );
				$posttypes[ $key ]['tabs'][ $j ]['title'] = wp_kses( $posttypes[ $key ]['tabs'][ $j ]['title'], $kses_allowed_html );

				if ( ! isset( $posttypes[ $key ]['tabs'][ $j ]['items'] ) || count( $posttypes[ $key ]['tabs'][ $j ]['items'] ) == 0 ) {
					if ( $posttypes[ $key ]['tabs'][ $j ]['title'] == '' ) {
						unset( $posttypes[ $key ]['tabs'][ $j ] );
					}

					continue;
				}

				$amount_metaboxes = count( $posttypes[ $key ]['tabs'][ $j ]['items'] );

				for ( $k = 0; $k < $amount_metaboxes; $k++ ) {
					// Should the metabox be moved. Only applies when browser doesn't support Javascript
					if (
						isset( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] ) &&
						$posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] != $j &&
						isset( $posttypes[ $key ]['tabs'][ intval( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] ) ] )
					) {
						$new_tab_key = intval( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] );

						if ( ! isset( $posttypes[ $key ]['tabs'][ $new_tab_key ]['items'] ) ) {
							$posttypes[ $key ]['tabs'][ $new_tab_key ]['items'] = array();
							$metaboxes_in_new_tab = 0;
						}
						else { 
							$metaboxes_in_new_tab = count( $posttypes[ $key ]['tabs'][ $new_tab_key ]['items'] );
						}

						$posttypes[ $key ]['tabs'][ $new_tab_key ]['items'][ $k ] = wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] );
						unset( $posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] );
					}
					else {
						$posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] = wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] );
					}
				}

				unset( $posttypes[ $key ]['tabs'][ $j ]['items_tab'] );
				$posttypes[ $key ]['tabs'][ $j ]['items'] = array_values( $posttypes[ $key ]['tabs'][ $j ]['items'] );
			}
		}

		return $posttypes;
	}

	/**
	 * Gets all the default WordPress metaboxes
	 * Little bit hackish but it works. Hopefully one day there will be a method for this in core.
	 *
	 * @since 0.1.0
	 */
	private function load_default_metaboxes( $post_type ) {
		if ( 'attachment' == $post_type ) {
			add_meta_box( 'submitdiv', __('Save'), 'attachment_submit_meta_box', $post_type, 'side', 'core' );
			add_meta_box( 'attachment-id3', __( 'Metadata' ), 'attachment_id3_data_meta_box', $post_type, 'normal', 'core' );
		}
		else {
			add_meta_box( 'submitdiv', __('Publish'), 'post_submit_meta_box', $post_type, 'side', 'core' );
		}

		if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ) {
			add_meta_box( 'formatdiv', _x( 'Format', 'post format' ), 'post_format_meta_box', $post_type, 'side', 'core' );
		}

		// all taxonomies
		foreach ( get_object_taxonomies( $post_type ) as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );

			if ( ! $taxonomy->show_ui ) {
				continue;
			}

			$label = $taxonomy->labels->name;

			if ( ! is_taxonomy_hierarchical( $tax_name ) ) {
				add_meta_box( 'tagsdiv-' . $tax_name, $label, 'post_tags_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ) );
			}
			else {
				add_meta_box( $tax_name . 'div', $label, 'post_categories_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ) );
			}
		}

		if ( post_type_supports( $post_type, 'page-attributes' ) ) {
			add_meta_box( 'pageparentdiv', 'page' == $post_type ? __( 'Page Attributes' ) : __( 'Attributes' ), 'page_attributes_meta_box', $post_type, 'side', 'core' );
		}

		if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) ) {
			add_meta_box( 'postimagediv', __('Featured Image'), 'post_thumbnail_meta_box', $post_type, 'side', 'low' );
		}

		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			add_meta_box( 'postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports($post_type, 'trackbacks') ) {
			add_meta_box( 'trackbacksdiv', __('Send Trackbacks'), 'post_trackback_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports($post_type, 'custom-fields') ) {
			add_meta_box( 'postcustom', __('Custom Fields'), 'post_custom_meta_box', $post_type, 'normal', 'core' );
		}

		do_action('dbx_post_advanced');

		if ( post_type_supports($post_type, 'comments') ) {
			add_meta_box( 'commentstatusdiv', __('Discussion'), 'post_comment_status_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports($post_type, 'comments') ) {
			add_meta_box( 'commentsdiv', __('Comments'), 'post_comment_meta_box', $post_type, 'normal', 'core' );
		}

		add_meta_box( 'slugdiv', __('Slug'), 'post_slug_meta_box', $post_type, 'normal', 'core' );

		if ( post_type_supports($post_type, 'author') ) {
			add_meta_box( 'authordiv', __('Author'), 'post_author_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports($post_type, 'revisions') ) {
			add_meta_box( 'revisionsdiv', __('Revisions'), 'post_revisions_meta_box', $post_type, 'normal', 'core' );
		}
	}

	/**
	 * Get all the metaboxes that should always be showed
	 *
	 * @return array All the metaboxes id's in an array
	 *
	 * @since 0.4.0
	 */
	public static function get_default_items( $post_type = '' ) {
		$defaults = array( 'titlediv', 'submitdiv' ); //, 'postdivrich'
		$defaults = apply_filters( 'tabify_default_metaboxes', $defaults, $post_type );

		return apply_filters( 'tabify_default_metaboxes_' . $post_type , $defaults );
	}

}
