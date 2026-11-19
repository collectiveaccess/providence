<?php
/** ---------------------------------------------------------------------
 * app/lib/Auth/MFA/UserMFA.php : per-user two-factor authentication settings and verification
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
require_once(__CA_LIB_DIR__.'/Auth/MFA/TOTP.php');

/**
 * Stores and checks two-factor authentication settings for users.
 *
 * Two-factor authentication is opt-in per user and only applies when "mfa_enabled" is set in authentication.conf.
 * Authenticator app secrets are encrypted with the key defined as __CA_MFA_ENCRYPTION_KEY__ in setup.php, so
 * a copy of the database alone is not enough to generate passcodes. Recovery codes are stored as password hashes.
 */
class UserMFA {
	# -------------------------------------------------------
	const TYPE_TOTP = 'totp';

	const RESULT_OK = 'ok';
	const RESULT_INVALID = 'invalid';
	const RESULT_RATE_LIMITED = 'rate_limited';
	const RESULT_NOT_ENABLED = 'not_enabled';

	// Recovery code characters. Easily confused characters (0/O, 1/I/L) are left out.
	const RECOVERY_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
	const RECOVERY_CODE_LENGTH = 10;
	# -------------------------------------------------------
	/**
	 * Is two-factor authentication turned on in authentication.conf?
	 *
	 * @return bool
	 */
	public static function isEnabled() : bool {
		return (bool)Configuration::load('authentication.conf')->get('mfa_enabled');
	}
	# -------------------------------------------------------
	/**
	 * Can users set up two-factor authentication? Requires it to be turned on in authentication.conf and a
	 * valid encryption key in setup.php.
	 *
	 * @return bool
	 */
	public static function isAvailable() : bool {
		return self::isEnabled() && !is_null(self::encryptionKey());
	}
	# -------------------------------------------------------
	/**
	 * Does user have to enter a second factor to log in with their password? Deliberately doesn't check
	 * for an encryption key, so that a missing key locks out users who set up two-factor authentication
	 * rather than letting them in with only a password.
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function isEnabledForUser(int $user_id) : bool {
		if(!self::isEnabled() || ($user_id <= 0)) { return false; }

		$db = new Db();
		$qr = $db->query("
			SELECT mfa_id FROM ca_user_mfa WHERE user_id = ? AND mfa_type = ? AND enabled_on IS NOT NULL
		", [$user_id, self::TYPE_TOTP]);
		return $qr && $qr->nextRow();
	}
	# -------------------------------------------------------
	# Setup
	# -------------------------------------------------------
	/**
	 * Start setting up an authenticator app for user. Generates a new secret, which is not used for logins
	 * until confirmed with UserMFA::confirmTOTPSetup(). Starting again replaces any unconfirmed secret.
	 *
	 * @param int $user_id
	 *
	 * @return string Base32-encoded secret, to be shown to user as a QR code and as text
	 * @throws ApplicationException if two-factor authentication is not available, or is already set up for user
	 */
	public static function beginTOTPSetup(int $user_id) : string {
		if(!self::isAvailable()) { throw new ApplicationException(_t('Two-factor authentication is not available')); }
		if(self::isEnabledForUser($user_id)) { throw new ApplicationException(_t('Two-factor authentication is already set up for this user')); }

		$secret = TOTP::generateSecret();

		$db = new Db();
		$db->query("DELETE FROM ca_user_mfa WHERE user_id = ? AND mfa_type = ?", [$user_id, self::TYPE_TOTP]);
		if(!$db->query("
			INSERT INTO ca_user_mfa (user_id, mfa_type, secret, created_on, enabled_on, last_used_step)
			VALUES (?, ?, ?, ?, NULL, 0)
		", [$user_id, self::TYPE_TOTP, self::encrypt($secret, $user_id), time()])) {
			throw new ApplicationException(_t('Could not save two-factor authentication settings: %1', join('; ', $db->getErrors())));
		}
		return $secret;
	}
	# -------------------------------------------------------
	/**
	 * Finish setting up an authenticator app, once user has entered a passcode from it. Turns on two-factor
	 * authentication for user and generates a new set of recovery codes.
	 *
	 * @param int $user_id
	 * @param string $code Passcode from authenticator app
	 *
	 * @return array|null List of recovery codes to show to user (only available now), or null if passcode is not valid
	 */
	public static function confirmTOTPSetup(int $user_id, string $code) : ?array {
		if(!self::isAvailable()) { return null; }
		if(!($row = self::getTOTPRow($user_id)) || !is_null($row['enabled_on'])) { return null; }
		if(is_null($secret = self::decrypt($row['secret'], $user_id))) { return null; }
		if(is_null($step = TOTP::verify($secret, $code))) { return null; }

		$db = new Db();
		$db->query("
			UPDATE ca_user_mfa SET enabled_on = ?, last_used_step = ? WHERE mfa_id = ? AND enabled_on IS NULL
		", [time(), $step, (int)$row['mfa_id']]);
		if($db->affectedRows() !== 1) { return null; }

		$codes = self::generateRecoveryCodes($user_id);
		self::log('MFA', $user_id, _t('Two-factor authentication turned on'));
		return $codes;
	}
	# -------------------------------------------------------
	/**
	 * Return otpauth:// URI for adding secret to an authenticator app
	 *
	 * @param ca_users $user
	 * @param string $secret Base32-encoded secret
	 *
	 * @return string
	 */
	public static function provisioningUri(ca_users $user, string $secret) : string {
		$issuer = Configuration::load('authentication.conf')->get('mfa_issuer') ?: Configuration::load()->get('app_display_name');
		return TOTP::provisioningUri($secret, (string)$user->get('user_name'), (string)$issuer ?: 'CollectiveAccess');
	}
	# -------------------------------------------------------
	/**
	 * Turn off two-factor authentication for user, removing their authenticator app secret and recovery codes
	 *
	 * @param int $user_id
	 * @param array $options Options include:
	 *		reason = Text added to log entry, eg. to note that an administrator reset two-factor authentication. [Default is null]
	 *
	 * @return bool
	 */
	public static function disable(int $user_id, ?array $options=null) : bool {
		$db = new Db();
		$was_enabled = self::isEnabledForUser($user_id);
		$ok = $db->query("DELETE FROM ca_user_mfa_recovery_codes WHERE user_id = ?", [$user_id])
			&& $db->query("DELETE FROM ca_user_mfa WHERE user_id = ?", [$user_id]);

		if($ok) {
			self::clearFailures($user_id);
			if($was_enabled) {
				$reason = caGetOption('reason', $options, null);
				self::log('MFA', $user_id, _t('Two-factor authentication turned off').($reason ? " ({$reason})" : ''));
			}
		}
		return (bool)$ok;
	}
	# -------------------------------------------------------
	# Recovery codes
	# -------------------------------------------------------
	/**
	 * Replace user's recovery codes with a new set
	 *
	 * @param int $user_id
	 *
	 * @return array List of codes, formatted for display as XXXXX-XXXXX
	 */
	public static function generateRecoveryCodes(int $user_id) : array {
		$count = (int)Configuration::load('authentication.conf')->get('mfa_recovery_code_count');
		if($count <= 0) { $count = 10; }

		$db = new Db();
		$db->query("DELETE FROM ca_user_mfa_recovery_codes WHERE user_id = ?", [$user_id]);

		$codes = [];
		$alphabet_len = strlen(self::RECOVERY_CODE_ALPHABET);
		for($i = 0; $i < $count; $i++) {
			$code = '';
			for($j = 0; $j < self::RECOVERY_CODE_LENGTH; $j++) {
				$code .= self::RECOVERY_CODE_ALPHABET[random_int(0, $alphabet_len - 1)];
			}
			$db->query("
				INSERT INTO ca_user_mfa_recovery_codes (user_id, code_hash, created_on) VALUES (?, ?, ?)
			", [$user_id, password_hash($code, PASSWORD_DEFAULT), time()]);
			$codes[] = substr($code, 0, 5).'-'.substr($code, 5);
		}
		return $codes;
	}
	# -------------------------------------------------------
	/**
	 * Return number of unused recovery codes user has left
	 *
	 * @param int $user_id
	 *
	 * @return int
	 */
	public static function numRecoveryCodes(int $user_id) : int {
		$db = new Db();
		$qr = $db->query("SELECT count(*) c FROM ca_user_mfa_recovery_codes WHERE user_id = ?", [$user_id]);
		return ($qr && $qr->nextRow()) ? (int)$qr->get('c') : 0;
	}
	# -------------------------------------------------------
	# Verification
	# -------------------------------------------------------
	/**
	 * Check second factor entered at login. Accepts either a passcode from user's authenticator app
	 * or one of their recovery codes. Recovery codes can only be used once. Failed attempts are
	 * limited per user (mfa_max_failures_per_user within login_rate_limit_window, in authentication.conf).
	 *
	 * @param int $user_id
	 * @param string $code
	 *
	 * @return string One of the UserMFA::RESULT_* constants
	 */
	public static function verify(int $user_id, string $code) : string {
		if(!self::isEnabledForUser($user_id)) { return self::RESULT_NOT_ENABLED; }
		if(self::failuresExceeded($user_id)) {
			self::log('LOGF', $user_id, _t('Rejected two-factor authentication attempt: too many failed attempts'));
			return self::RESULT_RATE_LIMITED;
		}

		$normalized = strtoupper(preg_replace('![^A-Za-z0-9]+!', '', $code));
		if(preg_match('!^[0-9]{'.TOTP::DIGITS.'}$!', $normalized)) {
			$ok = self::verifyTOTP($user_id, $normalized);
			$method = _t('authenticator app');
		} elseif(strlen($normalized) === self::RECOVERY_CODE_LENGTH) {
			$ok = self::verifyRecoveryCode($user_id, $normalized);
			$method = _t('recovery code');
		} else {
			$ok = false;
			$method = null;
		}

		if(!$ok) {
			self::recordFailure($user_id);
			self::log('LOGF', $user_id, _t('Failed two-factor authentication attempt'));
			return self::RESULT_INVALID;
		}

		self::clearFailures($user_id);
		self::log('MFA', $user_id, _t('Two-factor authentication succeeded using %1', $method));
		return self::RESULT_OK;
	}
	# -------------------------------------------------------
	/**
	 * Check authenticator app passcode. A passcode can only be used once.
	 *
	 * @param int $user_id
	 * @param string $code
	 *
	 * @return bool
	 */
	private static function verifyTOTP(int $user_id, string $code) : bool {
		if(!($row = self::getTOTPRow($user_id)) || is_null($row['enabled_on'])) { return false; }
		if(is_null($secret = self::decrypt($row['secret'], $user_id))) {
			self::log('SYS', $user_id, _t('Could not decrypt two-factor authentication secret. Check that __CA_MFA_ENCRYPTION_KEY__ in setup.php has not changed.'));
			return false;
		}
		if(is_null($step = TOTP::verify($secret, $code, (int)$row['last_used_step']))) { return false; }

		// Only succeed if no other request used this passcode (or a later one) in the meantime
		$db = new Db();
		$db->query("
			UPDATE ca_user_mfa SET last_used_step = ? WHERE mfa_id = ? AND last_used_step < ?
		", [$step, (int)$row['mfa_id'], $step]);
		return ($db->affectedRows() === 1);
	}
	# -------------------------------------------------------
	/**
	 * Check recovery code, removing it if it matches
	 *
	 * @param int $user_id
	 * @param string $code Normalized code (upper case, no separator)
	 *
	 * @return bool
	 */
	private static function verifyRecoveryCode(int $user_id, string $code) : bool {
		$db = new Db();
		$qr = $db->query("SELECT code_id, code_hash FROM ca_user_mfa_recovery_codes WHERE user_id = ?", [$user_id]);
		while($qr && $qr->nextRow()) {
			if(password_verify($code, $qr->get('code_hash'))) {
				// Only succeed if no other request used this code in the meantime
				$db->query("DELETE FROM ca_user_mfa_recovery_codes WHERE code_id = ?", [(int)$qr->get('code_id')]);
				return ($db->affectedRows() === 1);
			}
		}
		return false;
	}
	# -------------------------------------------------------
	# Rate limiting
	# -------------------------------------------------------
	/**
	 * Return failed attempt limit settings
	 *
	 * @return array
	 */
	private static function rateLimitConfig() : array {
		$config = Configuration::load('authentication.conf');
		$window = (int)$config->get('login_rate_limit_window');
		$max = $config->get('mfa_max_failures_per_user');
		return [
			'window' => ($window > 0) ? $window : 900,
			'max' => strlen((string)$max) ? (int)$max : 5
		];
	}
	# -------------------------------------------------------
	/**
	 * Has user reached the limit on failed attempts?
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function failuresExceeded(int $user_id) : bool {
		$config = self::rateLimitConfig();
		if($config['max'] <= 0) { return false; }

		$entry = PersistentCache::fetch("user:{$user_id}", 'MFARateLimit');
		return is_array($entry) && ((time() - (int)$entry['start']) < $config['window']) && ((int)$entry['count'] >= $config['max']);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function recordFailure(int $user_id) : void {
		$config = self::rateLimitConfig();
		if($config['max'] <= 0) { return; }

		$entry = PersistentCache::fetch("user:{$user_id}", 'MFARateLimit');
		if(!is_array($entry) || ((time() - (int)$entry['start']) >= $config['window'])) {
			$entry = ['start' => time(), 'count' => 0];
		}
		$entry['count']++;
		PersistentCache::save("user:{$user_id}", $entry, 'MFARateLimit');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function clearFailures(int $user_id) : void {
		PersistentCache::delete("user:{$user_id}", 'MFARateLimit');
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	/**
	 * Return user's authenticator app settings
	 *
	 * @param int $user_id
	 *
	 * @return array|null
	 */
	private static function getTOTPRow(int $user_id) : ?array {
		$db = new Db();
		$qr = $db->query("
			SELECT mfa_id, secret, enabled_on, last_used_step FROM ca_user_mfa WHERE user_id = ? AND mfa_type = ?
		", [$user_id, self::TYPE_TOTP]);
		if(!$qr || !$qr->nextRow()) { return null; }
		return [
			'mfa_id' => (int)$qr->get('mfa_id'),
			'secret' => (string)$qr->get('secret'),
			'enabled_on' => is_null($qr->get('enabled_on')) ? null : (int)$qr->get('enabled_on'),
			'last_used_step' => (int)$qr->get('last_used_step')
		];
	}
	# -------------------------------------------------------
	/**
	 * Return encryption key from setup.php, or null if it is missing or not valid
	 *
	 * @return string|null Binary key
	 */
	private static function encryptionKey() : ?string {
		if(!defined('__CA_MFA_ENCRYPTION_KEY__') || !strlen((string)__CA_MFA_ENCRYPTION_KEY__)) { return null; }
		$key = base64_decode((string)__CA_MFA_ENCRYPTION_KEY__, true);
		return ($key !== false) && (strlen($key) === SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) ? $key : null;
	}
	# -------------------------------------------------------
	/**
	 * Encrypt secret. The user id is bound to the ciphertext, so secrets can't be moved between users.
	 *
	 * @param string $secret
	 * @param int $user_id
	 *
	 * @return string Base64-encoded nonce and ciphertext
	 */
	private static function encrypt(string $secret, int $user_id) : string {
		if(is_null($key = self::encryptionKey())) { throw new ApplicationException(_t('__CA_MFA_ENCRYPTION_KEY__ is not set or is not valid')); }

		$nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
		$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($secret, "ca_user_mfa:{$user_id}", $nonce, $key);
		return base64_encode($nonce.$ciphertext);
	}
	# -------------------------------------------------------
	/**
	 * Decrypt secret
	 *
	 * @param string $data Value returned by UserMFA::encrypt()
	 * @param int $user_id
	 *
	 * @return string|null Secret, or null if it can't be decrypted
	 */
	private static function decrypt(string $data, int $user_id) : ?string {
		if(is_null($key = self::encryptionKey())) { return null; }

		$raw = base64_decode($data, true);
		if(($raw === false) || (strlen($raw) <= SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES)) { return null; }

		$nonce = substr($raw, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
		$ciphertext = substr($raw, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
		$secret = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, "ca_user_mfa:{$user_id}", $nonce, $key);
		return ($secret === false) ? null : $secret;
	}
	# -------------------------------------------------------
	/**
	 * Write event to authentication log
	 *
	 * @param string $code Log event code
	 * @param int $user_id
	 * @param string $message
	 */
	private static function log(string $code, int $user_id, string $message) : void {
		$ip = class_exists('RequestHTTP') ? RequestHTTP::ip() : null;
		caLogEvent($code, "{$message}; user_id={$user_id}; IP={$ip}", 'Auth');
	}
	# -------------------------------------------------------
}
