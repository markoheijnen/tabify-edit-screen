<?php

class Tabify_Edit_Screen_Settings_Base {
	private $type;
	private $sections;
	private $base_url;

	private $options;

	function __construct( $type ) {
		$this->type = $type;
		$this->sections = $this->load_sections();
		$this->base_url = remove_query_arg( array( 'type', 'section' ), $_SERVER["REQUEST_URI"] );

		$this->tabs = new Tabify_Edit_Screen_Tabs( $this->sections, 'vertical', 'subtab' );
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

	public function get_section() {
		echo '';
	}

	protected function get_options( $type = null ) {
		if( ! $this->options )
			$this->options = get_option( 'tabify-edit-screen', array() );

		if( $type && isset( $this->options[ $type ] ) )
			return $this->options[ $type ];

		return $this->options;
	}

	/**
	 * Show the metabox for the sortable list
	 *
	 * @since 0.4
	 */
	protected function list_show_metabox( $metabox_id, $metabox_title, $tab_id, $posttype, $default_metaboxes ) {
		if( in_array( $metabox_id, $default_metaboxes ) || empty( $metabox_title ) )
			echo '<li class="tabifybox-hide">';
		else
			echo '<li class="menu-item-handle">' . $metabox_title;

		echo '<input type="hidden" name="tabify[' . $this->type . '][' . $posttype . '][tabs][' . $tab_id . '][metaboxes][]" value="' . $metabox_id . '" />';

		echo '<span class="item-order hide-if-js">';
		echo '<select name="tabify[' . $this->type . '][' . $posttype . '][tabs][' . $tab_id . '][metaboxes_tab][]">';
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
}