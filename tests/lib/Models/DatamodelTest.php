<?php
/** ---------------------------------------------------------------------
 * tests/lib/Models/DatamodelTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Datamodel.php');

class DatamodelTest extends PHPUnit_Framework_TestCase {
	public function testInstantiateAllModels() {
		$va_tables = Datamodel::getTableNames();

		foreach($va_tables as $vs_table) {
			// we do multiple calls to get some cache hits
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vs_table));
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vs_table, true));

			$vn_table_num = Datamodel::getTableNum($vs_table);

			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vn_table_num));
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vn_table_num, true));

			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vs_table));
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vs_table, true));
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vn_table_num));
			$this->assertInstanceOf($vs_table, Datamodel::getInstance($vn_table_num, true));
		}
	}
}
