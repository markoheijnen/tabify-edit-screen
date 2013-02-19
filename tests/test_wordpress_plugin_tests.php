<?php
class Tabify_Tests_Tests extends WP_UnitTestCase {

	/**
	 * Verify that WordPress is installed and is the version that we requested
	 */
	function test_wp_version() {

		if ( ! getenv( 'TRAVIS_PHP_VERSION' ) )
			$this->markTestSkipped( 'Not running on Travis CI' );

		// check that we have the https wrapper (php 5.2 on travis-ci does not)
		if ( ! in_array( 'https', stream_get_wrappers() ) )
			$this->markTestSkipped( 'Test skipped since https wrapper unavailable.' );

		//grab the requested version
		$requested_version = getenv( 'WP_VERSION' );

		//trunk is always "master" in github terms, but WordPress has a specific way of describing it
		//grab the exact version number to verify that we're on trunk
		if ( $requested_version == 'master' ) {
			$file = file_get_contents( 'https://raw.github.com/WordPress/WordPress/master/wp-includes/version.php' );
			preg_match( '#\$wp_version = \'([^\']+)\';#', $file, $matches );
			$requested_version = $matches[1];
		}

		$this->assertEquals( get_bloginfo( 'version' ), $requested_version );

	}

}
