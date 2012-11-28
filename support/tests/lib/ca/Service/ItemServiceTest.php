<?php
/** ---------------------------------------------------------------------
 * support/tests/lib/core/Service/ItemServiceTest.php 
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
require_once('./setup.php');
require_once(__CA_LIB_DIR__."/ca/Service/ItemService.php");

class ItemServiceTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testGetAllItemInfo(){
		global $_SERVER; // emulate client request
		$_SERVER["REQUEST_METHOD"] = "GET";
		$_SERVER["SCRIPT_NAME"] = "/service.php";

		$vo_response = new ResponseHTTP();
		$vo_request = new RequestHTTP($vo_response);
		$vo_request->setParameter("id",27,"GET");

		$vo_service = new ItemService($vo_request,"ca_objects");
		$va_return = $vo_service->dispatch();

		$this->assertTrue($va_return["ok"]);

		// check example data from each section

		// intrinsic
  		$this->assertEquals($va_return["idno"]["value"],"CIHP.27");
		$this->assertEquals($va_return["access"]["value"],"1");
		$this->assertEquals($va_return["access"]["display_text"]["en_US"],"Public");

		// labels
		$this->assertEquals(
			$va_return["preferred_labels"]["en_US"][0],
			"Astroland arcade, Surf Avenue"
		);

		// attributes
		$this->assertEquals(
			$va_return["ca_objects.description"][0]["en_US"]["description"],
			"Astroland arcade, taken from Surf Avenue looking south west."
		);

		$this->assertEquals(
			$va_return["ca_objects.subtitle"][0]["en_US"]["subtitle"],
			"November 30, 2006"
		);

		// related items
		$this->assertEquals(
			$va_return["related"]["ca_entities"][0]["entity_id"], "4"
		);

		$this->assertEquals(
			$va_return["related"]["ca_entities"][0]["displayname"], "Seth Kaufman"
		);

		$this->assertEquals(
			$va_return["related"]["ca_list_items"][0]["displayname"], "arcades"
		);

		$this->assertEquals(
			$va_return["related"]["ca_list_items"][0]["relationship_typename"], "depicts"
		);

	}
	# -------------------------------------------------------
	/**
	 * This part of the service is basically a wrapper around BaseModel::get so we don't
	 * need to test that extensively here. We "just" have to make sure the integration works.
	 */
	public function testGetSpecificItemInfo(){
		global $_SERVER; // emulate client request
		$_SERVER["REQUEST_METHOD"] = "GET";
		$_SERVER["SCRIPT_NAME"] = "/service.php";

		$vo_response = new ResponseHTTP();
		$vo_request = new RequestHTTP($vo_response);
		$vs_request_body=<<<JSON
{
	"bundles" : {
		"ca_objects.access" : {
			"convertCodesToDisplayText" : true
		},
		"ca_objects.preferred_labels.name" : {
			"delimiter" : "; "
		},
		"ca_entities.entity_id" : {
			"returnAsArray" : true
		}
	}
}
JSON;
		$vo_request->setParameter("id",27,"GET");
		$vo_request->setRawPostData($vs_request_body);

		$vo_service = new ItemService($vo_request,"ca_objects");
		$va_return = $vo_service->dispatch();

		$this->assertTrue($va_return["ok"]);

		$this->assertEquals(
			$va_return["ca_objects.access"],"Public"
		);

		$this->assertEquals(
			$va_return["ca_objects.preferred_labels.name"],"Astroland arcade, Surf Avenue"
		);

		$this->assertEquals(
			$va_return["ca_entities.entity_id"],
			array("4")
		);
	}
	# -------------------------------------------------------
}
