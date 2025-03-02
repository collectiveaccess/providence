<?php
/**
 * Copyright (c) Orestes Sanchez Benavente <orestes@estotienearreglo.es> 2020
 */
use PHPUnit\Framework\TestCase;
 
require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");

class HtmlFormHelpersTest extends TestCase {
	# -------------------------------------------------------
	public function testImageUsesDefaultTileSize() {
		$result = caHTMLImage('http://example.com/image.tpc', array(
		));
		$this->assertStringContainsString('"tilesize":256', $result);
	}
	# -------------------------------------------------------
	public function testImageUsesCustomTileSize() {
		$result = caHTMLImage('http://example.com/image.tpc', array(
			'tile_width' => 512
		));
		$this->assertStringContainsString('"tilesize":512', $result);
	}
	# -------------------------------------------------------
	public function testImageDoesNotUseHeightTileSize() {
		$result = caHTMLImage('http://example.com/image.tpc', array(
			'tile_height' => 1024
		));
		// Uses default tilesize
		$this->assertStringContainsString('"tilesize":256', $result);
	}
	# -------------------------------------------------------
	public function testImagePrefersWidthTileSize() {
		$result = caHTMLImage('http://example.com/image.tpc', array(
			'tile_width' => 1024,
			'tile_height' => 512
		));
		// Uses default tilesize
		$this->assertStringContainsString('"tilesize":1024', $result);
	}
	# -------------------------------------------------------
	public function testJpegImageDoesNotUseTiles() {
		$result = caHTMLImage('http://example.com/image.jpg', array());
		$this->assertStringNotContainsString('"tilesize"', $result);
	}
	# -------------------------------------------------------
	# -------------------------------------------------------
}
