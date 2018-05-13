<?php
/** ---------------------------------------------------------------------
 * tests/lib/Cache/MemoryCacheTest.php: Memory cache test cases
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


require_once(__CA_LIB_DIR__.'/Cache/MemoryCache.php');

class MemoryCacheTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		MemoryCache::flush('default');
		MemoryCache::flush('barNamespace');
	}

	public function testAccessNonExistingItem(){

		$vm_ret = MemoryCache::fetch('foo', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::fetch('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

		$vm_ret = MemoryCache::contains('bar');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

	}

	public function testDeleteNonExistingItem(){
		$vm_ret = MemoryCache::delete('foo');
		$this->assertFalse($vm_ret, 'Removing a non-existing item is not possible');
	}

	public function testAccessNonExistingItemWithExistingCache() {

		$vm_ret = MemoryCache::save('foo', array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::fetch('bar', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::fetch('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');
	}

	public function testSetAndfetch() {
		$vm_ret = MemoryCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = MemoryCache::save('foo',  array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key we just set');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have old key');
		$this->assertEquals(array('bar' => 'foo'), $vm_ret, 'Cache item should reflect the overwrite');
	}

	public function testSetAndfetchWithNamespace() {
		$vm_ret = MemoryCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::contains('foo');
		$this->assertFalse($vm_ret, 'The key should not exist in an unused namespace');

		$vm_ret = MemoryCache::fetch('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetGetReplaceDeleteCycle() {
		$vm_ret = MemoryCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = MemoryCache::save('foo', array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Replacing item in cache should return true');

		$vm_ret = MemoryCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have replaced key');

		$vm_ret = MemoryCache::delete('foo');
		$this->assertTrue($vm_ret, 'Removing an existing key should return true');

		$vm_ret = MemoryCache::fetch('foo');
		$this->assertNull($vm_ret, 'Should not return anything after deleting');

		$vm_ret = MemoryCache::contains('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testSetAndGetNull() {
		$vm_ret = MemoryCache::save('foo',  null);
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');
	}

	public function testFlush() {
		$vm_ret = MemoryCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		MemoryCache::flush();

		$vm_ret = MemoryCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testFlushDifferentNS() {
		$vm_ret = MemoryCache::save('foo', 'data1', 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::save('bar', 'data2');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		MemoryCache::flush('default');

		$vm_ret = MemoryCache::contains('bar');
		$this->assertFalse($vm_ret, 'Item should be gone after flushing default namespace.');

		$vm_ret = MemoryCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Item should still be there after flushing different namespace');
	}

	/**
	 * @expectedException MemoryCacheInvalidParameterException
	 */
	public function testInvalidNameSpace() {
		MemoryCache::save('foo', 'data1', null);
	}

	/**
	 * @expectedException MemoryCacheInvalidParameterException
	 */
	public function testInvalidKey() {
		MemoryCache::save('', 'data1', 'barNamespace');
	}

}
