<?php
/** ---------------------------------------------------------------------
 * tests/lib/Auth/TOTPTest.php
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

require_once(__CA_LIB_DIR__.'/Auth/MFA/TOTP.php');

class TOTPTest extends TestCase {
	# -------------------------------------------------------
	/**
	 * Test vectors from RFC 6238 Appendix B (SHA1, 8 digits)
	 */
	public function testRFC6238Vectors() {
		$secret = TOTP::base32Encode('12345678901234567890');
		$vectors = [
			59 => '94287082',
			1111111109 => '07081804',
			1111111111 => '14050471',
			1234567890 => '89005924',
			2000000000 => '69279037',
			20000000000 => '65353130'
		];
		foreach($vectors as $time => $expected) {
			$this->assertEquals($expected, TOTP::codeForStep($secret, TOTP::timeStep($time), 8), "Wrong code for time {$time}");
		}
		// 6 digit codes are the last 6 digits
		$this->assertEquals('287082', TOTP::codeForStep($secret, TOTP::timeStep(59)));
	}
	# -------------------------------------------------------
	/**
	 * Test vectors from RFC 4648 section 10
	 */
	public function testBase32() {
		$vectors = ['f' => 'MY', 'fo' => 'MZXQ', 'foo' => 'MZXW6', 'foob' => 'MZXW6YQ', 'fooba' => 'MZXW6YTB', 'foobar' => 'MZXW6YTBOI'];
		foreach($vectors as $in => $out) {
			$this->assertEquals($out, TOTP::base32Encode($in));
			$this->assertEquals($in, TOTP::base32Decode($out));
			$this->assertEquals($in, TOTP::base32Decode(strtolower($out).'===='));
		}
		$this->assertEquals('', TOTP::base32Decode('ABC1'), 'Characters outside the alphabet should be rejected');
		$this->assertEquals('', TOTP::base32Decode(''));

		for($i = 0; $i < 50; $i++) {
			$data = random_bytes(random_int(1, 40));
			$this->assertEquals($data, TOTP::base32Decode(TOTP::base32Encode($data)));
		}
	}
	# -------------------------------------------------------
	public function testGenerateSecret() {
		$secret = TOTP::generateSecret();
		$this->assertEquals(TOTP::SECRET_BYTES, strlen(TOTP::base32Decode($secret)));
		$this->assertNotEquals($secret, TOTP::generateSecret());
	}
	# -------------------------------------------------------
	public function testVerifyAcceptsCurrentAndAdjacentSteps() {
		$secret = TOTP::generateSecret();
		$time = 1700000000;
		$step = TOTP::timeStep($time);

		$this->assertEquals($step, TOTP::verify($secret, TOTP::codeForStep($secret, $step), 0, $time));
		$this->assertEquals($step - 1, TOTP::verify($secret, TOTP::codeForStep($secret, $step - 1), 0, $time));
		$this->assertEquals($step + 1, TOTP::verify($secret, TOTP::codeForStep($secret, $step + 1), 0, $time));
		$this->assertNull(TOTP::verify($secret, TOTP::codeForStep($secret, $step - 2), 0, $time));
		$this->assertNull(TOTP::verify($secret, TOTP::codeForStep($secret, $step + 2), 0, $time));
	}
	# -------------------------------------------------------
	public function testVerifyRejectsReusedCodes() {
		$secret = TOTP::generateSecret();
		$time = 1700000000;
		$step = TOTP::timeStep($time);
		$code = TOTP::codeForStep($secret, $step);

		$this->assertEquals($step, TOTP::verify($secret, $code, $step - 1, $time));
		$this->assertNull(TOTP::verify($secret, $code, $step, $time), 'Code for last used step should be rejected');
		$this->assertNull(TOTP::verify($secret, TOTP::codeForStep($secret, $step - 1), $step, $time), 'Code for earlier step should be rejected');
	}
	# -------------------------------------------------------
	public function testVerifyInputFormats() {
		$secret = TOTP::generateSecret();
		$time = 1700000000;
		$step = TOTP::timeStep($time);
		$code = TOTP::codeForStep($secret, $step);

		$this->assertEquals($step, TOTP::verify($secret, substr($code, 0, 3).' '.substr($code, 3), 0, $time));
		$this->assertEquals($step, TOTP::verify($secret, substr($code, 0, 3).'-'.substr($code, 3), 0, $time));
		$this->assertNull(TOTP::verify($secret, '', 0, $time));
		$this->assertNull(TOTP::verify($secret, 'abcdef', 0, $time));
		$this->assertNull(TOTP::verify($secret, substr($code, 0, 5), 0, $time));
		$this->assertNull(TOTP::verify($secret, $code.'0', 0, $time));
	}
	# -------------------------------------------------------
	public function testProvisioningUri() {
		$uri = TOTP::provisioningUri('JBSWY3DPEHPK3PXP', 'jane.doe', 'My Archive: Collections');
		$this->assertEquals('otpauth://totp/My%20Archive%20Collections:jane.doe?secret=JBSWY3DPEHPK3PXP&issuer=My%20Archive%20Collections&algorithm=SHA1&digits=6&period=30', $uri);
	}
	# -------------------------------------------------------
}
