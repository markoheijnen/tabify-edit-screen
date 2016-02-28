<?php

include 'tabs.php';

class Tabify_Edit_Screen_Edit_Screen {

	private $editscreen_tabs;
	private $tab_location = 'default';

	public function __construct() {
		add_filter( 'redirect_post_location', array( $this, 'redirect_add_current_tab' ), 10, 2 );
		add_action( 'admin_head', array( $this, 'show_tabs' ), 100 );
	}

	/**
	 * When a post is saved let it return to the current selected tab.
	 *
	 * @param string $location The location the user will be sent to
	 * @param int $post_id The post id
	 * @return string $location The new location the user will be sent to
	 *
	 * @since 0.2.0
	 *
	 */
	public function redirect_add_current_tab( $location, $post_id ) {
		if ( isset( $_REQUEST['tab'] ) ) {
			$location = esc_url_raw( add_query_arg( 'tab', $_REQUEST['tab'], $location ) );
		}

		return $location;
	}

	/**
	 * Show the tabs on the edit screens.
	 * This will load the tab class, tab options and actions
	 * It will also will add the required classes to all the metaboxes
	 *
	 * @since 0.1.0
	 *
	 */
	public function show_tabs() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		if ( ! $screen || 'post' != $screen->base ) {
			return;
		}

		$this->tab_location = apply_filters( 'tabify_tab_location', $this->tab_location, 'posttype' );

		$post_type = $screen->post_type;
		$options   = get_option( 'tabify-edit-screen', array() );

		// Backwards compatibily
		if ( isset( $options['posttypes'] ) ) {
			$options = $options['posttypes'];
		}

		if ( ! isset( $options[ $post_type ] ) ) {
			return;
		}

		// Ability to change if the tabs should be showed or not.
		$display_tabs = apply_filters( 'tabify_tab_posttype_show', (bool) $options[ $post_type ]['show'] );

		// This posttype has tabs
		if ( ! $display_tabs ) {
			return;
		}

		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'generate_javascript' ), 9 );

		$tabs = apply_filters( 'tabify_tab_posttype_tabs', $options[ $post_type ]['tabs'], $post_type );

		$this->editscreen_tabs = new Tabify_Edit_Screen_Tabs( $tabs );
		$default_metaboxes     = Tabify_Edit_Screen_Settings_Posttypes::get_default_items( $post_type );
		$all_metaboxes         = array();

		foreach ( $wp_meta_boxes[ $post_type ] as $priorities ) {
			foreach ( $priorities as $priority => $_metaboxes ) {
				foreach ( $_metaboxes as $metabox ) {
					if ( ! in_array( $metabox['id'], $default_metaboxes ) ) {
						$all_metaboxes[ $metabox['id'] ] = $metabox['title'];
					}
				}
			}
		}

		$this->load_tabs();

		foreach ( $options[ $post_type ]['tabs'] as $tab_index => $tab ) {
			$class = 'tabifybox tabifybox-' . $tab_index;

			if ( $this->editscreen_tabs->get_current_tab() != $tab_index ) {
				$class .= ' tabifybox-hide';
			}

			// Backwards compatibily from 0.5 to 0.6
			if ( ! isset( $tab['items'] ) && isset( $tab['metaboxes'] ) ) {
				$tab['items'] = $tab['metaboxes'];
			}

			if ( isset( $tab['items'] ) ) {
				foreach ( $tab['items'] as $metabox_id_fallback => $metabox_id ) {
					if ( intval( $metabox_id_fallback ) == 0 && $metabox_id_fallback !== 0 ) {
						$metabox_id = $metabox_id_fallback;
					}

					if ( ! in_array( $metabox_id, $default_metaboxes ) ) {
						if ( $metabox_id == 'titlediv' || $metabox_id == 'postdivrich' ) {
							$func = create_function('', 'echo "jQuery(\"#' . $metabox_id . '\").addClass(\"' . $class . '\");";');
							add_action( 'tabify_custom_javascript' , $func );
						}
						else {
							$func = create_function( '$args', 'array_push( $args, "' . $class . '" ); return $args;' );
							add_action( 'postbox_classes_' . $post_type . '_' . $metabox_id, $func );

							if ( isset( $all_metaboxes[ $metabox_id ] ) ) {
								unset( $all_metaboxes[ $metabox_id ] );
							}
						}
					}
				}
			}
		}

		$show = apply_filters( 'tabify_unattached_metaboxes_show', true, $post_type );
		do_action( 'tabify_unattached_metaboxes', $all_metaboxes, $show );

		// Metaboxes that aren't attachted
		if ( $show ) {
			foreach ( $all_metaboxes as $metabox_id => $metabox_title ) {
				$last_index                 = $tab_index;
				$unattached_metaboxes_index = apply_filters( 'tabify_unattached_metaboxes_index', $last_index, $post_type );

				if ( $unattached_metaboxes_index < 0 || $unattached_metaboxes_index > $last_index ) {
					$unattached_metaboxes_index = $last_index;
				}

				$class = 'tabifybox tabifybox-' . $unattached_metaboxes_index;

				if ( $this->editscreen_tabs->get_current_tab() != $unattached_metaboxes_index ) {
					$class .= ' tabifybox-hide';
				}

				$func = create_function( '$args', 'array_push( $args, "' . $class . '" ); return $args;' );
				add_action( 'postbox_classes_' . $post_type . '_' . $metabox_id, $func );
			}
		}
	}

	public function add_admin_body_class( $body ) {
		if ( $this->tab_location ) {
			$body .= ' tabify_tab' . $this->tab_location;
		}

		return $body;
	}

	/**
	 * Check where tabs should be loaded and fire the right action and callback for it
	 *
	 * @since 0.5.0
	 *
	 */
	private function load_tabs() {
		if ( 'after_title' == $this->tab_location ) {
			add_action( 'edit_form_after_title', array( $this, 'output_tabs' ), 9 );
		}
		else { //default
			$tabs  = $this->submit_button();
			$tabs .= $this->editscreen_tabs->get_tabs_with_container();
			$func  = create_function('', 'echo "$(\'#post\').prepend(\'' . addslashes( $tabs ) . '\');";');

			add_action( 'tabify_custom_javascript' , $func );
		}
	}

	/**
	 * Outputs the tabs
	 *
	 * @since 0.5.0
	 *
	 */
	public function output_tabs() {
		echo $this->submit_button();
		echo $this->editscreen_tabs->get_tabs_with_container();
	}

	/**
	 * Add submit button when the submitbox isn't showed on every tab
	 *
	 * @since 0.7.0
	 *
	 */
	private function submit_button() {
		$post = get_post();

		$default = Tabify_Edit_Screen_Settings_Posttypes::get_default_items( $post->post_type );

		if ( in_array( 'submitdiv', $default ) ) {
			return;
		}
		
		$text = '';
		$post_type_object = get_post_type_object( $post->post_type );
		$can_publish      = current_user_can( $post_type_object->cap->publish_posts );


		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) {
			if ( $can_publish ) {
				if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) {
					$text = __( 'Schedule' );
				}
				else {
					$text = __( 'Publish' );
				}
			}
			else {
				$text = __( 'Submit for Review' );
			}
		}
		else {
			$text = __('Update');
		}

		return get_submit_button( $text, 'secondary', 'second-submit', false );
	}

	/**
	 * Generate the javascript for the edit screen
	 *
	 * @since 0.1.0
	 *
	 */
	public function generate_javascript() {
		echo '<script type="text/javascript">';
		echo 'jQuery(function($) {';
		do_action( 'tabify_custom_javascript' );
		echo '});';
		echo '</script>';
	}

}
