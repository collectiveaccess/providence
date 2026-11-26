<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/MFA/TOTP.php : time-based one-time passcodes (RFC 6238)
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
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Generates and checks RFC 6238 time-based one-time passcodes using the parameters every common
 * authenticator app supports: HMAC-SHA1, 6 digits, 30 second time steps.
 */
class TOTP {
	# -------------------------------------------------------
	const DIGITS = 6;
	const PERIOD = 30;
	const SECRET_BYTES = 20;	// 160 bits, as recommended by RFC 4226

	const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	# -------------------------------------------------------
	/**
	 * Generate a new random secret
	 *
	 * @return string Base32-encoded secret
	 */
	public static function generateSecret() : string {
		return self::base32Encode(random_bytes(self::SECRET_BYTES));
	}
	# -------------------------------------------------------
	/**
	 * Return time step number for a Unix timestamp
	 *
	 * @param int|null $time Unix timestamp. Defaults to the current time.
	 *
	 * @return int
	 */
	public static function timeStep(?int $time=null) : int {
		return intdiv($time ?? time(), self::PERIOD);
	}
	# -------------------------------------------------------
	/**
	 * Calculate passcode for a time step
	 *
	 * @param string $secret Base32-encoded secret
	 * @param int $step Time step number
	 * @param int $digits Number of digits in passcode
	 *
	 * @return string Passcode, zero-padded to the requested number of digits
	 */
	public static function codeForStep(string $secret, int $step, int $digits=self::DIGITS) : string {
		$key = self::base32Decode($secret);
		if(!strlen($key)) { throw new \InvalidArgumentException(_t('Invalid TOTP secret')); }

		$hmac = hash_hmac('sha1', pack('J', $step), $key, true);
		$offset = ord($hmac[19]) & 0x0f;
		$value = ((ord($hmac[$offset]) & 0x7f) << 24)
			| (ord($hmac[$offset + 1]) << 16)
			| (ord($hmac[$offset + 2]) << 8)
			| ord($hmac[$offset + 3]);

		return str_pad((string)($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
	}
	# -------------------------------------------------------
	/**
	 * Check a passcode against the current time step and the steps on either side of it, to allow
	 * for clock drift. Passcodes for steps at or before $after are rejected, which prevents a passcode from being used twice.
	 *
	 * @param string $secret Base32-encoded secret
	 * @param string $code Passcode entered by user. Spaces and dashes are ignored.
	 * @param int $after Last time step successfully used. Passcodes for this step or earlier are rejected.
	 * @param int|null $time Unix timestamp to check against. Defaults to the current time.
	 * @param int $window Number of time steps either side of the current one to accept
	 *
	 * @return int|null Matching time step, or null if the passcode is not valid
	 */
	public static function verify(string $secret, string $code, int $after=0, ?int $time=null, int $window=1) : ?int {
		$code = preg_replace('![\s\-]+!', '', $code);
		if(!preg_match('!^[0-9]{'.self::DIGITS.'}$!', $code)) { return null; }

		$current = self::timeStep($time);
		$matched = null;

		// Check every step in the window, without stopping early, so timing doesn't reveal which step matched
		for($step = $current - $window; $step <= $current + $window; $step++) {
			if(($step > $after) && hash_equals(self::codeForStep($secret, $step), $code)) {
				$matched = $step;
			}
		}
		return $matched;
	}
	# -------------------------------------------------------
	/**
	 * Return otpauth:// URI used to add a secret to an authenticator app (usually shown as a QR code)
	 *
	 * @param string $secret Base32-encoded secret
	 * @param string $account Account name shown in the app (usually the user name)
	 * @param string $issuer Name of the system shown in the app
	 *
	 * @return string
	 */
	public static function provisioningUri(string $secret, string $account, string $issuer) : string {
		// Colons separate issuer from account in the label, so they can't appear in either
		$issuer = str_replace(':', '', $issuer);
		$account = str_replace(':', '', $account);

		return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account).'?'.http_build_query([
			'secret' => $secret,
			'issuer' => $issuer,
			'algorithm' => 'SHA1',
			'digits' => self::DIGITS,
			'period' => self::PERIOD
		], '', '&', PHP_QUERY_RFC3986);
	}
	# -------------------------------------------------------
	/**
	 * Base32-encode (RFC 4648) binary data, without padding
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public static function base32Encode(string $data) : string {
		$bits = '';
		foreach(str_split($data) as $c) {
			$bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
		}
		$out = '';
		foreach(str_split($bits, 5) as $chunk) {
			if(!strlen($chunk)) { continue; }
			$out .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
		}
		return $out;
	}
	# -------------------------------------------------------
	/**
	 * Decode Base32 (RFC 4648) string. Case, spaces and padding are ignored.
	 *
	 * @param string $data
	 *
	 * @return string Binary data, or an empty string if $data is not valid Base32
	 */
	public static function base32Decode(string $data) : string {
		$data = strtoupper(preg_replace('![\s=]+!', '', $data));
		if(!strlen($data) || (strspn($data, self::BASE32_ALPHABET) !== strlen($data))) { return ''; }

		$bits = '';
		foreach(str_split($data) as $c) {
			$bits .= str_pad(decbin(strpos(self::BASE32_ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
		}
		$out = '';
		foreach(str_split($bits, 8) as $byte) {
			if(strlen($byte) === 8) { $out .= chr(bindec($byte)); }	// leftover bits are padding
		}
		return $out;
	}
	# -------------------------------------------------------
}
