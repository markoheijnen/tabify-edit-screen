<?php
/**
 * Tab navigation rendering.
 *
 * @package Tabify_Edit_Screen
 */

/**
 * Render tab navigation and enqueue its assets.
 */
class Tabify_Edit_Screen_Tabs {
	/**
	 * Navigation URL without the tab argument.
	 *
	 * @var string
	 */
	private $base_url = '';

	/**
	 * Query argument identifying the active tab.
	 *
	 * @var string
	 */
	private $get_arg = 'tab';

	/**
	 * Active tab key.
	 *
	 * @var string
	 */
	private $active = '';

	/**
	 * Tab labels and configuration.
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Tab orientation.
	 *
	 * @var string
	 */
	private $type = 'horizontal';

	/**
	 * Whether tab changes use JavaScript.
	 *
	 * @var bool
	 */
	private $javascript_support = true;

	/**
	 * Construct method to set up all internal properties
	 *
	 * @param mixed   $items List of all items.
	 * @param string  $type How to be displayed: vertical of horizontal.
	 * @param string  $get_arg Which get argument should be used.
	 * @param boolean $javascript_support With page refresh or not.
	 *
	 * @since 0.1.0
	 */
	public function __construct( $items, $type = 'horizontal', $get_arg = 'tab', $javascript_support = true ) {
		$this->type               = $type;
		$this->get_arg            = $get_arg;
		$this->javascript_support = (bool) $javascript_support;

		if ( is_array( $items ) ) {
			$this->items = apply_filters( 'tabify_tabs', $items, $this, $type );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This read-only argument selects a tab.
			if ( isset( $_REQUEST[ $this->get_arg ] ) && is_string( $_REQUEST[ $this->get_arg ] ) ) {
				$this->active = sanitize_text_field( (string) wp_unslash( $_REQUEST[ $this->get_arg ] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$request_uri    = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$this->base_url = remove_query_arg( $this->get_arg, $request_uri );

			if ( '' === $this->active || ! isset( $this->items[ $this->active ] ) ) {
				$this->active = (string) key( $this->items );
			}
		}
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
		$class = 'tabify-tabs tab-' . $this->type;

		if ( ! $this->javascript_support ) {
			$class .= ' js-disabled';
		}

		$return = '<div class="' . esc_attr( $class ) . '">';

		if ( 'horizontal' === $this->type ) {
			$return .= '<h2 class="nav-tab-wrapper">';
		} else {
			$return .= '<h2>';
		}

		$return .= $this->get_tabs_current_tab_input();
		$return .= $this->get_tabs();

		$return .= '</h2>';

		$return .= apply_filters( 'tabify_tabs_under', '', $this->type );

		$return .= '</div>';

		// When tabs are requested also enqueue the javascript and css code.
		$required = array( 'jquery' );

		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			$required[] = 'postbox';
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'tabify-edit-screen', plugins_url( '/js/tabs' . $suffix . '.js', __DIR__ ), $required, '1.1.0', false );
		wp_register_style( 'tabify-edit-screen', plugins_url( '/css/tabs' . $suffix . '.css', __DIR__ ), array(), '1.1.0' );

		wp_enqueue_script( 'tabify-edit-screen' );
		wp_enqueue_style( 'tabify-edit-screen' );

		$this->add_active_state_style();

		return $return;
	}

	/**
	 * Update switch background color for active state
	 *
	 * @since 0.9.6
	 */
	private function add_active_state_style() {
		global $_wp_admin_css_colors;

		$color = get_user_option( 'admin_color' );

		if ( empty( $color ) || ! isset( $_wp_admin_css_colors[ $color ] ) ) {
			$color = 'fresh';
		}

		if ( empty( $_wp_admin_css_colors[ $color ]->colors ) ) {
			return;
		}
		$colors     = $_wp_admin_css_colors[ $color ]->colors;
		$background = isset( $colors[3] ) ? $colors[3] : end( $colors );

		wp_add_inline_style(
			'tabify-edit-screen',
			'.switch-checkbox:checked + .switch-label { background: ' . $background . '; }'
		);
	}

	/**
	 * Get the hidden input code for current tab
	 *
	 * @return string The HTML of the hidden input field
	 *
	 * @since 0.2.0
	 */
	public function get_tabs_current_tab_input() {
		return '<input type="hidden" class="current_tab" name="' . esc_attr( $this->get_arg ) . '" value="' . esc_attr( $this->active ) . '" />';
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

		foreach ( $this->items as $key => $args ) {
			if ( ! is_array( $args ) ) {
				$args = array(
					'title' => $args,
				);
			}

			$title   = apply_filters( 'tabify_tabs_tab_title', $args['title'], $args, $this );
			$url     = esc_url( add_query_arg( $this->get_arg, $key, $this->base_url ) );
			$classes = 'tabify-tab nav-tab';

			if ( $this->active === (string) $key ) {
				$classes .= ' nav-tab-active';
			}

			$return .= '<a id="tab-' . esc_attr( $key ) . '" href="' . $url . '" class="' . esc_attr( $classes ) . '">' . wp_kses_post( $title ) . '</a>';
		}

		return $return;
	}
}
