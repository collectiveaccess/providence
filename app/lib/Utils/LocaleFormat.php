<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/LocaleFormat.php : locale-aware number parsing and formatting
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Thrown when a value cannot be parsed as a number in the requested locale
 */
class LocaleFormatException extends Exception {}

/**
 * Locale-aware number parsing and formatting, and basic locale information, backed by the PHP intl extension.
 *
 * Replaces Zend_Locale_Format, Zend_Locale_Data, Zend_Locale and Zend_Currency. Parsing and formatting
 * rules follow those of Zend_Locale_Format so that values are accepted and displayed as they were previously.
 */
class LocaleFormat {
	# -------------------------------------------------------
	/**
	 * Cache of number symbols and grouping information, keyed on locale
	 */
	static private $s_symbol_cache = [];
	# -------------------------------------------------------
	/**
	 * Normalize locale to a non-empty string, falling back to the default locale
	 *
	 * @param mixed $locale
	 * @return string
	 */
	static public function normalizeLocale($locale) : string {
		$locale = (string)$locale;
		if (!strlen($locale)) {
			$locale = defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : 'en_US';
		}
		return $locale;
	}
	# -------------------------------------------------------
	/**
	 * Return number symbols for locale. Keys are decimal, group, minus, plus, primaryGroupingSize,
	 * secondaryGroupingSize and grouping (true if grouping separators are used)
	 *
	 * @param mixed $locale
	 * @return array
	 */
	static public function getSymbols($locale) : array {
		$locale = self::normalizeLocale($locale);
		if (isset(self::$s_symbol_cache[$locale])) { return self::$s_symbol_cache[$locale]; }

		$fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);
		$primary = (int)$fmt->getAttribute(NumberFormatter::GROUPING_SIZE);
		$secondary = (int)$fmt->getAttribute(NumberFormatter::SECONDARY_GROUPING_SIZE);
		if ($secondary === $primary) { $secondary = 0; }

		return self::$s_symbol_cache[$locale] = [
			'decimal' => $fmt->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
			'group' => $fmt->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
			'minus' => $fmt->getSymbol(NumberFormatter::MINUS_SIGN_SYMBOL),
			'plus' => $fmt->getSymbol(NumberFormatter::PLUS_SIGN_SYMBOL),
			'grouping' => ((bool)$fmt->getAttribute(NumberFormatter::GROUPING_USED) && ($primary > 0)),
			'primaryGroupingSize' => ($primary > 0) ? $primary : 3,
			'secondaryGroupingSize' => ($secondary > 0) ? $secondary : 0
		];
	}
	# -------------------------------------------------------
	/**
	 * Determine if a string is a number formatted for a locale. Grouping separators are optional, but if present
	 * must be correctly placed. Eg. in en_US "1234.5" and "1,234.5" are numbers while "1,23.5" is not. In de_DE
	 * "54.33" is not a number, as "." is the grouping separator.
	 *
	 * @param string $input
	 * @param array $options Options include:
	 *		locale = Locale to use [Default is default locale]
	 * @return bool
	 */
	static public function isNumber($input, ?array $options=null) : bool {
		$input = (string)$input;
		$symbols = self::getSymbols($options['locale'] ?? null);

		if (strlen($input) && (strpos($input, $symbols['decimal']) === 0)) { $input = '0'.$input; }

		$input = self::_normalizeSpaces($input, $symbols);
		foreach(self::_getRegexes($symbols) as $regex) {
			if (preg_match($regex, $input)) { return true; }
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Convert a locale-formatted number into a normalized numeric string (Eg. "1.234,5" in de_DE is returned as "1234.5")
	 * Non-string input is returned unchanged.
	 *
	 * @param mixed $input
	 * @param array $options Options include:
	 *		locale = Locale to use [Default is default locale]
	 *		precision = Number of decimal places to truncate value to. [Default is null; no truncation]
	 * @return mixed
	 * @throws LocaleFormatException
	 */
	static public function getNumber($input, ?array $options=null) {
		if (!is_string($input)) { return $input; }
		if (!self::isNumber($input, $options)) {
			throw new LocaleFormatException("No localized value in {$input} found, or the given number does not match the localized format");
		}
		$symbols = self::getSymbols($options['locale'] ?? null);
		$precision = $options['precision'] ?? null;

		if (strlen($input) && (strpos($input, $symbols['decimal']) === 0)) { $input = '0'.$input; }
		$input = self::_normalizeSpaces($input, $symbols);

		if (($symbols['minus'] !== '-') && (strpos($input, $symbols['minus']) === 0)) {
			$input = '-'.substr($input, strlen($symbols['minus']));
		}
		if (($symbols['plus'] !== '+') && (strpos($input, $symbols['plus']) === 0)) {
			$input = substr($input, strlen($symbols['plus']));
		}
		$input = str_replace($symbols['group'], '', $input);

		if (strpos($input, $symbols['decimal']) !== false) {
			if ($symbols['decimal'] !== '.') {
				$input = str_replace($symbols['decimal'], '.', $input);
			}
			$fraction = substr($input, strpos($input, '.') + 1);
			if (($precision !== null) && ($precision >= 0) && (strlen($fraction) >= $precision)) {
				$input = substr($input, 0, strlen($input) - strlen($fraction) + $precision);
			}
			if (substr($input, -1) === '.') {
				$input = substr($input, 0, -1);
			}
		}
		return $input;
	}
	# -------------------------------------------------------
	/**
	 * Convert a locale-formatted number into a float
	 *
	 * @param mixed $input
	 * @param array $options See LocaleFormat::getNumber()
	 * @return float
	 * @throws LocaleFormatException
	 */
	static public function getFloat($input, ?array $options=null) : float {
		return (float)self::getNumber($input, $options);
	}
	# -------------------------------------------------------
	/**
	 * Format a number for display in a locale (Eg. 1234.5 is returned as "1.234,5" for de_DE)
	 *
	 * @param mixed $value
	 * @param array $options Options include:
	 *		locale = Locale to use [Default is default locale]
	 *		precision = Number of decimal places to round to. Decimals are zero-padded to this length. [Default is null; all decimals in the value are output]
	 *		number_format = An ICU-style number pattern, such as that returned by LocaleFormat::getCurrencyPattern(). Literal text in the pattern (Eg. the "¤" currency placeholder) is retained. [Default is null]
	 * @return string
	 */
	static public function toNumber($value, ?array $options=null) : string {
		$symbols = self::getSymbols($options['locale'] ?? null);
		$precision = $options['precision'] ?? null;
		if (($precision !== null) && ($precision < 0)) { $precision = null; }

		$value = self::_toDecimalString($value);
		$negative = (substr($value, 0, 1) === '-');
		if ($negative) { $value = substr($value, 1); }

		if ($precision !== null) {
			$value = self::_toDecimalString(round((float)$value, (int)$precision));
		}

		$tmp = explode('.', $value, 2);
		$int = ltrim($tmp[0], '0');
		if (!strlen($int)) { $int = '0'; }
		$fraction = $tmp[1] ?? '';
		if ($precision !== null) {
			$fraction = str_pad(substr($fraction, 0, $precision), $precision, '0');
		}
		if ($negative && !(int)$int && !(int)$fraction) { $negative = false; }	// don't output "-0"

		if ($pattern = ($options['number_format'] ?? null)) {
			return self::_applyPattern($pattern, $int, $fraction, $negative, $symbols);
		}

		$formatted = ($symbols['grouping'] ? self::_group($int, $symbols['group'], $symbols['primaryGroupingSize'], $symbols['secondaryGroupingSize']) : $int)
			.(strlen($fraction) ? $symbols['decimal'].$fraction : '');
		return $negative ? $symbols['minus'].$formatted : $formatted;
	}
	# -------------------------------------------------------
	/**
	 * Return currency number pattern for locale (Eg. "¤#,##0.00" for en_US and "#,##0.00 ¤" for de_DE)
	 * Bidirectional text marks are removed, so the "¤" placeholder is always the first or last character of a positive pattern.
	 *
	 * @param mixed $locale
	 * @return string
	 */
	static public function getCurrencyPattern($locale) : string {
		$fmt = new NumberFormatter(self::normalizeLocale($locale), NumberFormatter::CURRENCY);
		return preg_replace('![\x{200E}\x{200F}\x{061C}]!u', '', $fmt->getPattern());
	}
	# -------------------------------------------------------
	/**
	 * Return ISO currency code for locale (Eg. "USD" for en_US)
	 *
	 * @param mixed $locale
	 * @return string|null Currency code, or null if locale does not specify a region with a currency
	 */
	static public function getCurrencyCode($locale) : ?string {
		$fmt = new NumberFormatter(self::normalizeLocale($locale), NumberFormatter::CURRENCY);
		$code = $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE);
		return ($code && ($code !== 'XXX')) ? $code : null;
	}
	# -------------------------------------------------------
	/**
	 * Return name of language, localized in a locale (Eg. "français" for language "fr" in locale "fr")
	 *
	 * @param string $language Language code
	 * @param mixed $in_locale Locale to return name in
	 * @return string|null
	 */
	static public function getLanguageName(string $language, $in_locale) : ?string {
		$name = Locale::getDisplayLanguage($language, self::normalizeLocale($in_locale));
		return (strlen($name) && ($name !== $language)) ? $name : null;
	}
	# -------------------------------------------------------
	/**
	 * Return name of country, localized in a locale (Eg. "France" for country "FR" in locale "fr_FR")
	 *
	 * @param string $country Two-letter country code
	 * @param mixed $in_locale Locale to return name in
	 * @return string|null
	 */
	static public function getCountryName(string $country, $in_locale) : ?string {
		$name = Locale::getDisplayRegion("und_{$country}", self::normalizeLocale($in_locale));
		return (strlen($name) && ($name !== $country)) ? $name : null;
	}
	# -------------------------------------------------------
	/**
	 * Determine if text in a locale is written right-to-left
	 *
	 * @param mixed $locale
	 * @return bool
	 */
	static public function isRightToLeft($locale) : bool {
		$locale = self::normalizeLocale($locale);
		try {
			if (($bundle = ResourceBundle::create($locale, null)) && ($layout = $bundle->get('layout'))) {
				return ($layout->get('characters') === 'right-to-left');
			}
		} catch (Throwable $e) {
			// fall through to language list
		}
		$script = Locale::getScript($locale);
		if (in_array($script, ['Arab', 'Hebr', 'Thaa', 'Syrc', 'Nkoo', 'Adlm', 'Rohg'], true)) { return true; }
		return in_array(Locale::getPrimaryLanguage($locale), ['ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'iw', 'ks', 'ps', 'sd', 'ug', 'ur', 'yi'], true);
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	/**
	 * Convert number to a non-scientific decimal string, using the same precision as PHP's float to string conversion
	 *
	 * @param mixed $value
	 * @return string
	 */
	static private function _toDecimalString($value) : string {
		if (is_float($value) && (is_nan($value) || is_infinite($value))) { return '0'; }
		$value = trim((string)$value);
		if (!strlen($value)) { return '0'; }
		if (stripos($value, 'e') !== false) {
			$tmp = explode('e', strtolower($value));
			$mantissa = $tmp[0];
			$decimals = (strpos($mantissa, '.') !== false) ? strlen(substr($mantissa, strpos($mantissa, '.') + 1)) : 0;
			if ((int)$tmp[1] < 0) { $decimals += abs((int)$tmp[1]); }
			$value = number_format((float)$value, min($decimals, 30), '.', '');
			if (strpos($value, '.') !== false) { $value = rtrim(rtrim($value, '0'), '.'); }
		}
		if (substr($value, 0, 1) === '+') { $value = substr($value, 1); }
		return $value;
	}
	# -------------------------------------------------------
	/**
	 * Insert grouping separators into string of digits
	 *
	 * @param string $digits
	 * @param string $separator
	 * @param int $primary Size of the right-most group
	 * @param int $secondary Size of other groups; zero if same as primary
	 * @return string
	 */
	static private function _group(string $digits, string $separator, int $primary, int $secondary) : string {
		if (strlen($digits) <= $primary) { return $digits; }
		$size = $secondary ?: $primary;
		$groups = [substr($digits, -$primary)];
		$rest = substr($digits, 0, -$primary);
		while (strlen($rest) > $size) {
			array_unshift($groups, substr($rest, -$size));
			$rest = substr($rest, 0, -$size);
		}
		if (strlen($rest)) { array_unshift($groups, $rest); }
		return join($separator, $groups);
	}
	# -------------------------------------------------------
	/**
	 * Format number parts using an ICU-style pattern (Eg. "¤#,##0.00"), retaining literal prefix and suffix text
	 *
	 * @param string $pattern
	 * @param string $int Integer digits
	 * @param string $fraction Fractional digits
	 * @param bool $negative
	 * @param array $symbols
	 * @return string
	 */
	static private function _applyPattern(string $pattern, string $int, string $fraction, bool $negative, array $symbols) : string {
		$subpatterns = explode(';', $pattern, 2);
		$use_negative_subpattern = ($negative && isset($subpatterns[1]));
		$pattern = $use_negative_subpattern ? $subpatterns[1] : $subpatterns[0];

		if (!preg_match('!^(.*?)([#0,]*0[#0,]*)(\.[0#]+)?(.*)$!u', $pattern, $m)) {
			return ($negative ? $symbols['minus'] : '').$int.(strlen($fraction) ? $symbols['decimal'].$fraction : '');
		}
		list(, $prefix, $int_pattern, , $suffix) = $m;

		if (($pos = strrpos($int_pattern, ',')) !== false) {
			$primary = strlen($int_pattern) - $pos - 1;
			$first = strpos($int_pattern, ',');
			$secondary = ($first !== $pos) ? ($pos - $first - 1) : 0;
			$int = self::_group($int, $symbols['group'], $primary, $secondary);
		}
		$formatted = $int.(strlen($fraction) ? $symbols['decimal'].$fraction : '');

		if ($use_negative_subpattern) {
			return str_replace('-', $symbols['minus'], $prefix).$formatted.str_replace('-', $symbols['minus'], $suffix);
		}
		return ($negative ? $symbols['minus'] : '').$prefix.$formatted.$suffix;
	}
	# -------------------------------------------------------
	/**
	 * Regular expressions matching numbers in a locale
	 *
	 * @param array $symbols
	 * @return array
	 */
	static private function _getRegexes(array $symbols) : array {
		$d = preg_quote($symbols['decimal'], '!');
		$g = preg_quote($symbols['group'], '!');
		$sign = '['.preg_quote($symbols['minus'].$symbols['plus'], '!').'+\-]?';
		$p = $symbols['primaryGroupingSize'];

		if (!$symbols['grouping']) {
			$int = '[0-9]*';
		} elseif ($s = $symbols['secondaryGroupingSize']) {
			$int = "(?:[0-9]{1,{$s}}(?:{$g}?[0-9]{{$s}})*{$g}?[0-9]{{$p}}|[0-9]{1,{$p}})";
		} else {
			$int = "[0-9]{1,{$p}}(?:{$g}?[0-9]{{$p}})*";
		}
		return [
			"!^{$sign}{$int}(?:{$d}[0-9]+)?$!u",
			"!^{$sign}[0-9]+(?:{$d}[0-9]+)*[eE][+\-]?[0-9]+$!u"
		];
	}
	# -------------------------------------------------------
	/**
	 * Locales such as fr_FR use a non-breaking space as group separator. Accept regular and other non-breaking spaces as well.
	 *
	 * @param string $input
	 * @param array $symbols
	 * @return string
	 */
	static private function _normalizeSpaces(string $input, array $symbols) : string {
		if (preg_match('!^[\s\x{00A0}\x{202F}]$!u', $symbols['group'])) {
			$input = preg_replace('![ \x{00A0}\x{202F}]!u', $symbols['group'], $input);
		}
		return $input;
	}
	# -------------------------------------------------------
}
