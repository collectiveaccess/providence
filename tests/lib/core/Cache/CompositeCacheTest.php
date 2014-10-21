<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/Cache/CompositeCacheTest.php: Composite cache test cases
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


require_once(__CA_LIB_DIR__.'/core/Cache/CompositeCache.php');

class CompositeCacheTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		CompositeCache::flush(); // might have side-effects on other tests?
	}

	public function testAccessNonExistingItem(){

		$vm_ret = CompositeCache::fetch('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = CompositeCache::fetch('bar');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = CompositeCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

		$vm_ret = CompositeCache::contains('bar');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

	}

	public function testAccessNonExistingItemWithExistingCache() {

		$vm_ret = CompositeCache::save('foo', array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::fetch('bar', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = CompositeCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::fetch('bar');
		$this->assertFalse($vm_ret, 'Should not be able to access non-existing cache item');
	}

	public function testSetAndfetch() {
		$vm_ret = CompositeCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = CompositeCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = CompositeCache::save('foo',  array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key we just set');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have old key');
		$this->assertEquals(array('bar' => 'foo'), $vm_ret, 'Cache item should reflect the overwrite');
	}

	public function testSetAndfetchWithNamespace() {
		$vm_ret = CompositeCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = CompositeCache::contains('foo');
		$this->assertFalse($vm_ret, 'The key should not exist in an unused namespace');

		$vm_ret = CompositeCache::fetch('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetAndFetchFromExternalCache() {
		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = CompositeCache::contains('foo');
		$this->assertFalse($vm_ret, 'The key should not exist in an unused namespace');

		$vm_ret = CompositeCache::fetch('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		// after we fetch it once using CompositeCache, it should be in memory
		$vm_ret = MemoryCache::fetch('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetGetReplaceDeleteCycle() {
		$vm_ret = CompositeCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = CompositeCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = CompositeCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = CompositeCache::save('foo', array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Replacing item in cache should return true');

		$vm_ret = CompositeCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have replaced key');

		$vm_ret = CompositeCache::delete('foo');
		$this->assertTrue($vm_ret, 'Removing an existing key should return true');

		$vm_ret = CompositeCache::fetch('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');

		$vm_ret = CompositeCache::contains('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testFlush() {
		$vm_ret = CompositeCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		CompositeCache::flush();

		$vm_ret = CompositeCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	/**
	 * @expectedException ExternalCacheInvalidParameterException
	 */
	public function testInvalidNameSpace() {
		CompositeCache::save('foo', 'data1', 'this is invalid');
	}

	/**
	 * @expectedException MemoryCacheInvalidParameterException
	 */
	public function testInvalidKey() {
		CompositeCache::save('', 'data1', 'this is invalid');
	}

}
