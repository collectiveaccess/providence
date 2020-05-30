<?php

use PHPUnit\Framework\TestCase;

/**
 * ----------------------------------------------------------------------
 * StemmerFactoryTests.php
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
require_once(__CA_LIB_DIR__ . '/Search/Common/StemmerFactory.php');


class StemmerFactoryTests extends TestCase {

    public function testStemmerFactoryAvailableClasses() {
        $instance = StemmerFactory::get_instance();
        $va_plugins = StemmerFactory::getPluginNames();
        $this->assertIsArray($va_plugins);
        $this->assertCount(2, $va_plugins);
    }

    public function testStemmerFactoryCreates() {
        $instance = StemmerFactory::get_instance();
        $instance->setPluginPath(__DIR__ . '/StemmerClasses');
        $class1 = $instance->create('Class1');
        $this->assertNotNull($class1);
    }
}
