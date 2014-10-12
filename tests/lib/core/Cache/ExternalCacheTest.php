<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/Cache/ExternalCacheTest.php: External cache test cases
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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


require_once(__CA_LIB_DIR__.'/core/Cache/ExternalCache.php');

class ExternalCacheTest extends PHPUnit_Framework_TestCase {

	public function setUp(){
		ExternalCache::flush();
		ExternalCache::flush('barNamespace');
	}

	public function tearDown(){
		ExternalCache::flush();
		ExternalCache::flush('barNamespace');
	}

	public function testAccessNonExistingItem(){

		$vm_ret = ExternalCache::getItem('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::getItem('bar');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::hasItem('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

		$vm_ret = ExternalCache::hasItem('bar');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

	}

	public function testDeleteNonExistingItem(){
		$vm_ret = ExternalCache::removeItem('foo');
		$this->assertFalse($vm_ret, 'Removing a non-existing item is not possible');
	}

	public function testAccessNonExistingItemWithExistingCache() {

		$vm_ret = ExternalCache::setItem('foo', array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::getItem('bar', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::getItem('bar');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');
	}

	public function testSetAndGetItem() {
		$vm_ret = ExternalCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::hasItem('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::getItem('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetAndGetItemWithNamespace() {
		$vm_ret = ExternalCache::setItem('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::hasItem('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::getItem('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetGetReplaceDeleteCycle() {
		$vm_ret = ExternalCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::hasItem('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::getItem('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = ExternalCache::replaceItem('foo', array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Replacing item in cache should return true');

		$vm_ret = ExternalCache::getItem('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have replaced key');

		$vm_ret = ExternalCache::removeItem('foo');
		$this->assertTrue($vm_ret, 'Removing an existing key should return true');

		$vm_ret = ExternalCache::getItem('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');

		$vm_ret = ExternalCache::hasItem('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

}