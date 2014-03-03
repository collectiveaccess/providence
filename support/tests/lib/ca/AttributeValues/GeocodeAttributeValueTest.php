<?php
/** ---------------------------------------------------------------------
 * support/tests/lib/ca/AttributeValues/GeocodeAttributeValueTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/ca/Attributes/Values/GeocodeAttributeValue.php");

class GeocodeAttributeValueTest extends PHPUnit_Framework_TestCase {

	public function testWithCoordinates(){
		$o_val = new GeocodeAttributeValue();

		$va_return = $o_val->parseValue('[52.52000660000002,13.404954]',array());
		$this->assertEquals($va_return['value_decimal1'],'52.52000660000002');
		$this->assertEquals($va_return['value_decimal2'],'13.404954');
		// they do get rounded a bit in this format
		$this->assertEquals($va_return['value_longtext2'],'52.5200066,13.404954');
	}

	public function testWithAddresses(){
		$o_val = new GeocodeAttributeValue();

		// google use this as example in their API docs, so let's hope it doesn't move ;-)
		$va_return = $o_val->parseValue('1600 Amphitheatre Parkway, Mountain View, CA',array());
		$this->assertEquals($va_return['value_decimal1'],'37.4219985');
		$this->assertEquals($va_return['value_decimal2'],'-122.0839544');
		$this->assertEquals($va_return['value_longtext2'],'37.4219985,-122.0839544');
	}

	public function testWithGarbage(){
		$o_val = new GeocodeAttributeValue();

		$vm_return = $o_val->parseValue('thisshouldntgiveusanyresultsfromgoogle',array());
		$this->assertFalse($vm_return);
	}

}
