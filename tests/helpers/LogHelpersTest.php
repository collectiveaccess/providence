<?php
/** ---------------------------------------------------------------------
 * tests/helpers/LogHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
use PHPUnit\Framework\TestCase;

require_once(__CA_APP_DIR__."/helpers/logHelpers.php");

class LogHelpersTest extends TestCase {
	# -------------------------------------------------------
	/**
	 * Test that caLogEvent does not raise a notice when HTTP_X_REQUEST_ID is not set
	 */
	public function testCaLogEventWithoutRequestId() {
		// Ensure the header is not set
		if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
			$saved_request_id = $_SERVER['HTTP_X_REQUEST_ID'];
			$request_id_was_set = true;
		} else {
			$request_id_was_set = false;
		}
		unset($_SERVER['HTTP_X_REQUEST_ID']);
		
		$temp_log_dir = sys_get_temp_dir() . '/ca_log_test_' . uniqid();
		mkdir($temp_log_dir, 0777, true);
		
		try {
			// This should not raise any notices or errors
			$result = caLogEvent('TEST', 'Test message without request ID', null, array('logDirectory' => $temp_log_dir));
			
			$this->assertTrue($result);
			
			// Verify the log file was created and contains the message
			$log_files = glob($temp_log_dir . '/log_*.txt');
			$this->assertCount(1, $log_files, 'Exactly one log file should be created');
			
			$log_content = file_get_contents($log_files[0]);
			$this->assertStringContainsString('[TEST] Test message without request ID', $log_content);
			// Request ID should NOT appear in the log
			$this->assertStringNotContainsString('req-abc-123', $log_content);
			
		} finally {
			// Restore the original request ID if it was set
			if ($request_id_was_set) {
				$_SERVER['HTTP_X_REQUEST_ID'] = $saved_request_id;
			}
			// Cleanup
			$this->_removeDirectory($temp_log_dir);
		}
	}
	# -------------------------------------------------------
	/**
	 * Test that caLogEvent includes the request ID when HTTP_X_REQUEST_ID header is present
	 */
	public function testCaLogEventWithRequestId() {
		// Set the request ID header
		$_SERVER['HTTP_X_REQUEST_ID'] = 'req-abc-123';
		
		$temp_log_dir = sys_get_temp_dir() . '/ca_log_test_' . uniqid();
		mkdir($temp_log_dir, 0777, true);
		
		try {
			$result = caLogEvent('TEST', 'Test message with request ID', null, array('logDirectory' => $temp_log_dir));
			
			$this->assertTrue($result);
			
			// Verify the log file was created and contains both the request ID and the message
			$log_files = glob($temp_log_dir . '/log_*.txt');
			$this->assertCount(1, $log_files, 'Exactly one log file should be created');
			
			$log_content = file_get_contents($log_files[0]);
			$this->assertStringContainsString('req-abc-123', $log_content);
			$this->assertStringContainsString('[TEST] req-abc-123 Test message with request ID', $log_content);
			
		} finally {
			unset($_SERVER['HTTP_X_REQUEST_ID']);
			$this->_removeDirectory($temp_log_dir);
		}
	}
	# -------------------------------------------------------
	/**
	 * Test that caLogEvent includes source when provided, along with request ID
	 */
	public function testCaLogEventWithRequestIdAndSource() {
		$_SERVER['HTTP_X_REQUEST_ID'] = 'req-xyz-789';
		
		$temp_log_dir = sys_get_temp_dir() . '/ca_log_test_' . uniqid();
		mkdir($temp_log_dir, 0777, true);
		
		try {
			$result = caLogEvent('TEST', 'Test message', 'MySource', array('logDirectory' => $temp_log_dir));
			
			$this->assertTrue($result);
			
			$log_files = glob($temp_log_dir . '/log_*.txt');
			$this->assertCount(1, $log_files);
			
			$log_content = file_get_contents($log_files[0]);
			$this->assertStringContainsString('[TEST] req-xyz-789 Test message (MySource)', $log_content);
			
		} finally {
			unset($_SERVER['HTTP_X_REQUEST_ID']);
			$this->_removeDirectory($temp_log_dir);
		}
	}
	# -------------------------------------------------------
	/**
	 * Test that caLogEvent includes source when provided, without request ID
	 */
	public function testCaLogEventWithoutRequestIdButWithSource() {
		if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
			$saved_request_id = $_SERVER['HTTP_X_REQUEST_ID'];
			$request_id_was_set = true;
		} else {
			$request_id_was_set = false;
		}
		unset($_SERVER['HTTP_X_REQUEST_ID']);
		
		$temp_log_dir = sys_get_temp_dir() . '/ca_log_test_' . uniqid();
		mkdir($temp_log_dir, 0777, true);
		
		try {
			$result = caLogEvent('TEST', 'Test message', 'MySource', array('logDirectory' => $temp_log_dir));
			
			$this->assertTrue($result);
			
			$log_files = glob($temp_log_dir . '/log_*.txt');
			$this->assertCount(1, $log_files);
			
			$log_content = file_get_contents($log_files[0]);
			$this->assertStringContainsString('[TEST] Test message (MySource)', $log_content);
			
		} finally {
			if ($request_id_was_set) {
				$_SERVER['HTTP_X_REQUEST_ID'] = $saved_request_id;
			}
			$this->_removeDirectory($temp_log_dir);
		}
	}
	# -------------------------------------------------------
	/**
	 * Helper to recursively remove a directory
	 */
	private function _removeDirectory($dir) {
		if (!is_dir($dir)) { return; }
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			is_dir($path) ? $this->_removeDirectory($path) : unlink($path);
		}
		rmdir($dir);
	}
	# -------------------------------------------------------
}
