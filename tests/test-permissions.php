<?php

include 'features/permissions/permissions.php';

class Tabify_Tests_Permission extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
	}

	function tearDown() {
		parent::tearDown();
	}

	public function test_roles_method() {
		$method = new ReflectionMethod(
		  'Tabify_Edit_Screen_Feature_Permissions', 'get_roles'
		);
 
		$method->setAccessible(TRUE);

		$permission = $method->invoke( new Tabify_Edit_Screen_Feature_Permissions );

		$this->assertNotNull( $permission );
		$this->assertTrue( is_array( $permission ) );
	}

}
