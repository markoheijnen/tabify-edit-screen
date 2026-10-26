<?php

if ( 8 !== count( $argv ) ) {
	fwrite( STDERR, "Expected input config, output config, core directory, database name, user, password and host.\n" );
	exit( 1 );
}

$config = file_get_contents( $argv[1] );
$config = str_replace( "dirname( __FILE__ ) . '/src/'", var_export( rtrim( $argv[3], '/' ) . '/', true ), $config );
foreach ( array( 'DB_NAME' => $argv[4], 'DB_USER' => $argv[5], 'DB_PASSWORD' => $argv[6], 'DB_HOST' => $argv[7] ) as $constant => $value ) {
	$config = preg_replace_callback( "~define\\(\\s*'" . $constant . "'\\s*,\\s*'[^']*'\\s*\\);~", function() use ( $constant, $value ) {
		return 'define( ' . var_export( $constant, true ) . ', ' . var_export( $value, true ) . ' );';
	}, $config );
}
file_put_contents( $argv[2], $config );
