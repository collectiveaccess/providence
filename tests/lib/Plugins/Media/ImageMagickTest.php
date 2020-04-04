<?php
/** ---------------------------------------------------------------------
 * tests/helpers/ImageMagickTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @author Orestes Sanchez <orestes@estotienearreglo.es>
 *
 * ----------------------------------------------------------------------
 */
use PHPUnit\Framework\TestCase;

require_once(__CA_APP_DIR__."/helpers/systemHelpers.php");
require_once(__CA_APP_DIR__."/lib/Plugins/Media/ImageMagick.php");

class ImageMagickTest extends TestCase {
	# -------------------------------------------------------
	public function testConvertCmd() {
		$im_plugin = new WLPlugMediaImageMagick();
		$im_plugin->register();
		$command = $im_plugin->command('convert');
		$this->assertEquals('/usr/bin/convert', $command);
	}

	public function testIdentifyCmd() {
		$im_plugin = new WLPlugMediaImageMagick();
		$im_plugin->register();
		$command = $im_plugin->command('identify');
		$this->assertEquals('/usr/bin/identify', $command);
	}

	public function testWhateverCmd() {
		$im_plugin = new WLPlugMediaImageMagick();
		$im_plugin->register();
		$command = $im_plugin->command('whatever');
		$this->assertEquals('/usr/bin/whatever', $command);
	}

	public function testMissingCmdWithEmptyArgs() {
		$im_plugin = new WLPlugMediaImageMagick();
		$im_plugin->register();
		$command = $im_plugin->commandWithDefaultArgs('whatever');
		$this->assertEquals('/usr/bin/whatever ', $command);
	}

	public function testConvertCmdWithDefaultArgs() {
		$im_plugin = new WLPlugMediaImageMagick();
		$im_plugin->register();
		$command = $im_plugin->commandWithDefaultArgs('convert');
		$this->assertEquals('/usr/bin/convert -limit thread 1', $command);
	}

}
