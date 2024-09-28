<?php
/** ---------------------------------------------------------------------
 * tests/lib/Controller/LabelTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__."/Db.php");
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/AccessRestrictions.php");
require_once(__CA_LIB_DIR__."/Controller/RequestDispatcher.php");
require_once(__CA_LIB_DIR__."/Controller/Request/RequestHTTP.php");
require_once(__CA_LIB_DIR__."/Controller/Response/ResponseHTTP.php");
require_once(__CA_MODELS_DIR__."/ca_user_roles.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

class LabelTest extends TestCase {
	# -------------------------------------------------------
	/**
	 * @var ca_objects
	 */
	var $object;
	# -------------------------------------------------------
	protected function setUp() : void {
		Datamodel::getTableNum("ca_objects");

		// set up test role

		$this->object = new ca_objects();
		$this->object->set("type_id","moving_image");
		$this->object->set("idno","TEST.1");
		if(!$this->object->insert()){
			print "ERROR inserting object: ".join(" ",$this->object->getErrors())."\n";
		}

		

		$this->assertInstanceOf('ca_objects', $this->object);
	}
	# -------------------------------------------------------
	protected function tearDown() : void {
		$this->object->delete(true);
	}
	# -------------------------------------------------------
	public function testAddLabel(){
		$ret = $this->object->addLabel(['name' => 'Test object 1'], 'de_DE', null, true);
		$this->assertGreaterThan(0, $ret, "Expect return of object_id (> 0)");
		
		$labels = $this->object->get('ca_objects.preferred_labels.name', ['returnAllLocales' => true, 'returnWithStructure' => true]);
		$arr = array_shift($labels);
		$locale_id = array_shift(array_keys($arr));
		$this->assertEquals(ca_locales::idToCode($locale_id), 'de_DE', 'Expect locale of newly inserted label to be German (de_DE)');
	}
	# -------------------------------------------------------
}
