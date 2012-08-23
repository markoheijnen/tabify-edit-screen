<?php

class Tabify_Edit_Screen_Tabs {
	private $base_url = '';

	private $active = '';
	private $items = array();

	/**
	 * construct method
	 *
	 * @param array $items All the tab items
	 *
	 * @since 0.1
	 */
	function __construct( $items ) {
		if( is_array( $items ) ) {
			do_action( 'tabify_tabs', $this );
			$this->items = apply_filters( 'tabify_tabs', $items, $this );

			if( isset( $_REQUEST['tab'] ) ) {
				$this->active = esc_attr( $_REQUEST['tab'] );
			}

			$this->base_url = remove_query_arg( 'tab', $_SERVER["REQUEST_URI"] );

			if( empty( $this->active ) || !isset( $items[ $this->active ] ) ) {
				$this->active = key( $items );
			}

			return true;
		}
		return false;
	}

	/**
	 * Get the current tab
	 *
	 * @return string Current tab name
	 *
	 * @since 0.1
	 */
	public function get_current_tab() {
		return $this->active;
	}

	/**
	 * Get the HTML code of the tabs container including the tabs
	 *
	 * @param boolean $show_current_tab_input If you want to have the the hidden input field for current tab
	 * @return string The HTML of the tabs
	 *
	 * @since 0.1
	 */
	public function get_tabs_with_container( $show_current_tab_input = true ) {
		$return  = '<h2 class="nav-tab-wrapper" style="padding-left: 20px;">';

		if( $show_current_tab_input == true ) {
			$return .= $this->get_tabs_current_tab_input();
		}

		$return .= $this->get_tabs();
		$return .=  '</h2>';

		//When tabs are requested also enqueue the javascript and css code
		wp_register_script( 'tabify-edit-screen', plugins_url( '/js/tabs.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'tabify-edit-screen' );

		wp_register_style( 'tabify-edit-screen', plugins_url( '/css/tabs.css', dirname( __FILE__ ) ), array( ), '1.0' );
		wp_enqueue_style( 'tabify-edit-screen' );

		return $return;
	}

	/**
	 * Get the hidden input code for current tab
	 *
	 * @return string The HTML of the hidden input field
	 *
	 * @since 0.2
	 */
	public function get_tabs_current_tab_input() {
		return '<input type="hidden" id="current_tab" name="tab" value="' . $this->active. '" />';
	}

	/**
	 * Get the HTML code of all the tabs
	 *
	 * @return string The HTML of the hidden input field
	 *
	 * @since 0.1
	 */
	private function get_tabs() {
		$return = '';

		foreach( $this->items as $key => $title ) {
			if( is_array( $title ) ) {
				$title = $title['title'];
			}

			if( $this->active == $key ) {
				$return .= '<a id="tab-' . $key . '" href="' . $this->base_url . '&tab=' . $key . '" class="tabify-tab nav-tab nav-tab-active">' . $title . '</a>';
			}
			else {
				$return .= '<a id="tab-' . $key . '" href="' . $this->base_url . '&tab=' . $key . '" class="tabify-tab nav-tab">' . $title . '</a>';
			}
		}

		return $return;
	}

	/**
	 * Get all the metaboxes that should always be showed
	 *
	 * @return array All the metaboxes id's in an array
	 *
	 * @since 0.1
	 */
	public function get_default_metaboxes( $post_type = '' ) {
		$defaults = array( 'titlediv', 'submitdiv' ); //, 'postdivrich'
		$defaults = apply_filters( 'tabify_default_metaboxes', $defaults, $post_type );
		return apply_filters( 'tabify_default_metaboxes_' . $post_type , $defaults );
	}
}