<?php

class Tabify_Edit_Screen_Settings_Base {
	private $type;
	private $sections;
	private $base_url;
	private $tabs;

	private $options;

	protected $items = array();

	public function __construct( $type ) {
		$this->type = $type;
		$this->sections = $this->load_sections();
		$this->base_url = remove_query_arg( array( 'type', 'section' ), $_SERVER["REQUEST_URI"] );

		$this->tabs     = new Tabify_Edit_Screen_Tabs( $this->sections, 'vertical', 'subtab' );
	}

	protected function load_sections() {
		return array();
	}

	protected function get_sections() {
		return $this->sections;
	}

	public function get_sections_menu() {
		echo $this->tabs->get_tabs_with_container();
	}

	/**
	 * Echo all the items
	 *
	 * @since 0.1.0
	 */
	public function get_section() {
		$sections = $this->get_sections();
		$items    = $this->items;
		$options  = $this->get_options( $this->type );

		foreach ( $sections as $section => $label ) {
			$default_items = $this->get_default_items( $section );

			if ( ! isset( $options[ $section ] ) ) {
				$options[ $section ] = array (
					'tabs' => array(
						array( 'title' => __( 'Others', 'tabify-edit-screen' ), 'items' => array() )
					)
				);
			}

			if ( $section == $this->tabs->get_current_tab() ) {
				echo '<div class="tabifybox tabifybox-' . $section . '">';
			}
			else {
				echo '<div class="tabifybox tabifybox-hide tabifybox-' . $section . '" style="display: none;">';
			}

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

			do_action( 'tabify_settings', $this->type, $section, $options[ $section ] );
			echo '</div>';

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

				// Backwards compatibily from 0.5 to 0.6
				if ( ! isset( $tab['items'] ) && isset( $tab['metaboxes'] ) ) {
					$tab['items'] = $tab['metaboxes'];
				}

				if ( $tab['title'] == '' ) {
					$tab['title'] = __( 'Choose title', 'tabify-edit-screen' );
				}

				echo '<div class="menu-item-handle tabify_tab">';

				$this->get_section_tab_title( $section, $tab['title'], $tab, $remove );

				echo '<ul>';
				if ( isset( $tab['items'] ) ) {
					foreach ( $tab['items'] as $item_id_fallback => $item_id ) {
						// Backwards compatibily from 0.3 to 0.4.
						if ( intval( $item_id_fallback ) == 0 && $item_id_fallback !== 0 ) {
							$item_id = $item_id_fallback;
						}

						if ( empty( $item_id ) ) {
							continue;
						}

						$item_title = '';
						if ( isset( $items[ $section ][ $item_id ] ) ) {
							$item_title = $items[ $section ][ $item_id ];

							$item_title = apply_filters( 'tabify_items_title', $item_title, $item_id );
							$item_title = apply_filters( 'tabify_items_title_' . $item_id , $item_title );
						}

						$this->list_show_items( $item_id, $item_title, $tab_id, $section, $default_items );

						unset( $items[ $section ][ $item_id ] );
					}
				}

				if ( count( $options[ $section ]['tabs'] ) == ( $tab_id + 1 ) ) {
					foreach ( $items[ $section ] as $item_id => $item_title ) {
						if ( empty( $item_id ) ) {
							continue;
						}

						$item_title = apply_filters( 'tabify_items_title', $item_title, $item_id );
						$item_title = apply_filters( 'tabify_items_title_' . $item_id , $item_title );

						$this->list_show_items( $item_id, $item_title, $tab_id, $section, $default_items );
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
		echo '<input type="submit" id="create_tab" name="create_tab" class="button button-secondary" value="' . __( 'Create a new tab', 'tabify-edit-screen' ) . '" />';
		submit_button( '', 'primary', 'submit', false );
		echo '</p>';

		$this->print_backbone_template();
	}

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

	protected function get_options( $type = null ) {
		if ( ! $this->options ) {
			$this->options = get_option( 'tabify-edit-screen', array() );
		}

		if ( $type && isset( $this->options[ $type ] ) ) {
			return $this->options[ $type ];
		}

		return $this->options;
	}

	/**
	 * Show the items for the sortable list
	 *
	 * @since 0.4.0
	 */
	protected function list_show_items( $item_id, $item_title, $tab_id, $type, $default_items ) {
		$options = $this->get_options( $this->type );

		// Most likely a meta box that doesn't exist anymore
		if ( empty( $item_title ) ) {
			return;
		}

		$item_title = strip_tags( $item_title );

		if ( in_array( $item_id, $default_items ) ) {
			echo '<li id="' . $type . '-' . $item_id . '" class="tabifybox-hide">';
		}
		else {
			echo '<li id="' . $type . '-' . $item_id . '">';
		}

		echo '<div class="menu-item-bar"><div class="menu-item-handle">';
		echo '<span class="item-title">' . $item_title . '</span>';

		echo '<input type="hidden" name="tabify[' . $this->type . '][' . $type . '][tabs][' . $tab_id . '][items][]" value="' . $item_id . '" />';

		echo '<span class="item-order hide-if-js">';
		echo '<select name="tabify[' . $this->type . '][' . $type . '][tabs][' . $tab_id . '][items_tab][]">';

		if ( isset( $options[ $type ] ) ) {
			$amount_tabs = count( $options[ $type ]['tabs'] );

			for( $i = 0; $i < $amount_tabs; $i++ ) {
				if ( ! isset( $options[ $type ]['tabs'][ $i ] ) ) {
					continue;
				}

				if ( $i == $tab_id ) {
					echo '<option value="' . $i . '" selected="selected">' . esc_html( $options[ $type ]['tabs'][ $i ]['title'] ) . '</option>';
				}
				else {
					echo '<option value="' . $i . '">' . esc_html( $options[ $type ]['tabs'][ $i ]['title'] ) . '</option>';
				}
			}
		}

		echo '</select>';
		echo '</span>';
		echo '</div></div></li>';
	}

}
