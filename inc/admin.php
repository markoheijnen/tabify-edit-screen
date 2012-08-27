<?php

class Tabify_Edit_Screen_Admin {
	private $metaboxes = array();
	private $tabs;
	private $options;

	/**
	 * Adds a option page to manage all the tabs
	 *
	 * @since 0.1
	 */
	public function admin_menu() {
		add_options_page( __( 'Tabify edit screen', 'tabify-edit-screen' ), __( 'Tabify edit screen', 'tabify-edit-screen' ), 'manage_options', 'tabify-edit-screen', array( &$this, 'edit_screen' ) );
	}

	/**
	 * Option page that handles the form request
	 *
	 * @since 0.1
	 */
	public function edit_screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
		}

		$this->load_plugin_support();

		$this->update_settings();

		wp_register_script( 'tabify-edit-screen-admin', plugins_url( '/js/admin.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui-sortable' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen-admin' );

		$data = array( 'remove' => __( 'Remove', 'tabify-edit-screen' ), 'choose_title' => __( 'Choose title', 'tabify-edit-screen' ) );
		wp_localize_script( 'tabify-edit-screen-admin', 'tabify_l10', $data );

		if( ! wp_script_is( 'jquery-touch-punch', 'registered' ) ) {
			wp_register_script( 'jquery-touch-punch', plugins_url( '/js/jquery.ui.touch-punch.js', dirname( __FILE__ ) ), array( 'jquery-ui-widget', 'jquery-ui-mouse' ), '0.2.2', 1 ); 
		}
		wp_enqueue_script( 'jquery-touch-punch' );
		
		echo '<div class="wrap">';

		screen_icon();
		echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';

		echo '<form id="tabify-form" method="post">';
		wp_nonce_field( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' );

		echo '<input type="hidden" id="tabify_edit_screen_nojs" name="tabify_edit_screen_nojs" value="1" />';

		$posttypes = $this->get_posttypes();
		$this->get_tabs( $posttypes );

		echo '<div id="tabify-settings"><div id="tabifyboxes">';
		$this->get_metaboxes( $posttypes );

		echo '</div>';

		if( apply_filters( 'tabify_support', true ) ) {
			include 'support.php';
			$support = new Tabify_Support( 'tabify-edit-screen' );

			echo '<div id="tabify-support">';
			$support->support_forum();
			echo '</div>';
		}

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Updates settings
	 *
	 * @since 0.2
	 *
	 */
	private function update_settings() {
		if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['tabify'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'tabify_edit_screen_nonce' ) ) {
			$options = $_POST['tabify'];
			$options = $this->escape( $options );

			update_option( 'tabify-edit-screen', $options );
		}
	}

	/**
	 * Sanitize the options array to be how we expect it to be
	 *
	 * @since 0.2
	 *
	 * @param array $posttypes Raw options array
	 * @return array filtered options array
	 */
	private function escape( $posttypes ) {
		$posttypes_keys = array_keys( $posttypes );
		$amount_posttypes = count( $posttypes );
		for( $i = 0; $i < $amount_posttypes; $i++ ) {
			$key = $posttypes_keys[ $i ];

			if( isset( $posttypes[ $key ]['show'] ) && $posttypes[ $key ]['show'] == 1 ) {
				$posttypes[ $key ]['show'] = intval( $posttypes[ $key ]['show'] );
			}
			else {
				$posttypes[ $key ]['show'] = 0;
			}

			$amount_tabs = count( $posttypes[ $key ]['tabs'] );
			for( $j = 0; $j < $amount_tabs; $j++ ) {
				$posttypes[ $key ]['tabs'][ $j ]['title'] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['title'] ) );

				if( !isset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] ) || count( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] ) == 0 ) {
					if( $posttypes[ $key ]['tabs'][ $j ]['title'] == '' ) {
						unset( $posttypes[ $key ]['tabs'][ $j ] );
					}
					continue;
				}

				$amount_metaboxes = count( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] );
				for( $k = 0; $k < $amount_metaboxes; $k++ ) {
					// Should the metabox be moved. Only applies when browser doesn't support Javascript
					if(
						isset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] ) &&
						$posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] != $j &&
						isset( $posttypes[ $key ]['tabs'][ intval( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] ) ] )
					) {
						$new_tab_key = intval( $posttypes[ $key ]['tabs'][ $j ]['metaboxes_tab'][ $k ] );

						if( ! isset( $posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] ) ) {
							$posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] = array();
							$metaboxes_in_new_tab = 0;
						}
						else { 
							$metaboxes_in_new_tab = count( $posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'] );
						}

						$posttypes[ $key ]['tabs'][ $new_tab_key ]['metaboxes'][ $k ] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] ) );
						unset( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] );
					}
					else {
						$posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] = esc_attr( wp_strip_all_tags( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'][ $k ] ) );
					}
				}
				$posttypes[ $key ]['tabs'][ $j ]['metaboxes'] = array_values( $posttypes[ $key ]['tabs'][ $j ]['metaboxes'] );
			}
		}

		return $posttypes;
	}

	/**
	 * Load additional support for plugins that have unique code
	 *
	 * @since 0.4
	 */
	private function load_plugin_support() {
		if( apply_filters( 'tabify_plugin_support', true ) ) {
			include 'plugin-support.php';
			new Tabify_Edit_Screen_Plugin_Support();
		}
	}


	/**
	 * Gets all the post types
	 *
	 * @since 0.1
	 *
	 * @return array All post types that are showed from the backend.
	 */
	private function get_posttypes() {
		$args = array(
			'show_ui' => 'true'
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
	 * Echo the tabs for the settings page
	 *
	 * @since 0.1
	 */
	private function get_tabs( $posttypes ) {
		$this->tabs = new Tabify_Edit_Screen_Tabs( $posttypes );
		echo $this->tabs->get_tabs_with_container();
	}

	/**
	 * Echo all the metaboxes
	 *
	 * @since 0.1
	 */
	private function get_metaboxes( $posttypes ) {
		$metaboxes = $this->_get_metaboxes( $posttypes );
		$this->options = $options = get_option( 'tabify-edit-screen', array() );

		foreach( $posttypes as $posttype => $label ) {
			$default_metaboxes = $this->tabs->get_default_metaboxes( $posttype );

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
				echo '>' . __( 'Remove', 'tabify-edit-screen' ) . '</a>';
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
		echo '<input type="button" id="create_tab" class="button-secondary" value="' . __( 'Create a new tab', 'tabify_edit_screen' ) . '" />';
		submit_button( '', 'primary', 'submit', false );
		echo '</p>';
	}

	/**
	 * Show the metabox for the sortable list
	 *
	 * @since 0.1
	 */
	private function list_show_metabox( $metabox_id, $metabox_title, $tab_id, $posttype, $default_metaboxes ) {
		if( in_array( $metabox_id, $default_metaboxes ) || empty( $metabox_title ) ) {
			echo '<li class="tabifybox-hide">';
		}
		else {
			echo '<li class="menu-item-handle">' . $metabox_title;
		}

		echo '<input type="hidden" name="tabify[' . $posttype . '][tabs][' . $tab_id . '][metaboxes][]" value="' . $metabox_id . '" />';

		echo '<span class="item-order hide-if-js">';
		echo '<select name="tabify[' . $posttype . '][tabs][' . $tab_id . '][metaboxes_tab][]">';
		$amount_tabs = count( $this->options[ $posttype ]['tabs'] );
		for( $i = 0; $i < $amount_tabs; $i++ ) {
			if( $i == $tab_id ) {
				echo '<option value="' . $i . '" selected="selected">' . $this->options[ $posttype ]['tabs'][ $i ]['title'] . '</option>';
			}
			else {
				echo '<option value="' . $i . '">' . $this->options[ $posttype ]['tabs'][ $i ]['title'] . '</option>';
			}
		}
		echo '</select>';
		echo '</span>';
		echo '</li>';
	}

	/**
	 * Gets all the metaboxes that are registered
	 *
	 * @since 0.1
	 */
	private function _get_metaboxes( $posttypes ) {
		if( ! $this->metaboxes ) {
			global $wp_meta_boxes;

			foreach( $posttypes as $posttype => $label ) {
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
		$post_type_object = get_post_type_object( $post_type );
		
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

		if ( post_type_supports($post_type, 'author') ) {
			if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) )
				add_meta_box('authordiv', __('Author'), 'post_author_meta_box', $post_type, 'normal', 'core');
		}

		if ( post_type_supports($post_type, 'revisions') )
			add_meta_box('revisionsdiv', __('Revisions'), 'post_revisions_meta_box', $post_type, 'normal', 'core');
	}
}