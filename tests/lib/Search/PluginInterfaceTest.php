<?php
/**
 * ----------------------------------------------------------------------
 * PluginInterfaceTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
 * @subpackage test
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 *
 */

use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__ . '/Search/Common/StemmerFactory.php');

class IPluginListTest {
    use IPlugin;
}

class IPluginListSkipFilesTest {
    use IPlugin;

    public static function skip_file($file, $dir) {
        return $file=="skipfile.php";
    }

}

class PluginInterfaceTest extends TestCase {

    protected $opo_plugin;

    protected function setUp(): void {
        parent::setUp();
        IPluginListTest::setPluginPath(__DIR__ . '/dataAllFiles');
        IPluginListSkipFilesTest::setPluginPath(__DIR__ . '/dataSkipFiles');
    }

    public function testPluginListAllFiles() {
        $va_plugins = IPluginListTest::getPluginNames();
        $this->assertIsArray($va_plugins);
        $this->assertCount(4, $va_plugins);
    }

    public function testPluginListSkipFiles() {
        $va_plugins = IPluginListSkipFilesTest::getPluginNames();
        $this->assertIsArray($va_plugins);
        $this->assertCount(3, $va_plugins);
    }
}