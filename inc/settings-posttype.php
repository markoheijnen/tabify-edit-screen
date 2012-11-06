<?php

class Tabify_Edit_Screen_Settings_Posttypes extends Tabify_Edit_Screen_Settings_Base {
	private $metaboxes = array();

	/**
	 * Loads the base with the type
	 *
	 * @since 0.4
	 */
	function __construct() {
		parent::__construct('posttypes');
	}

	/**
	 * Loads all the posttypes as sections
	 *
	 * @since 0.4
	 */
	protected function load_sections() {
		$args = array(
			'show_ui' => true
		);

		$posttypes_objects = get_post_types( $args, 'objects' );
		$posttypes_objects = apply_filters( 'tabify_posttypes', $posttypes_objects );

		$posttypes = array();
		foreach( $posttypes_objects as $posttype_object ) {
			if( is_object( $posttype_object ) ) {
				$posttypes[ $posttype_object->name ] = $posttype_object->label;
			}
		}

		return $posttypes;
	}

	/**
	 * Echo all the metaboxes
	 *
	 * @since 0.1
	 */
	public function get_section() {
		$posttypes = $this->get_sections();
		$metaboxes = $this->get_metaboxes();
		$this->metaboxes = apply_filters( 'tabify_metaboxes', $this->metaboxes, $posttypes );

		$this->options = $options = get_option( 'tabify-edit-screen', array() );

		foreach( $posttypes as $posttype => $label ) {
			$default_metaboxes = $this->get_default_metaboxes( $posttype );

			if( !isset( $options[ $posttype ] ) ) {
				$options[ $posttype ] = array (
					'tabs' => array(
						array( 'title' => __( 'Others' ), 'metaboxes' => array() )
					)
				);
			}

			if( $posttype == $this->tabs->get_current_tab() ) {
				echo '<div class="tabifybox tabifybox-' . $posttype . '">';
			}
			else {
				echo '<div class="tabifybox tabifybox-hide tabifybox-' . $posttype . '" style="display: none;">';
			}

			$checked = '';
			if( isset( $options[ $posttype ]['show'] ) && $options[ $posttype ]['show'] == 1 ) {
				$checked = ' checked="checked"';
			}

			echo '<div class="tabifybox-options">';
			echo '<p><input type="checkbox" name="tabify[' . $posttype . '][show]" value="1" ' . $checked . '/> ' . __( 'Show tabs in this post type.', 'tabify-edit-screen' ) . '</p>';
			do_action( 'tabify-settings-posttype', $posttype, $options[ $posttype ] );
			echo '</div>';

			echo '<div class="tabify_control">';

			$tab_id = 0;
			foreach( $options[ $posttype ]['tabs'] as $tab ) {
				echo '<div class="menu-item-handle tabify_tab">';

				if( $tab['title'] == '' ) {
					$tab['title'] = __( 'Choose title' );
				}
				echo '<h2><span class="hide-if-no-js">' . $tab['title'] . '</span><input type="text" name="tabify[' . $posttype . '][tabs][' . $tab_id . '][title]" value="' . $tab['title'] . '" class="hide-if-js" /></h2>';

				echo '<a href="#" class="tabify-remove-tab hide-if-no-js"';
				if( isset( $tab['metaboxes'] ) && count( $tab['metaboxes'] ) > 0 ) { echo ' style="display: none;"'; }
				echo '>' . __( 'Remove' ) . '</a>';
				echo '<div class="clear"></div>';

				echo '<ul>';
				if( isset( $tab['metaboxes'] ) ) {
					foreach( $tab['metaboxes'] as $metabox_id_fallback => $metabox_id ) {
						if( intval( $metabox_id_fallback ) == 0 && $metabox_id_fallback !== 0 ) {
							$metabox_id = $metabox_id_fallback;
						}

						if( empty( $metabox_id ) ) {
							continue;
						}

						$metabox_title = '';
						if( isset( $metaboxes[ $posttype ][ $metabox_id ] ) ) {
							$metabox_title = $metaboxes[ $posttype ][ $metabox_id ];

							$metabox_title = apply_filters( 'tabify_metaboxes_title' , $metabox_title, $metabox_id );
							$metabox_title = apply_filters( 'tabify_metaboxes_title_' . $metabox_id , $metabox_title );
						}

						$this->list_show_metabox( $metabox_id, $metabox_title, $tab_id, $posttype, $default_metaboxes );

						unset( $metaboxes[ $posttype ][ $metabox_id ] );
					}
				}

				if ( count( $options[ $posttype ]['tabs'] ) == ( $tab_id + 1 ) ) {
					foreach(  $metaboxes[ $posttype ] as $metabox_id => $metabox_title ) {
						if( empty( $metabox_id ) ) {
							continue;
						}

						$metabox_title = apply_filters( 'tabify_metaboxes_title' , $metabox_title, $metabox_id );
						$metabox_title = apply_filters( 'tabify_metaboxes_title_' . $metabox_id , $metabox_title );

						$this->list_show_metabox( $metabox_id, $metabox_title, $tab_id, $posttype, $default_metaboxes );
					}
				}

				echo '</ul>';
				echo '</div>';

				$tab_id++;
			}


			echo '</div>';

			echo '</div>';
		}

		echo '<p class="submit">';
		echo '<input type="button" id="create_tab" class="button-secondary" value="' . __( 'Create a new tab', 'tabify-edit-screen' ) . '" />';
		submit_button( '', 'primary', 'submit', false );
		echo '</p>';
	}

	/**
	 * Gets all the metaboxes that are registered
	 *
	 * @since 0.1
	 */
	private function get_metaboxes() {
		$sections = $this->get_sections();

		if( ! $this->metaboxes ) {
			global $wp_meta_boxes;

			foreach( $sections as $posttype => $label ) {
				$this->metaboxes[ $posttype ] = array();

				if ( post_type_supports( $posttype, 'title' ) ) {
					$this->metaboxes[ $posttype ][ 'titlediv'] = __( 'Title' );
				}

				if ( post_type_supports( $posttype, 'editor' ) ) {
					$this->metaboxes[ $posttype ][ 'postdivrich'] = __( 'Editor' );
				}

				do_action( 'tabify_add_meta_boxes', $posttype );

				$this->load_default_metaboxes( $posttype );
				do_action( 'add_meta_boxes', $posttype, null );
				do_action( 'add_meta_boxes_' . $posttype, null );
			}

			foreach( $wp_meta_boxes as $posttype => $context ) {
				foreach( $context as $priorities ) {
					foreach( $priorities as $priority => $metaboxes ) {
						foreach( $metaboxes as $metabox ) {
							$this->metaboxes[ $posttype ][ $metabox['id'] ] = $metabox['title'];
						}
					}
				}
			}
		}

		return $this->metaboxes;
	}

	/**
	 * Gets all the default WordPress metaboxes
	 * Little bit hackish but it works. Hopefully one day there will be a method for this in core.
	 *
	 * @since 0.1
	 */
	private function load_default_metaboxes( $post_type ) {
		add_meta_box( 'submitdiv', __('Publish'), 'post_submit_meta_box', $post_type, 'side', 'core' );

		if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) )
			add_meta_box( 'formatdiv', _x( 'Format', 'post format' ), 'post_format_meta_box', $post_type, 'side', 'core' );

		// all taxonomies
		foreach ( get_object_taxonomies($post_type) as $tax_name ) {
			$taxonomy = get_taxonomy($tax_name);
			if ( ! $taxonomy->show_ui )
				continue;

			$label = $taxonomy->labels->name;

			if ( !is_taxonomy_hierarchical($tax_name) )
				add_meta_box('tagsdiv-' . $tax_name, $label, 'post_tags_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ));
			else
				add_meta_box($tax_name . 'div', $label, 'post_categories_meta_box', $post_type, 'side', 'core', array( 'taxonomy' => $tax_name ));
		}

		if ( post_type_supports($post_type, 'page-attributes') )
			add_meta_box('pageparentdiv', 'page' == $post_type ? __('Page Attributes') : __('Attributes'), 'page_attributes_meta_box', $post_type, 'side', 'core');

		if ( current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' ) )
				add_meta_box('postimagediv', __('Featured Image'), 'post_thumbnail_meta_box', $post_type, 'side', 'low');

		if ( post_type_supports($post_type, 'excerpt') )
			add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', $post_type, 'normal', 'core');

		if ( post_type_supports($post_type, 'trackbacks') )
			add_meta_box('trackbacksdiv', __('Send Trackbacks'), 'post_trackback_meta_box', $post_type, 'normal', 'core');

		if ( post_type_supports($post_type, 'custom-fields') )
			add_meta_box('postcustom', __('Custom Fields'), 'post_custom_meta_box', $post_type, 'normal', 'core');

		do_action('dbx_post_advanced');
		if ( post_type_supports($post_type, 'comments') )
			add_meta_box('commentstatusdiv', __('Discussion'), 'post_comment_status_meta_box', $post_type, 'normal', 'core');

		if ( post_type_supports($post_type, 'comments') )
			add_meta_box('commentsdiv', __('Comments'), 'post_comment_meta_box', $post_type, 'normal', 'core');

		add_meta_box('slugdiv', __('Slug'), 'post_slug_meta_box', $post_type, 'normal', 'core');

		if ( post_type_supports($post_type, 'author') )
			add_meta_box('authordiv', __('Author'), 'post_author_meta_box', $post_type, 'normal', 'core');

		if ( post_type_supports($post_type, 'revisions') )
			add_meta_box('revisionsdiv', __('Revisions'), 'post_revisions_meta_box', $post_type, 'normal', 'core');
	}

	/**
	 * Get all the metaboxes that should always be showed
	 *
	 * @return array All the metaboxes id's in an array
	 *
	 * @since 0.4
	 */
	public function get_default_metaboxes( $post_type = '' ) {
		$defaults = array( 'titlediv', 'submitdiv' ); //, 'postdivrich'
		$defaults = apply_filters( 'tabify_default_metaboxes', $defaults, $post_type );
		return apply_filters( 'tabify_default_metaboxes_' . $post_type , $defaults );
	}
}