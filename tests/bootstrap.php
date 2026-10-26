<?php

$polyfills = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && is_dir( $polyfills ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills );
}

$_tests_dir = getenv('WP_TESTS_DIR');

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../tabify-edit-screen.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

