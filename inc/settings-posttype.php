<?php
/**
 * Post-type settings.
 *
 * @package Tabify_Edit_Screen
 */

/**
 * Manage tab assignments for post types.
 */
class Tabify_Edit_Screen_Settings_Posttypes extends Tabify_Edit_Screen_Settings_Base {
	/**
	 * Items shown on every tab.
	 *
	 * @var array<string>
	 */
	protected $defaults = array( 'titlediv', 'submitdiv' );

	/**
	 * Loads the base with the type
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		parent::__construct( 'posttypes' );

		add_action( 'tabify_settings', array( $this, 'display_gutenberg_warning' ) );
		add_filter( 'tabify_settings_update', array( $this, 'save_settings' ) );
	}

	/**
	 * Show warning if post type uses gutenberg
	 *
	 * @since 0.4.0
	 *
	 * @param string $section Post-type name.
	 *
	 * @return void
	 */
	public function display_gutenberg_warning( $section ) {
		if ( use_block_editor_for_post_type( $section ) ) {
			echo '<p class="gutenberg-error notice notice-error inline">' . esc_html__( 'This post type support Gutenberg which is not supported.', 'tabify-edit-screen' ) . '</p>';
		}
	}

	/**
	 * Set the items property when needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function load_items() {
		if ( $this->items ) {
			return;
		}

		$posttypes   = $this->get_sections();
		$metaboxes   = $this->get_metaboxes();
		$this->items = apply_filters( 'tabify_metaboxes', $metaboxes, $posttypes );
	}

	/**
	 * Loads all the posttypes as sections
	 *
	 * @return array<string,string> Post-type labels keyed by name.
	 *
	 * @since 0.4.0
	 */
	protected function load_sections() {
		$args = array(
			'show_ui' => true,
		);

		$posttypes_objects = get_post_types( $args, 'objects' );
		$posttypes_objects = apply_filters( 'tabify_posttypes', $posttypes_objects );

		$posttypes = array();
		foreach ( $posttypes_objects as $posttype_object ) {
			if ( is_object( $posttype_object ) && isset( $posttype_object->name, $posttype_object->label ) && is_string( $posttype_object->name ) && is_string( $posttype_object->label ) ) {
				$posttypes[ $posttype_object->name ] = $posttype_object->label;
			}
		}

		return $posttypes;
	}

	/**
	 * Gets all the metaboxes that are registered
	 *
	 * @return array<string,array<string,string>> Meta-box titles keyed by post type and ID.
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
				$metaboxes[ $posttype ]['titlediv'] = __( 'Title', 'default' );
			}

			if ( post_type_supports( $posttype, 'editor' ) ) {
				$metaboxes[ $posttype ]['postdivrich'] = __( 'Editor', 'default' );
			}

			$this->load_default_metaboxes( $posttype );
			do_action( 'add_meta_boxes', $posttype, null );
			do_action( 'add_meta_boxes_' . $posttype, null );

			do_action( 'tabify_add_meta_boxes', $posttype );
		}

		if ( ! is_array( $wp_meta_boxes ) ) {
			return $metaboxes;
		}

		foreach ( $wp_meta_boxes as $posttype => $context ) {
			foreach ( $context as $priorities ) {
				foreach ( $priorities as $priority => $_metaboxes ) {
					foreach ( $_metaboxes as $metabox ) {
						// Metabox has been removed.
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

	/**
	 * Call escape method when settings are being saved
	 *
	 * @param mixed $options list of options to be escaped.
	 *
	 * @return array<array-key,mixed> Sanitized plugin options.
	 *
	 * @since 0.1.0
	 */
	public function save_settings( $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		if ( isset( $options['posttypes'] ) ) {
			$options['posttypes'] = $this->escape( $options['posttypes'] );
		}

		return $options;
	}

	/**
	 * Sanitize the options array to be how we expect it to be
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $posttypes Raw options array.
	 *
	 * @return array<array-key,array<array-key,mixed>> Sanitized options for each post type.
	 */
	private function escape( $posttypes ) {
		if ( ! is_array( $posttypes ) ) {
			return array();
		}

		$posttypes_keys   = array_keys( $posttypes );
		$amount_posttypes = count( $posttypes );

		$kses_allowed_html = array(
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'span'   => array(
				'style' => true,
			),
			'strong' => array(
				'style' => true,
			),
		);
		$kses_allowed_html = apply_filters( 'tabify_posttype_escape_kses', $kses_allowed_html );

		for ( $i = 0; $i < $amount_posttypes; $i++ ) {
			$key = $posttypes_keys[ $i ];

			if ( ! is_array( $posttypes[ $key ] ) ) {
				unset( $posttypes[ $key ] );
				continue;
			}

			$posttypes[ $key ]['show'] = isset( $posttypes[ $key ]['show'] );
			if ( ! isset( $posttypes[ $key ]['tabs'] ) || ! is_array( $posttypes[ $key ]['tabs'] ) ) {
				$posttypes[ $key ]['tabs'] = array();
			}
			$amount_tabs = count( $posttypes[ $key ]['tabs'] );

			for ( $j = 0; $j < $amount_tabs; $j++ ) {
				if ( ! isset( $posttypes[ $key ]['tabs'][ $j ] ) ) {
					continue;
				}

				if ( ! is_array( $posttypes[ $key ]['tabs'][ $j ] ) ) {
					unset( $posttypes[ $key ]['tabs'][ $j ] );
					continue;
				}

				if ( ! isset( $posttypes[ $key ]['tabs'][ $j ]['title'] ) || ! is_string( $posttypes[ $key ]['tabs'][ $j ]['title'] ) ) {
					$posttypes[ $key ]['tabs'][ $j ]['title'] = '';
				}
				$posttypes[ $key ]['tabs'][ $j ]['title'] = wp_kses( $posttypes[ $key ]['tabs'][ $j ]['title'], $kses_allowed_html );

				if ( ! isset( $posttypes[ $key ]['tabs'][ $j ]['items'] ) || ! is_array( $posttypes[ $key ]['tabs'][ $j ]['items'] ) || empty( $posttypes[ $key ]['tabs'][ $j ]['items'] ) ) {
					if ( '' === $posttypes[ $key ]['tabs'][ $j ]['title'] ) {
						unset( $posttypes[ $key ]['tabs'][ $j ] );
					} else {
						$posttypes[ $key ]['tabs'][ $j ]['items'] = array();
					}

					continue;
				}

				$amount_metaboxes = count( $posttypes[ $key ]['tabs'][ $j ]['items'] );

				for ( $k = 0; $k < $amount_metaboxes; $k++ ) {
					// Should the metabox be moved. Only applies when browser doesn't support Javascript.
					if (
						isset( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] ) &&
						(int) $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] !== $j &&
						isset( $posttypes[ $key ]['tabs'][ intval( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] ) ] )
					) {
						$new_tab_key = intval( $posttypes[ $key ]['tabs'][ $j ]['items_tab'][ $k ] );

						$posttypes[ $key ]['tabs'][ $new_tab_key ]['items'][ $k ] = wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] );
						unset( $posttypes[ $key ]['tabs'][ $j ]['items'][ $k ] );
					} else {
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
	 * Found in wp-admin/edit-form-advanced.php.
	 *
	 * @param string $post_type The post type or which meta boxes need to be loaded.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function load_default_metaboxes( $post_type ) {
		if ( 'attachment' === $post_type ) {
			add_meta_box( 'submitdiv', __( 'Save', 'default' ), 'attachment_submit_meta_box', $post_type, 'side', 'core' );
			add_meta_box( 'attachment-id3', __( 'Metadata', 'default' ), 'attachment_id3_data_meta_box', $post_type, 'normal', 'core' );
		} else {
			add_meta_box( 'submitdiv', __( 'Publish', 'default' ), 'post_submit_meta_box', $post_type, 'side', 'core' );
		}

		if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ) {
			add_meta_box( 'formatdiv', _x( 'Format', 'post format', 'default' ), 'post_format_meta_box', $post_type, 'side', 'core' );
		}

		$this->load_taxonomy_metaboxes( $post_type );

		if ( post_type_supports( $post_type, 'page-attributes' ) ) {
			add_meta_box( 'pageparentdiv', 'page' === $post_type ? __( 'Page Attributes', 'default' ) : __( 'Attributes', 'default' ), 'page_attributes_meta_box', $post_type, 'side', 'core' );
		}

		if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) ) {
			add_meta_box( 'postimagediv', __( 'Featured Image', 'default' ), 'post_thumbnail_meta_box', $post_type, 'side', 'low' );
		}

		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			add_meta_box( 'postexcerpt', __( 'Excerpt', 'default' ), 'post_excerpt_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports( $post_type, 'trackbacks' ) ) {
			add_meta_box( 'trackbacksdiv', __( 'Send Trackbacks', 'default' ), 'post_trackback_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports( $post_type, 'custom-fields' ) ) {
			add_meta_box( 'postcustom', __( 'Custom Fields', 'default' ), 'post_custom_meta_box', $post_type, 'normal', 'core' );
		}

		do_action( 'dbx_post_advanced' );

		if ( post_type_supports( $post_type, 'comments' ) ) {
			add_meta_box( 'commentstatusdiv', __( 'Discussion', 'default' ), 'post_comment_status_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports( $post_type, 'comments' ) ) {
			add_meta_box( 'commentsdiv', __( 'Comments', 'default' ), 'post_comment_meta_box', $post_type, 'normal', 'core' );
		}

		add_meta_box( 'slugdiv', __( 'Slug', 'default' ), 'post_slug_meta_box', $post_type, 'normal', 'core' );

		if ( post_type_supports( $post_type, 'author' ) ) {
			add_meta_box( 'authordiv', __( 'Author', 'default' ), 'post_author_meta_box', $post_type, 'normal', 'core' );
		}

		if ( post_type_supports( $post_type, 'revisions' ) ) {
			add_meta_box( 'revisionsdiv', __( 'Revisions', 'default' ), 'post_revisions_meta_box', $post_type, 'normal', 'core' );
		}
	}

	/**
	 * Load taxonomy meta boxes
	 *
	 * @param string $post_type The post type or which meta boxes need to be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_taxonomy_metaboxes( $post_type ) {
		foreach ( get_object_taxonomies( $post_type ) as $tax_name ) {
			$taxonomy = get_taxonomy( $tax_name );

			if ( ! $taxonomy || ! $taxonomy->show_ui ) {
				continue;
			}

			$label = $taxonomy->labels->name;

			if ( ! is_taxonomy_hierarchical( $tax_name ) ) {
				add_meta_box( 'tagsdiv-' . $tax_name, $label, 'post_tags_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ) );
			} else {
				add_meta_box( $tax_name . 'div', $label, 'post_categories_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ) );
			}
		}
	}
}
