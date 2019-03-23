<?php
/** ---------------------------------------------------------------------
 * tests/lib/Cache/ExternalCacheTest.php: External cache test cases
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


require_once(__CA_LIB_DIR__.'/Cache/ExternalCache.php');

class ExternalCacheTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		ExternalCache::flush('default'); // might have side-effects on other tests?
		ExternalCache::flush('barNamespace');
		ExternalCache::setInvalidationMode(Stash\Invalidation::NONE);
	}

	public function testAccessNonExistingItem(){

		$vm_ret = ExternalCache::fetch('foo', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::fetch('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

		$vm_ret = ExternalCache::contains('bar');
		$this->assertFalse($vm_ret, 'Checking for existence of a non-existing cache item should return false');

	}

	public function testDeleteNonExistingItem(){
		$vm_ret = ExternalCache::delete('foo');
		//$this->assertFalse($vm_ret, 'Removing a non-existing item is not possible');
	}

	public function testAccessNonExistingItemWithExistingCache() {

		$vm_ret = ExternalCache::save('foo', array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::fetch('bar', 'barNamespace');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');

		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::fetch('bar');
		$this->assertNull($vm_ret, 'Should not be able to access non-existing cache item');
	}

	public function testSetAndfetch() {
		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = ExternalCache::save('foo',  array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key we just set');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have old key');
		$this->assertEquals(array('bar' => 'foo'), $vm_ret, 'Cache item should reflect the overwrite');
	}

	public function testSetAndfetchWithNamespace() {
		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::contains('foo');
		$this->assertFalse($vm_ret, 'The key should not exist in an unused namespace');

		$vm_ret = ExternalCache::fetch('foo', 'barNamespace');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');
	}

	public function testSetGetReplaceDeleteCycle() {
		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'));
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		$vm_ret = ExternalCache::contains('foo');
		$this->assertTrue($vm_ret, 'Checking for existence of a key we just set should return true');

		$vm_ret = ExternalCache::fetch('foo');
		$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
		$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

		$vm_ret = ExternalCache::save('foo', array('bar' => 'foo'));
		$this->assertTrue($vm_ret, 'Replacing item in cache should return true');

		$vm_ret = ExternalCache::fetch('foo');
		$this->assertArrayHasKey('bar', $vm_ret, 'Returned array should have key');
		$this->assertArrayNotHasKey('foo', $vm_ret, 'Returned array should not have replaced key');

		$vm_ret = ExternalCache::delete('foo');
		$this->assertTrue($vm_ret, 'Removing an existing key should return true');

		$vm_ret = ExternalCache::fetch('foo');
		$this->assertNull($vm_ret, 'Should not return anything after deleting');

		$vm_ret = ExternalCache::contains('foo');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testFlush() {
		$vm_ret = ExternalCache::save('foo',  array('foo' => 'bar'), 'barNamespace');
		$this->assertTrue($vm_ret, 'Setting item in cache should return true');

		ExternalCache::flush('barNamespace');

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'Should not return anything after deleting');
	}

	public function testManyKeys() {
		for($i=1; $i<10; $i++) {
			$vm_ret = ExternalCache::save($i, $i, 'barNamespace');
			$this->assertTrue($vm_ret, 'Setting item in cache should return true');

			$vm_ret = ExternalCache::contains($i, 'barNamespace');
			$this->assertTrue($vm_ret, 'The key we just set should exist');

			$vm_ret = ExternalCache::fetch($i, 'barNamespace');
			$this->assertEquals($i, $vm_ret, 'The value we set should be returned');
		}

		// after all that the first key should still be around

		$vm_ret = ExternalCache::contains(1, 'barNamespace');
		$this->assertTrue($vm_ret, 'The first key should still exist');

		$vm_ret = ExternalCache::fetch(1, 'barNamespace');
		$this->assertEquals(1, $vm_ret, 'The first value should still be correct');

		ExternalCache::flush();
	}

	public function testWeirdKeys() {
		// we could try to carefully engineer potentially dangerous
		// cache keys (which may translate into on-disk file names depending
		// on the selected backend) but this random selection should do for now.
		$va_weird_chars = array('\\', '/', '_', '?', '-', '>', '<', '|', '{', '}', '[', ']', '(', ')', '$', '#', ':', ';', '"', '\'');

		for($i=0; $i<20; $i++) {
			$vm_ret = shuffle($va_weird_chars);
			$this->assertTrue($vm_ret, 'Shuffling should not fail');

			$vs_key = join('', $va_weird_chars);

			$vm_ret = ExternalCache::contains($vs_key);
			$this->assertFalse($vm_ret, 'Should not return anything for a new key');
			$vm_ret = ExternalCache::save($vs_key, array('foo' => 'bar'));
			$this->assertTrue($vm_ret, 'Setting new item in cache should return true');
			$vm_ret = ExternalCache::fetch($vs_key);
			$this->assertArrayHasKey('foo', $vm_ret, 'Returned array should have key');
			$this->assertArrayNotHasKey('bar', $vm_ret, 'Returned array should not have key we did not set');
			$this->assertEquals(array('foo' => 'bar'), $vm_ret, 'Cache item should not change');

			ExternalCache::flush();
		}
	}

	/**
	 * @expectedException ExternalCacheInvalidParameterException
	 */
	public function testInvalidNameSpace() {
		ExternalCache::save('foo', 'data1', 'this is invalid');
	}

	/**
	 * @expectedException ExternalCacheInvalidParameterException
	 */
	public function testInvalidKey() {
		ExternalCache::save('', 'data1', 'this is invalid');
	}

	public function testTTL() {
		ExternalCache::save('foo', array(), 'barNamespace', 1);
		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'The key we just set should exist');

		$vm_ret = ExternalCache::fetch('foo', 'barNamespace');
		$this->assertEquals(array(), $vm_ret, 'The value we set should be returned');

		sleep(2);

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'The key should have expired by now');
	}

	public function testLongerTTL() {
		ExternalCache::save('foo', array(), 'barNamespace', 3);
		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'The key we just set should exist');

		$vm_ret = ExternalCache::fetch('foo', 'barNamespace');
		$this->assertEquals(array(), $vm_ret, 'The value we set should be returned');

		sleep(1);

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertTrue($vm_ret, 'The key should still be there');

		sleep(3);

		$vm_ret = ExternalCache::contains('foo', 'barNamespace');
		$this->assertFalse($vm_ret, 'The key should have expired by now');
	}
}
