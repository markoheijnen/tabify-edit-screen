<?php

include_once 'features/permissions/permissions.php';

class Tabify_Tests_Permission extends WP_UnitTestCase {

	public function test_roles_method() {
		$method = new ReflectionMethod(
		  'Tabify_Edit_Screen_Feature_Permissions', 'get_roles'
		);
 
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$permission = $method->invoke( new Tabify_Edit_Screen_Feature_Permissions );

		$this->assertNotNull( $permission );
		$this->assertTrue( is_array( $permission ) );
	}

}
