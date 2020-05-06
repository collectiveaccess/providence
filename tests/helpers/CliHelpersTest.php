<?php
/** ---------------------------------------------------------------------
 * tests/helpers/CliHelpersTest.php
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

class CliHelpersTest extends TestCase {

    private $opa_options;

    protected function setUp(): void {
        $this->opa_options = array(
                "hostname-s" => 'Hostname of installation. If omitted default installation is used.',
                "hostname=s" => 'Hostname of installation. If omitted default installation is used.',
                "hostname|h-s" => 'Hostname of installation. If omitted default installation is used.',
                "hostname|h=s" => 'Hostname of installation. If omitted default installation is used.',
                "hostname" => 'Hostname of installation. If omitted default installation is used.',
        );
    }

    /**
     * Delete all records we created for this test to avoid side effects with other tests
     */
    protected function tearDown() : void {
    }

    # -------------------------------------------------------
    public function testDisplayFormatWithNoAliasOptional() {
        // some real-world examples
        $vs_key = "hostname-s";

        $result = caFormatCmdOptionsForDisplay($vs_key, $this->opa_options[$vs_key]);
        $this->assertStringContainsString("--hostname     ", $result);
    }

    public function testDisplayFormatWithNoAliasMandatory() {
        // some real-world examples
        $vs_key = "hostname=s";

        $result = caFormatCmdOptionsForDisplay($vs_key, $this->opa_options[$vs_key]);
        $this->assertStringContainsString("--hostname     ", $result);
    }

    public function testDisplayFormatWithAliasOptional() {
        // some real-world examples
        $vs_key = "hostname|h-s";

        $result = caFormatCmdOptionsForDisplay($vs_key, $this->opa_options[$vs_key]);
        $this->assertStringContainsString("--hostname (-h)     ", $result);
    }

    public function testDisplayFormatWithAliasMandatory() {
        // some real-world examples
        $vs_key = "hostname|h=s";

        $result = caFormatCmdOptionsForDisplay($vs_key, $this->opa_options[$vs_key]);
        $this->assertStringContainsString("--hostname (-h)     ", $result);
    }

    public function testDisplayFormatNoArgs() {
        // some real-world examples
        $vs_key = "hostname";

        $result = caFormatCmdOptionsForDisplay($vs_key, $this->opa_options[$vs_key]);
        $this->assertStringContainsString("--hostname     ", $result);
    }

}
