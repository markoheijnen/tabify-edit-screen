<?php

class Tabify_Edit_Screen_Tabs {
	private $base_url = '';
	private $get_arg = 'tab';

	private $active = '';

	private $items  = array();
	private $type;
	private $javascript_support;

	/**
	 * construct method
	 *
	 * @param array $items All the tab items
	 *
	 * @since 0.1.0
	 */
	public function __construct( $items, $type = 'horizontal', $get_arg = 'tab', $javascript_support = true ) {
		if ( is_array( $items ) ) {
			do_action( 'tabify_tabs', $this, $type );

			$this->items              = apply_filters( 'tabify_tabs', $items, $this );
			$this->type               = $type;
			$this->get_arg            = $get_arg;
			$this->javascript_support = $javascript_support;

			if ( isset( $_REQUEST[ $this->get_arg ] ) ) {
				$this->active = esc_attr( $_REQUEST[ $this->get_arg ] );
			}

			$this->base_url = remove_query_arg( $this->get_arg, $_SERVER["REQUEST_URI"] );

			if ( empty( $this->active ) || ! isset( $items[ $this->active ] ) ) {
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
	 * @since 0.1.0
	 */
	public function get_current_tab() {
		return $this->active;
	}

	/**
	 * Get the HTML code of the tabs container including the tabs
	 *
	 * @return string The HTML of the tabs
	 *
	 * @since 0.1.0
	 */
	public function get_tabs_with_container() {
		$class = 'tabify-tabs tab-' .  $this->type;

		if ( ! $this->javascript_support ) {
			$class .= ' js-disabled';
		}

		$return  = '<div class="' . $class . '">';


		if ( 'horizontal' == $this->type ) {
			$return .= '<h2 class="nav-tab-wrapper">';
		}
		else {
			$return .= '<h2>';
		}

		$return .= $this->get_tabs_current_tab_input();
		$return .= $this->get_tabs();

		$return .= '</h2>';

		$return .= apply_filters( 'tabify_tabs_under', '', $this->type );

		$return .= '</div>';

		//When tabs are requested also enqueue the javascript and css code
		$required = array( 'jquery' );

		if ( 'post' == get_current_screen()->base ) {
			$required[] = 'postbox';
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'tabify-edit-screen', plugins_url( '/js/tabs' . $suffix . '.js', dirname( __FILE__ ) ), $required, '1.0' );
		wp_register_style( 'tabify-edit-screen', plugins_url( '/css/tabs' . $suffix . '.css', dirname( __FILE__ ) ), array(), '1.0' );

		wp_enqueue_script( 'tabify-edit-screen' );
		wp_enqueue_style( 'tabify-edit-screen' );

		return $return;
	}

	/**
	 * Get the hidden input code for current tab
	 *
	 * @return string The HTML of the hidden input field
	 *
	 * @since 0.2.0
	 */
	public function get_tabs_current_tab_input() {
		return '<input type="hidden" class="current_tab" name="' . $this->get_arg . '" value="' . $this->active . '" />';
	}

	/**
	 * Get the HTML code of all the tabs
	 *
	 * @return string The HTML of the hidden input field
	 *
	 * @since 0.1.0
	 */
	private function get_tabs() {
		$return = '';

		foreach ( $this->items as $key => $title ) {
			if ( is_array( $title ) ) {
				$title = $title['title'];
			}

			if ( $this->active == $key ) {
				$return .= '<a id="tab-' . $key . '" href="' . $this->base_url . '&' . $this->get_arg . '=' . $key . '" class="tabify-tab nav-tab nav-tab-active">' . $title . '</a>';
			}
			else {
				$return .= '<a id="tab-' . $key . '" href="' . $this->base_url . '&' . $this->get_arg . '=' . $key . '" class="tabify-tab nav-tab">' . $title . '</a>';
			}
		}

		return $return;
	}

}
