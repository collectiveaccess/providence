<?php
/**
 * ----------------------------------------------------------------------
 * MediaPluginHelpersTest.php
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
 * @subpackage tests
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 *
 */

use PHPUnit\Framework\TestCase;

class MediaPluginHelpersTest extends TestCase {
	public function setUp(): void {
		// noop
	}

	public function testCaGetExternalApplicationPathForAppAsString() {
		$paths = caGetExternalApplicationPath( "ghostscript_app", [ 'returnAsArray' => true ] );
		$this->assertSame( "/usr/bin/gs", $paths[0] );
	}

	public function testCaGetExternalApplicationPathForAppInList() {
		$paths = caGetExternalApplicationPath( "ffmpeg_app", [ 'returnAsArray' => true ] );
		$this->assertSame( "/usr/bin/ffmpeg", $paths[0] );
		$this->assertSame( "/usr/local/bin/ffmpeg", $paths[1] );
	}

	public function testCaGetExternalApplicationPathForPathAsString() {
		$paths = caGetExternalApplicationPath( "imagemagick_path", [ 'returnAsArray' => true ] );
		$this->assertSame( "/usr/bin", $paths[0] );
	}

	public function testCaGetExternalApplicationPathReturnsNullsForMissingApplication() {
		$this->assertNull( caGetExternalApplicationPath( "test" ) );
	}

	// TODO: How do we reliably test detection of dependencies? Is ImageMagick installed on Travis-CI?
	// 		Not sure what the correct strategy is for testing presence of this across various platforms.
	// public function testCaMediaPluginImageMagickInstalledFails() {
	//         $this->assertFalse(caMediaPluginImageMagickInstalled());
	// }
}
