<?php
/** ---------------------------------------------------------------------
 * tests/helpers/ImageMagickTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2020 Whirl-i-Gig
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
 * @author     Orestes Sanchez <orestes@estotienearreglo.es>
 *
 * ----------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once( __CA_APP_DIR__ . "/helpers/systemHelpers.php" );
require_once( __CA_APP_DIR__ . "/lib/Plugins/Media/ImageMagick.php" );

/**
 * Class ImageMagickTest
 *
 * Allow testing imagemagick media plugin.
 * See configuration on tests/conf.
 *
 */
class ImageMagickTest extends TestCase {

	protected $im_plugin = null;
	protected $prophet = null;

	protected function setUp(): void {
		$this->im_plugin = new WLPlugMediaImageMagick();
		$this->im_plugin->register();
		$this->im_plugin->setBasePath('/usr/bin');
	}
	
	# -------------------------------------------------------
	public function testConvertCmd() {
		$command = $this->im_plugin->command( 'convert' );
		$this->assertEquals( '/usr/bin/convert', $command );
	}

	public function testIdentifyCmd() {
		$command = $this->im_plugin->command( 'identify' );
		$this->assertEquals( '/usr/bin/identify', $command );
	}

	public function testWhateverCmd() {
		$command = $this->im_plugin->command( 'whatever' );
		$this->assertEquals( '/usr/bin/whatever', $command );
	}

	public function testMissingCmdWithEmptyArgs() {
		$command = $this->im_plugin->commandWithDefaultArgs( 'whatever' );
		$this->assertEquals( '/usr/bin/whatever ', $command );
	}

	public function testConvertCmdWithDefaultArgs() {
		$command = $this->im_plugin->commandWithDefaultArgs( 'convert' );
		$this->assertEquals( '/usr/bin/convert -limit thread 1', $command );
	}

	/**
	 * First attempt for unit testing and mocks.
	 */
	public function testCheckStatus() {
		$imagemagick_plugin = $this->getMockBuilder( WLPlugMediaImageMagick::class )->onlyMethods( [ 'register' ] )
		                           ->getMock();
		$imagemagick_plugin->method( 'register' )->willReturn( array( 'info' => 1 ) );
		$status = $imagemagick_plugin->checkStatus();

		$this->assertIsArray( $status );
		$this->assertTrue( $status['available'] );
	}
}
