<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/Measure.php : base class for physical measurements with unit conversion
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
require_once(__DIR__.'/LocaleFormat.php');

/**
 * Thrown on invalid measurement values or units
 */
class MeasureException extends Exception {}

/**
 * A quantity with units. Replaces Zend_Measure_Abstract, and has the same interface and unit type codes.
 *
 * Subclasses define $units: a list of unit type codes, each with the factor to convert to the standard unit and a display symbol.
 */
abstract class Measure {
	# -------------------------------------------------------
	/**
	 * Unit definitions: type code => [factor to convert to standard unit, symbol]
	 * The special STANDARD key names the standard unit type
	 */
	protected static $units = [];

	/**
	 * Quantity in current units
	 */
	protected $value = 0;

	/**
	 * Current unit type code
	 */
	protected $type = null;

	/**
	 * Locale used to parse and format values
	 */
	protected $locale = null;
	# -------------------------------------------------------
	/**
	 * @param mixed $value Numeric value, or a string formatted for $locale
	 * @param string $type Unit type code [Default is standard unit]
	 * @param string $locale [Default is default locale]
	 * @throws MeasureException
	 */
	public function __construct($value, $type=null, $locale=null) {
		$this->setLocale($locale);
		$this->setValue($value, $type, $this->locale);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getLocale() : string {
		return $this->locale;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function setLocale($locale=null) {
		$this->locale = LocaleFormat::normalizeLocale($locale);
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Return value in current units
	 *
	 * @param int $round Number of decimal places to round to; -1 for no rounding [Default is -1]
	 * @param string $locale If set, the value is returned as a string formatted for the locale [Default is null]
	 * @return mixed
	 */
	public function getValue($round=-1, $locale=null) {
		if ($locale !== null) {
			return $this->formatValue($round, $locale);
		}
		return ($round < 0) ? $this->value : round($this->value, (int)$round);
	}
	# -------------------------------------------------------
	/**
	 * Set value and units
	 *
	 * @param mixed $value Numeric value, or a string formatted for $locale
	 * @param string $type Unit type code [Default is standard unit]
	 * @param string $locale [Default is locale of measurement]
	 * @throws MeasureException
	 */
	public function setValue($value, $type=null, $locale=null) {
		if ($locale === null) { $locale = $this->locale; }
		if ($type === null) { $type = static::$units['STANDARD']; }
		if (!static::isValidType($type)) {
			throw new MeasureException("Type ({$type}) is unknown");
		}
		try {
			$value = LocaleFormat::getNumber($value, ['locale' => $locale]);
		} catch (LocaleFormatException $e) {
			throw new MeasureException($e->getMessage(), $e->getCode(), $e);
		}
		$this->value = (float)$value;
		$this->type = null;
		$this->setType($type);
		return $this;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getType() {
		return $this->type;
	}
	# -------------------------------------------------------
	/**
	 * Change units, converting the value
	 *
	 * @param string $type Unit type code
	 * @throws MeasureException
	 */
	public function setType($type) {
		if (!static::isValidType($type)) {
			throw new MeasureException("Type ({$type}) is unknown");
		}
		if ($this->type !== null) {
			$this->value = $this->value * static::$units[$this->type][0] / static::$units[$type][0];
		}
		$this->type = $type;
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Determine if unit type code is valid for this measurement
	 *
	 * @param string $type
	 * @return bool
	 */
	public static function isValidType($type) : bool {
		return is_string($type) && ($type !== 'STANDARD') && isset(static::$units[$type]);
	}
	# -------------------------------------------------------
	/**
	 * Return value with unit symbol, formatted for locale (Eg. "1,234.5 cm")
	 *
	 * @param int $round Number of decimal places to round to; -1 for no rounding [Default is -1]
	 * @param string $locale [Default is locale of measurement]
	 * @return string
	 */
	public function toString($round=-1, $locale=null) : string {
		if ($locale === null) { $locale = $this->locale; }
		return $this->formatValue($round, $locale).' '.static::$units[$this->type][1];
	}
	# -------------------------------------------------------
	/**
	 * Format value for locale. Rounding follows the conventions of Zend_Measure, so that output is unchanged
	 * from previous versions: when rounding removes decimals the value is output with exactly $round decimal
	 * places (Eg. 1.2963 rounded to 2 is "1.30"), values with fewer decimals are output as-is (Eg. 1.5 rounded to 2 is "1.5")
	 * and whole numbers are output with a single decimal place (Eg. 12 rounded to 2 is "12.0").
	 *
	 * @param int $round Number of decimal places to round to; -1 for no rounding
	 * @param string $locale
	 * @return string
	 */
	protected function formatValue($round, $locale) : string {
		$value = self::decimalString($this->value);
		if ($round >= 0) {
			$decimals = (($pos = strpos($value, '.')) !== false) ? strlen($value) - $pos - 1 : 0;
			if ($decimals > $round) {
				$value = self::decimalString(round((float)$value, (int)$round), (int)$round);
			} elseif (($decimals === 0) && ($round > 0)) {
				$value .= '.0';
			}
		}
		return LocaleFormat::toNumber($value, ['locale' => $locale]);
	}
	# -------------------------------------------------------
	/**
	 * Convert float to a decimal string with up to 15 significant digits, the maximum that represents a float exactly
	 *
	 * @param float $value
	 * @param int $decimals Exact number of decimals to output; null to output only significant decimals [Default is null]
	 * @return string
	 */
	protected static function decimalString(float $value, ?int $decimals=null) : string {
		if ($decimals !== null) {
			return number_format($value, $decimals, '.', '');
		}
		$value = (float)sprintf('%.15G', $value);
		$str = number_format($value, max(0, 14 - (int)floor(log10(max(abs($value), 1e-300)))), '.', '');
		if (strpos($str, '.') !== false) { $str = rtrim(rtrim($str, '0'), '.'); }
		return ($str === '-0') ? '0' : $str;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __toString() : string {
		return $this->toString();
	}
	# -------------------------------------------------------
	/**
	 * Convert to units and return as string with unit symbol (Eg. "12.7 cm"). Units of the measurement are changed.
	 *
	 * @param string $type Unit type code
	 * @param int $round Number of decimal places to round to; -1 for no rounding [Default is 2]
	 * @param string $locale [Default is locale of measurement]
	 * @return string
	 * @throws MeasureException
	 */
	public function convertTo($type, $round=2, $locale=null) : string {
		$this->setType($type);
		return $this->toString($round, $locale);
	}
	# -------------------------------------------------------
	/**
	 * Return unit definitions
	 *
	 * @return array
	 */
	public function getConversionList() : array {
		return static::$units;
	}
	# -------------------------------------------------------
	/**
	 * Add measurement to this one. $measure is converted to units of this measurement.
	 *
	 * @param Measure $measure
	 * @return Measure
	 */
	public function add(Measure $measure) {
		$measure->setType($this->type);
		$this->value += $measure->getValue();
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Subtract measurement from this one. $measure is converted to units of this measurement.
	 *
	 * @param Measure $measure
	 * @return Measure
	 */
	public function sub(Measure $measure) {
		$measure->setType($this->type);
		$this->value -= $measure->getValue();
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Compare measurement to this one. $measure is converted to units of this measurement.
	 *
	 * @param Measure $measure
	 * @return int -1, 0 or 1 if this measurement is less than, equal to or greater than $measure
	 */
	public function compare(Measure $measure) : int {
		$measure->setType($this->type);
		return $this->value <=> $measure->getValue();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function equals($measure) : bool {
		return ((string)$measure === $this->toString());
	}
	# -------------------------------------------------------
}
