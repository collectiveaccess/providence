<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/ConfigurationUpdateTest.php
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

require_once(__CA_BASE_DIR__.'/install/inc/Installer.php');

class ConfigurationUpdateTest extends PHPUnit_Framework_TestCase {

	public function testAddNewLocale() {
		$t_locale = new ca_locales();
		$this->assertFalse((bool) $t_locale->localeCodeToID('fk_FK'));

		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/locales/add_new.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();

		MemoryCache::flush('LocaleCodeToId');
		$this->assertGreaterThan(0, ($vn_locale_id = $t_locale->localeCodeToID('fk_FK')));

		$t_locale->load($vn_locale_id);
		$t_locale->setMode(ACCESS_WRITE);
		$t_locale->delete();
	}

	public function testUpdateLocale() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/locales/update_existing.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();

		$t_locale = new ca_locales();
		$this->assertGreaterThan(0, ($vn_locale_id = $t_locale->localeCodeToID('en_AU')));

		$t_locale->load($vn_locale_id);
		$this->assertFalse((bool) $t_locale->get('dont_use_for_cataloguing'));

		$t_locale->setMode(ACCESS_WRITE);
		$t_locale->set('dont_use_for_cataloguing', 1);
		$t_locale->update();
		$this->assertTrue((bool) $t_locale->get('dont_use_for_cataloguing'));
	}
}
