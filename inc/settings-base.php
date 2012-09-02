<?php

class Tabify_Edit_Screen_Settings_Base {
	private $type;
	private $sections;
	private $base_url;

	function __construct( $type ) {
		$this->type = $type;
		$this->sections = $this->load_sections();
		$this->base_url = remove_query_arg( array( 'type', 'section' ), $_SERVER["REQUEST_URI"] );

		
		$this->tabs = new Tabify_Edit_Screen_Tabs( $this->sections, 'vertical' );
	}

	protected function load_sections() {
		return array();
	}

	protected function get_sections() {
		return $this->sections;
	}

	public function get_sections_menu() {
		echo $this->tabs->get_tabs_with_container();
		return;

		$html = '<ul>';

		$sections = $this->get_sections();
		foreach( $sections as $section_key => $section_title ) {
			echo '<li><a href="' . $this->base_url . '&tab=' . $this->type . '&section=' . $section_key . '">' . $section_title . '</a></li>';
		}

		echo '</ul>';
	}

	public function get_section() {
		echo '';
	}

	/**
	 * Show the metabox for the sortable list
	 *
	 * @since 0.4
	 */
	protected function list_show_metabox( $metabox_id, $metabox_title, $tab_id, $posttype, $default_metaboxes ) {
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
	 * Get all the metaboxes that should always be showed
	 *
	 * @return array All the metaboxes id's in an array
	 *
	 * @since 0.4
	 */
	protected function get_default_metaboxes( $post_type = '' ) {
		$defaults = array( 'titlediv', 'submitdiv' ); //, 'postdivrich'
		$defaults = apply_filters( 'tabify_default_metaboxes', $defaults, $post_type );
		return apply_filters( 'tabify_default_metaboxes_' . $post_type , $defaults );
	}
}