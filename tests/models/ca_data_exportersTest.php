<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importers.php
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


require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
class ca_data_exportersTest extends PHPUnit_Framework_TestCase {
	/**
	 * @link http://clangers.collectiveaccess.org/jira/browse/PROV-1026
	 */
	public function testDataExporterCanLoadFromFile(){
		$t_locale = new ca_locales();
		$va_locales = $t_locale->getLocaleList();
		$vn_locale_id = key($va_locales);

		$t_exporter = new ca_data_exporters();
		$va_errors = array();
		ca_data_exporters::loadExporterFromFile(__DIR__ . '/data/list_item_export_mapping.xlsx', $va_errors, array('locale_id' => $vn_locale_id));

		$vo_exporter = ca_data_exporters::loadExporterByCode('testmappingforunittests');

		$this->assertEmpty($va_errors, 'Should be no error messages');
		$this->assertTrue(is_object($vo_exporter), 'Should have found an exporter by the correct name');
		$this->assertInstanceOf('ca_data_exporters', $vo_exporter, 'Incorrect type loaded');
		$vo_exporter->setMode(ACCESS_WRITE);
		$vo_exporter->delete(true, array( 'hard' => true ));

		$vo_exporter = $t_exporter->load(array('exporter_code' => 'testmappingforunittests'));
		$this->assertFalse($vo_exporter, 'Should no longer have an exporter loaded');
	}
}
