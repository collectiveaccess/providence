<?php
/** ---------------------------------------------------------------------
 * tests/install/ProfileSchemaTest.php
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

require_once(__CA_APP_DIR__.'/helpers/configurationHelpers.php');
require_once(dirname(__FILE__).'/../../install/inc/Installer.php');

class ProfileSchemaTest extends PHPUnit_Framework_TestCase {

	public function testAvailableProfilesConformToSchema() {
		$va_profiles = caGetAvailableXMLProfiles(dirname(__FILE__).'/../../install/');
		$this->assertGreaterThan(0, sizeof($va_profiles));

		foreach($va_profiles as $vs_profile) {
			$vo_installer = new Installer(dirname(__FILE__).'/../../install/profiles/xml/', $vs_profile, 'info@collectiveaccess.org', false, false);
			$this->assertEquals(0, $vo_installer->numErrors(), "The profile '$vs_profile' doesn't conform to the XML schema");
		}
	}
}
