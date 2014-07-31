<?php

class Test_Plugin extends WP_UnitTestCase {
	private $readme_data;

	public function test_tested_up_to() {
		$readme_data = $this->get_readme();

		try {
			wp_version_check();
		}
		catch (Exception $e) {
			$this->markTestSkipped( "There is not internet connection" );
		}

		$cur = get_preferred_from_update_core();

		if ( isset( $cur->current ) ) {
			list( $display_version ) = explode( '-', $cur->current );

			$this->assertTrue( version_compare( $readme_data['tested_up_to'], $display_version, '>=' ) );
		}
	}

	public function test_stable_tag() {
		$readme_data = $this->get_readme();
		$plugin_data = get_plugin_data( dirname( dirname( __FILE__ ) ) . '/tabify-edit-screen.php' );

		$this->assertEquals( $readme_data['stable_tag'], $plugin_data['Version'] );
	}


	private function get_readme() {
		if( ! isset( $this->readme_data ) ) {
			$file = dirname( dirname( __FILE__ ) ) . '/readme.txt';

			if ( ! is_file( $file ) ) {
				return false;
			}

			$file_contents = implode( '', file( $file ) );

			$file_contents = str_replace( array( "\r\n", "\r" ), "\n", $file_contents );
			$file_contents = trim($file_contents);

			if ( 0 === strpos( $file_contents, "\xEF\xBB\xBF" ) ) {
				$file_contents = substr( $file_contents, 3 );
			}

			// Markdown transformations
			$file_contents = preg_replace( "|^###([^#]+)#*?\s*?\n|im", '=$1='."\n",     $file_contents );
			$file_contents = preg_replace( "|^##([^#]+)#*?\s*?\n|im",  '==$1=='."\n",   $file_contents );
			$file_contents = preg_replace( "|^#([^#]+)#*?\s*?\n|im",   '===$1==='."\n", $file_contents );


			preg_match( '|Tested up to:(.*)|i', $file_contents, $_tested_up_to );
			preg_match( '|Stable tag:(.*)|i', $file_contents, $_stable_tag );


			$this->readme_data = array(
				'tested_up_to' => trim( $_tested_up_to[1] ),
				'stable_tag'   => trim( $_stable_tag[1] )
			);
		}

		return $this->readme_data;
	}
}

