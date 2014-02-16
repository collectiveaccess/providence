<?php
/** ---------------------------------------------------------------------
 * support/tests/lib/core/Service/BaseJSONServiceTest.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 * 
 * @package CollectiveAccess
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once('PHPUnit/Autoload.php');
require_once(__CA_LIB_DIR__."/ca/Service/BaseJSONService.php");
require_once(__CA_LIB_DIR__."/core/Controller/Request/RequestHTTP.php");
require_once(__CA_LIB_DIR__."/core/Controller/Response/ResponseHTTP.php");

class BaseJSONServiceTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testProperInstantiation(){
		global $_SERVER; // emulate client request
		$_SERVER["REQUEST_METHOD"] = "GET";
		$_SERVER["SCRIPT_NAME"] = "/service.php";

		$vo_response = new ResponseHTTP();
		$vo_request = new RequestHTTP($vo_response, array("dont_create_new_session" => true));
		$vo_request->setRawPostData('{"foo" : "bar"}');
		$vo_request->setParameter("id",4711,"GET");

		$vo_service = new BaseJSONService($vo_request,"ca_objects");

		$this->assertFalse($vo_service->hasErrors());
		$this->assertEquals("ca_objects",$vo_service->getTableName());
		$this->assertEquals("GET",$vo_service->getRequestMethod());
		$this->assertEquals(4711,$vo_service->getIdentifier());
		$this->assertEquals(array("foo" => "bar"),$vo_service->getRequestBodyArray());
	}
	# -------------------------------------------------------
	public function testImproperInstantiation(){
		global $_SERVER; // emulate client request
		$_SERVER["REQUEST_METHOD"] = "FOOBAR";
		$_SERVER["SCRIPT_NAME"] = "/service.php";

		$vo_response = new ResponseHTTP();
		$vo_request = new RequestHTTP($vo_response, array("dont_create_new_session" => true));
		$vo_request->setRawPostData('This is not JSON!');

		$vo_service = new BaseJSONService($vo_request,"invalid_table");

		$this->assertTrue($vo_service->hasErrors());
		// we don't check error messages because they tend to change frequently but
		// the above code should generate 3 errors (invalid table, no JSON request body
		// and invalid request method)
		$this->assertEquals(3,sizeof($vo_service->getErrors()));
	}
	# -------------------------------------------------------
}

