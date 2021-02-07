<?php

abstract class Tabify_Edit_Screen_Settings_Base {
	private $type;
	private $sections;
	private $base_url;
	private $tabs;

	private $options;

	protected $items    = array();
	protected $defaults = array();

	/**
	 * Set properties and load the sections and tabs
	 *
	 * @param string $type Where the settings are for
	 *
	 * @since 0.4.0
	 */
	public function __construct( $type ) {
		$this->type     = $type;
		$this->sections = $this->load_sections();
		$this->base_url = remove_query_arg( array( 'type', 'section' ), $_SERVER["REQUEST_URI"] );

		$this->tabs     = new Tabify_Edit_Screen_Tabs( $this->sections, 'vertical', 'subtab' );
	}

	/**
	 * Set the items property when needed.
	 *
	 * @since 1.0.0
	 */
	abstract protected function load_items();

	/**
	 * Set properties and load the sections and tabs
	 *
	 * @return array The sections
	 *
	 * @since 0.4.0
	 */
	abstract protected function load_sections();

	/**
	 * Get sections
	 *
	 * @return array The sections
	 *
	 * @since 0.4.0
	 */
	protected function get_sections() {
		return $this->sections;
	}

	/**
	 * Get the sections as a tab menu
	 *
	 * @since 0.4.0
	 */
	public function get_sections_menu() {
		echo $this->tabs->get_tabs_with_container();
	}

	/**
	 * Get all the metaboxes that should always be showed
	 *
	 * @return array All the metaboxes id's in an array
	 *
	 * @since 0.4.0
	 */
	public function get_default_items( $id ) {
		$defaults = apply_filters( 'tabify_default_metaboxes', $this->defaults, $id, $this->type );
		$defaults = apply_filters( 'tabify_default_metaboxes_' . $this->type, $defaults, $id );

		return $defaults;
	}

	/**
	 * Echo all the items
	 *
	 * @since 0.4.0
	 */
	public function get_sections_box() {
		$sections = $this->get_sections();

		foreach ( $sections as $section => $label ) {
			$this->get_section_box( $section );
		}

		$this->print_buttons();

		$this->print_backbone_template();
	}

	/**
	 * Get the HTML for one pacticular section
	 *
	 * @param string $section The section name
	 *
	 * @since 1.0.0
	 */
	private function get_section_box( $section ) {
		$options       = $this->get_options( $this->type );
		$default_items = $this->get_default_items( $section );

		if ( ! isset( $options[ $section ] ) ) {
			$options[ $section ] = array (
				'tabs' => array(
					array(
						'title' => __( 'Others', 'tabify-edit-screen' ),
						'items' => array()
					)
				)
			);
		}

		if ( $section == $this->tabs->get_current_tab() ) {
			echo '<div class="tabifybox tabifybox-' . $section . '">';
		}
		else {
			echo '<div class="tabifybox tabifybox-hide tabifybox-' . $section . '" style="display: none;">';
		}

		$this->get_section_settings( $section );

		echo '<div class="tabify_control tabify_control_tabs">';

		$tab_id = 0;
		$remove = false;

		if ( 1 < count( $options[ $section ]['tabs'] ) ) {
			$remove = true;
		}

		foreach ( $options[ $section ]['tabs'] as $tab ) {
			$tab['id'] = $tab_id;

			if ( ! isset( $tab['permissions'] ) ) {
				$tab['permissions'] = array();
			}

			if ( $tab['title'] == '' ) {
				$tab['title'] = __( 'Choose title', 'tabify-edit-screen' );
			}

			echo '<div class="menu-item-handle tabify_tab">';

			$this->get_section_tab_title( $section, $tab['title'], $tab, $remove );
			$this->get_section_box_list( $section, $tab['items'], $tab_id, $default_items );

			echo '</div>';

			$tab_id++;
		}


		echo '</div>';

		echo '</div>';
	}

	/**
	 * Get the settings HTML for one pacticular section
	 *
	 * @param string $section The section name
	 *
	 * @since 1.0.0
	 */
	private function get_section_settings( $section ) {
		$options = $this->get_options( $this->type );

		$checked = '';
		if ( isset( $options[ $section ]['show'] ) && $options[ $section ]['show'] == 1 ) {
			$checked = ' checked="checked"';
		}

		echo '<div class="tabifybox-options">';
		echo '<p id="show-type">';
		_e( 'Show tabs in this post type:', 'tabify-edit-screen' );
		echo ' <span class="switch">';
		echo '<input type="checkbox" name="tabify[' . $this->type . '][' . $section . '][show]" value="1" class="switch-checkbox" id="switch-' . $this->type . '-' . $section . '"' . $checked . '>';
		echo '<label data-tg-off="' .  __( 'Off', 'tabify-edit-screen' ) . '" data-tg-on="' .  __( 'On', 'tabify-edit-screen' ) . '" for="switch-' . $this->type . '-' . $section . '" class="switch-label"></label>';
		echo '</span>';
		echo '</p>';

		do_action( 'tabify_settings', $section, $this->type );
		echo '</div>';
	}

	/**
	 * Get the html for one pacticular section
	 *
	 * @param string $section The section name
	 * @param string $title The title
	 * @param array $tab Tab information
	 * @param boolean $remove
	 *
	 * @since 0.4.0
	 */
	private function get_section_tab_title( $section, $title, $tab, $remove ) {
		echo '<h2><span class="hide-if-no-js">' . $title . '</span><input type="text" name="tabify[' . $this->type . '][' . $section . '][tabs][' . $tab['id'] . '][title]" value="' . esc_html( $title ) . '" class="hide-if-js" /></h2>';

		echo '<div class="tabify-title-box">';
			do_action( 'tabify_settings_tab_title_box', $tab, $section, $this->type );

			echo '<a href="#" class="tabify-remove-tab hide-if-no-js"';
			if ( ! $remove ) {
				echo ' style="display: none;"';
			}
			echo '>' . __( 'Remove', 'tabify-edit-screen' ) . '</a>';
		echo '</div>';

		echo '<div class="clear"></div>';
		do_action( 'tabify_settings_tab_title_after', $tab, $section, $this->type );
	}

	/**
	 * Get the html for the section sortable list
	 *
	 * @param string $section The section name
	 * @param array $items List of all items in the section
	 * @param integer $tab_id The id of the tab the list is in
	 * @param array $default_items List of items that are always shown
	 *
	 * @since 1.0.0
	 */
	private function get_section_box_list( $section, $items, $tab_id, $default_items ) {
		$this->load_items();
		$options = $this->get_options( $this->type );

		echo '<ul>';
		if ( isset( $items ) ) {
			foreach ( $items as  $item_id ) {
				if ( empty( $item_id ) ) {
					continue;
				}

				$item_title = '';
				if ( isset( $this->items[ $section ][ $item_id ] ) ) {
					$item_title = $this->items[ $section ][ $item_id ];

					$item_title = apply_filters( 'tabify_items_title', $item_title, $item_id );
					$item_title = apply_filters( 'tabify_items_title_' . $item_id , $item_title );
				}

				$this->list_show_items( $item_id, $item_title, $tab_id, $section, $default_items );

				unset( $this->items[ $section ][ $item_id ] );
			}
		}

		if ( ! isset( $options[ $section ] )|| count( $options[ $section ]['tabs'] ) == ( $tab_id + 1 ) ) {
			foreach ( $this->items[ $section ] as $item_id => $item_title ) {
				if ( empty( $item_id ) ) {
					continue;
				}

				$item_title = apply_filters( 'tabify_items_title', $item_title, $item_id );
				$item_title = apply_filters( 'tabify_items_title_' . $item_id , $item_title );

				$this->list_show_items( $item_id, $item_title, $tab_id, $section, $default_items );
			}
		}

		echo '</ul>';
	}

	/**
	 * Show the items for the sortable list
	 *
	 * @param integer $item_id The id of the item in the list
	 * @param string $item_title The title of the item
	 * @param integer $tab_id The id of the tab the list is in
	 * @param string $section The section name
	 * @param array $default_items List of items that are always shown
	 *
	 * @since 0.4.0
	 */
	protected function list_show_items( $item_id, $item_title, $tab_id, $section, $default_items ) {
		$options = $this->get_options( $this->type );

		// Most likely a meta box that doesn't exist anymore
		if ( empty( $item_title ) ) {
			return;
		}

		$item_title = strip_tags( $item_title );

		if ( in_array( $item_id, $default_items ) ) {
			echo '<li id="' . $section . '-' . $item_id . '" class="tabifybox-hide">';
		}
		else {
			echo '<li id="' . $section . '-' . $item_id . '">';
		}

		echo '<div class="menu-item-bar"><div class="menu-item-handle">';
		echo '<span class="item-title">' . $item_title . '</span>';

		echo '<input type="hidden" name="tabify[' . $this->type . '][' . $section . '][tabs][' . $tab_id . '][items][]" value="' . $item_id . '" />';

		echo '<span class="item-order hide-if-js">';
		echo '<select name="tabify[' . $this->type . '][' . $section . '][tabs][' . $tab_id . '][items_tab][]">';

		if ( isset( $options[ $section ] ) ) {
			$amount_tabs = count( $options[ $section ]['tabs'] );

			for( $i = 0; $i < $amount_tabs; $i++ ) {
				if ( ! isset( $options[ $section ]['tabs'][ $i ] ) ) {
					continue;
				}

				if ( $i == $tab_id ) {
					echo '<option value="' . $i . '" selected="selected">' . esc_html( $options[ $section ]['tabs'][ $i ]['title'] ) . '</option>';
				}
				else {
					echo '<option value="' . $i . '">' . esc_html( $options[ $section ]['tabs'][ $i ]['title'] ) . '</option>';
				}
			}
		}

		echo '</select>';
		echo '</span>';
		echo '</div></div></li>';
	}


	/**
	 * Display general buttons.
	 *
	 * @since 1.0.0
	 */
	private function print_buttons() {
		echo '<p class="submit">';
		echo '<input type="submit" id="create_tab" name="create_tab" class="button button-secondary" value="' . __( 'Create a new tab', 'tabify-edit-screen' ) . '" />';
		submit_button( '', 'primary', 'submit', false );
		echo '</p>';
	}


	/**
	 * New tabify tab template
	 *
	 * @since 0.4.0
	 */
	private function print_backbone_template() {
		$tab = array(
			'id'          => '{{ data.tab_id }}',
			'permissions' => array()
		);

		echo '<script type="text/template" id="tmpl-new-tab">';
		echo '<div class="menu-item-handle tabify_tab">';
			$this->get_section_tab_title( '{{ data.section }}', __( 'Choose title', 'tabify-edit-screen' ), $tab, true );
			echo '<ul></ul>';
		echo '</div>';

		echo '</script>';
	}

	/**
	 * Get the options of the current type
	 *
	 * @since 0.4.0
	 */
	protected function get_options( $type = null ) {
		if ( ! $this->options ) {
			$this->options = get_option( 'tabify-edit-screen', array() );
		}

		if ( $type && isset( $this->options[ $type ] ) ) {
			return $this->options[ $type ];
		}

		return $this->options;
	}

}
