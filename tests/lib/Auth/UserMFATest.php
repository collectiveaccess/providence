<?php
/** ---------------------------------------------------------------------
 * tests/lib/Auth/UserMFATest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/Auth/MFA/UserMFA.php');
require_once(__CA_MODELS_DIR__.'/ca_users.php');

/**
 * Requires mfa_enabled in tests/conf/authentication.conf and __CA_MFA_ENCRYPTION_KEY__ in tests/setup-tests.php
 */
class UserMFATest extends TestCase {
	# -------------------------------------------------------
	private $user;
	# -------------------------------------------------------
	protected function setUp() : void {
		$this->user = new ca_users();
		$this->user->set([
			'user_name' => 'unit_test_mfa_user_'.uniqid(),
			'password' => 'Topsecret1_#',
			'email' => 'mfa@example.com',
			'active' => 1,
			'userclass' => 0,
			'fname' => 'Test',
			'lname' => 'User'
		]);
		$this->assertTrue((bool)$this->user->insert(), 'Could not create test user: '.join('; ', $this->user->getErrors()));
	}
	# -------------------------------------------------------
	protected function tearDown() : void {
		if($user_id = (int)$this->user->getPrimaryKey()) {
			UserMFA::disable($user_id);
			$db = new Db();
			$db->query("DELETE FROM ca_users WHERE user_id = ?", [$user_id]);
		}
	}
	# -------------------------------------------------------
	/**
	 * Set up an authenticator app for test user and return the secret and recovery codes
	 */
	private function setUpTOTP() : array {
		$user_id = (int)$this->user->getPrimaryKey();
		$secret = UserMFA::beginTOTPSetup($user_id);

		// Use the previous step so verification tests can use the current one
		$codes = UserMFA::confirmTOTPSetup($user_id, TOTP::codeForStep($secret, TOTP::timeStep() - 1));
		$this->assertIsArray($codes);
		return [$secret, $codes];
	}
	# -------------------------------------------------------
	public function testAvailable() {
		$this->assertTrue(UserMFA::isAvailable());
	}
	# -------------------------------------------------------
	public function testSetupRequiresConfirmation() {
		$user_id = (int)$this->user->getPrimaryKey();
		$this->assertFalse(UserMFA::isEnabledForUser($user_id));

		$secret = UserMFA::beginTOTPSetup($user_id);
		$this->assertFalse(UserMFA::isEnabledForUser($user_id), 'Two-factor authentication should not be on until confirmed');
		$this->assertEquals(UserMFA::RESULT_NOT_ENABLED, UserMFA::verify($user_id, TOTP::codeForStep($secret, TOTP::timeStep())));

		$this->assertNull(UserMFA::confirmTOTPSetup($user_id, '000000' === TOTP::codeForStep($secret, TOTP::timeStep()) ? '111111' : '000000'));
		$this->assertFalse(UserMFA::isEnabledForUser($user_id));

		$codes = UserMFA::confirmTOTPSetup($user_id, TOTP::codeForStep($secret, TOTP::timeStep()));
		$this->assertIsArray($codes);
		$this->assertCount(10, $codes);
		$this->assertTrue(UserMFA::isEnabledForUser($user_id));
		$this->assertEquals(10, UserMFA::numRecoveryCodes($user_id));

		$this->expectException(ApplicationException::class);
		UserMFA::beginTOTPSetup($user_id);
	}
	# -------------------------------------------------------
	public function testSecretIsEncrypted() {
		[$secret, ] = $this->setUpTOTP();

		$db = new Db();
		$qr = $db->query("SELECT secret FROM ca_user_mfa WHERE user_id = ?", [(int)$this->user->getPrimaryKey()]);
		$this->assertTrue($qr->nextRow());
		$this->assertStringNotContainsString($secret, $qr->get('secret'));
	}
	# -------------------------------------------------------
	public function testVerifyTOTP() {
		$user_id = (int)$this->user->getPrimaryKey();
		[$secret, ] = $this->setUpTOTP();
		$code = TOTP::codeForStep($secret, TOTP::timeStep());

		$this->assertEquals(UserMFA::RESULT_OK, UserMFA::verify($user_id, $code));
		$this->assertEquals(UserMFA::RESULT_INVALID, UserMFA::verify($user_id, $code), 'Code should only work once');
		$this->assertEquals(UserMFA::RESULT_INVALID, UserMFA::verify($user_id, 'not a code'));
	}
	# -------------------------------------------------------
	public function testRecoveryCodesWorkOnce() {
		$user_id = (int)$this->user->getPrimaryKey();
		[, $codes] = $this->setUpTOTP();

		$this->assertMatchesRegularExpression('!^[A-Z0-9]{5}-[A-Z0-9]{5}$!', $codes[0]);
		$this->assertEquals(UserMFA::RESULT_OK, UserMFA::verify($user_id, $codes[0]));
		$this->assertEquals(9, UserMFA::numRecoveryCodes($user_id));
		$this->assertEquals(UserMFA::RESULT_INVALID, UserMFA::verify($user_id, $codes[0]), 'Recovery code should only work once');

		// Case and separator don't matter
		$this->assertEquals(UserMFA::RESULT_OK, UserMFA::verify($user_id, strtolower(str_replace('-', ' ', $codes[1]))));

		// Regenerating replaces old codes
		$new_codes = UserMFA::generateRecoveryCodes($user_id);
		$this->assertEquals(UserMFA::RESULT_INVALID, UserMFA::verify($user_id, $codes[2]));
		$this->assertEquals(UserMFA::RESULT_OK, UserMFA::verify($user_id, $new_codes[0]));
	}
	# -------------------------------------------------------
	public function testRateLimit() {
		$user_id = (int)$this->user->getPrimaryKey();
		[$secret, ] = $this->setUpTOTP();
		$good = TOTP::codeForStep($secret, TOTP::timeStep());
		$bad = ($good === '000000') ? '111111' : '000000';

		for($i = 0; $i < 5; $i++) {
			$this->assertEquals(UserMFA::RESULT_INVALID, UserMFA::verify($user_id, $bad));
		}
		$this->assertTrue(UserMFA::failuresExceeded($user_id));
		$this->assertEquals(UserMFA::RESULT_RATE_LIMITED, UserMFA::verify($user_id, $good), 'Valid codes should be rejected once the limit is reached');
	}
	# -------------------------------------------------------
	public function testDisable() {
		$user_id = (int)$this->user->getPrimaryKey();
		$this->setUpTOTP();

		$this->assertTrue(UserMFA::disable($user_id));
		$this->assertFalse(UserMFA::isEnabledForUser($user_id));
		$this->assertEquals(0, UserMFA::numRecoveryCodes($user_id));
	}
	# -------------------------------------------------------
}
