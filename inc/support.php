<?php

/**
 * This class get added to all my plugins for an easy way to generate a support sidebar
 */

class Tabify_Support {
	private $name;

	function __construct( $name ) {
		$this->name = $name;
	}

	public function support_forum() {
		$content = '<p>' . __( 'If you are having problems with this plugin, please talk about them in the', $this->name ) . ' <a href="http://wordpress.org/support/plugin/' . $this->name . '/">' . __( "Support forums", $this->name ) . '</a>.</p>';
		$this->postbox( 'support', __( 'Need support?', $this->name ), $content );
	}



	/**
	 * Create a potbox widget.
	 *
	 * @param string $id      ID of the postbox.
	 * @param string $title   Title of the postbox.
	 * @param string $content Content of the postbox.
	 * 
	 * @since 0.1
	 */
	private function postbox( $id, $title, $content ) {
		echo '<div id="<?php echo $id; ?>" class="mhbox">';
		echo '<h3>' . $title . '</h3>';
		echo $content;
		echo '</div>';
	}
}