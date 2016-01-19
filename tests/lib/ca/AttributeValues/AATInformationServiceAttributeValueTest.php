<?php
/** ---------------------------------------------------------------------
 * tests/lib/ca/AttributeValues/AATInformationServiceAttributeValueTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/AAT.php");
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

class AATInformationServiceAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testGetDisplayLabelFromLookupText() {
		$o_service = new WLPlugInformationServiceAAT();
		$this->assertEquals('dump trucks', $o_service->getDisplayValueFromLookupText('[300022372] dump trucks [trucks, cargo vehicles by form]'));
	}
}
