<?php
/** ---------------------------------------------------------------------
 * tests/helpers/LocaleHelpersTest.php
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
 * @package    CollectiveAccess
 * @subpackage tests
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once(__CA_APP_DIR__ . "/helpers/CLIHelpers.php");

class LocaleHelpersTest extends TestCase
{

    private $ops_locales_by_code;

    protected function setUp(): void
    {
        $this->ops_locales_by_code = $vs_locales_by_code = [
            1 => "locale 1",
            2 => "locale 2",
            3 => "locale 3"
        ];
    }

    /**
     * Delete all records we created for this test to avoid side effects with other tests
     */
    protected function tearDown(): void
    {
    }

    # -------------------------------------------------------
    public function testGetLanguageFromLocaleWithLanguage()
    {
        $locale  = 'ca_ES';
        $country = caGetLanguageFromLocale($locale);
        $this->assertEquals('ca', $country);
    }

    # -------------------------------------------------------
    public function testGetLanguageFromLocaleWithEmptyLanguage()
    {
        $locale  = 'es_';
        $country = caGetLanguageFromLocale($locale);
        $this->assertEquals('es', $country);
    }

    # -------------------------------------------------------
    public function testGetLanguageFromLocaleWithOnlyLanguage()
    {
        $locale  = 'es';
        $country = caGetLanguageFromLocale($locale);
        $this->assertEquals('es', $country);
    }

    # -------------------------------------------------------
    public function testGetLanguageFromLocaleFromNullUsesDefaultLocale()
    {
        $locale  = null;
        $country = caGetLanguageFromLocale($locale);
        $this->assertEquals('en', $country);
    }

    # -------------------------------------------------------
    public function testCaFilterLocalesByCodeFilterWithNonExistingLocale()
    {
        $result = caFilterLocalesByCode($this->ops_locales_by_code, [1, 2, 6]);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([1, 2], array_keys($result));
    }

    # -------------------------------------------------------
    public function testCaFilterLocalesByCodeFilterWithEmptyFilter()
    {
        $result = caFilterLocalesByCode($this->ops_locales_by_code, []);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([], array_keys($result));
    }
    # -------------------------------------------------------
    public function testCaFilterLocalesByCodeFilterWithAllMissingLocales()
    {
        $result = caFilterLocalesByCode($this->ops_locales_by_code, [7,8,9]);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([], array_keys($result));
    }
    # -------------------------------------------------------
    public function testCaFilterLocalesByCodeFilterWithExistingLocalesAtTheEnd()
    {
        $result = caFilterLocalesByCode($this->ops_locales_by_code, [7,8,9,3,2]);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([3,2], array_keys($result));
    }
    # -------------------------------------------------------

}
