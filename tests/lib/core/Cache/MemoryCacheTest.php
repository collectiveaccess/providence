<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/Cache/MemoryCacheTest.php: Memory cache test cases
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


require_once(__CA_LIB_DIR__.'/core/Cache/MemoryCache.php');

class MemoryCacheTest extends PHPUnit_Framework_TestCase {

	public function testAccessNonExistingItem(){

		$vm_ret = MemoryCache::getItem('foo', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::getItem('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::hasItem('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

		$vm_ret = MemoryCache::hasItem('bar');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

	}

	public function testDeleteNonExistingItem(){
		$vm_ret = MemoryCache::removeItem('foo');
		$this->assertFalse($vm_ret, 'Removing a non-existing item is not possible');
	}

	public function testAccessNonExistingItemWithExistingCache() {

		$vm_ret = MemoryCache::setItem('foo', array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::getItem('bar', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = MemoryCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::getItem('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');
	}

	public function testSetAndGetItem() {
		$vm_ret = MemoryCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::hasItem('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetAndGetItemWithNamespace() {
		$vm_ret = MemoryCache::setItem('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::hasItem('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::getItem('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetGetReplaceDeleteCycle() {
		$vm_ret = MemoryCache::setItem('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = MemoryCache::hasItem('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = MemoryCache::replaceItem('foo', array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Replacing item in cache should return true');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have replaced key');

		$vm_ret = MemoryCache::removeItem('foo');
		$this->assertTrue($vm_ret, 'Removing an existing key should return true');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertNull($vm_ret, 'Should not return anything after deleting');

		$vm_ret = MemoryCache::hasItem('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testSetGetReplaceDeleteCycleWithMultipleItems() {
		$va_data = array(
			'foo' => 'data1',
			'bar' => 'data2'
		);

		// returns array of not stored keys
		$vm_ret = MemoryCache::setItems($va_data);
		$this->assertEmpty($vm_ret, 'Array of not stored keys should be empty');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertEquals('data1', $vm_ret, 'Cache item should not change');
		$vm_ret = MemoryCache::getItem('bar');
		$this->assertEquals('data2', $vm_ret, 'Cache item should not change');

		$vm_ret = MemoryCache::getItems(array('foo', 'bar'));
		$this->assertEquals($va_data, $vm_ret, 'Cache items should not change');

		$va_new_data = array(
			'foo' => 'data_new1',
			'bar' => 'data_new2',
			'new_key' => 'data_new3'
		);

		$vm_ret = MemoryCache::replaceItems($va_new_data);
		$this->assertEquals(array('new_key'), $vm_ret, 'Nonexisting cache keys cannot be replaced');

		$vm_ret = MemoryCache::getItem('foo');
		$this->assertEquals('data_new1', $vm_ret, 'Cache item was replaced');
		$vm_ret = MemoryCache::getItem('bar');
		$this->assertEquals('data_new2', $vm_ret, 'Cache item was replaced');

		$vm_ret = MemoryCache::hasItem('new_key');
		$this->assertFalse($vm_ret, 'Key should not exist');

		// returns array of found keys
		$vm_ret = MemoryCache::hasItems(array('foo', 'bar', 'new_key'));
		$this->assertContains('foo', $vm_ret, 'Existing key must be part of the array');
		$this->assertContains('bar', $vm_ret, 'Existing key must be part of the array');
		$this->assertNotContains('new_key', $vm_ret, 'Nonexisting key must not be part of the array');

		$vm_ret = MemoryCache::removeItems(array('foo', 'bar'));
		$this->assertEmpty($vm_ret, 'Removing existing keys should not fail');

		$vm_ret = MemoryCache::hasItem('foo');
		$this->assertFalse($vm_ret, 'Key should not exist');

		$vm_ret = MemoryCache::hasItem('bar');
		$this->assertFalse($vm_ret, 'Key should not exist');
	}
}
