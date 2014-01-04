<?php
/* ----------------------------------------------------------------------
 * app/lib/core/Plugins/CurrencyConversion/EuroBank.php :
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
 * ----------------------------------------------------------------------
 */
	require_once(__CA_LIB_DIR__."/core/Plugins/CurrencyConversion/BaseCurrencyConversionPlugin.php");
	require_once(__CA_LIB_DIR__."/core/Zend/Currency.php");
	
	class WLPlugCurrencyConversionEuroBank Extends BaseCurrencyConversionPlugIn implements IWLPlugCurrencyConversion {
		# ------------------------------------------------
		/**
		 * URL to XML feed of current currency conversion rates. Used by caConvertCurrencyValue()
		 */ 		
		const CONVERSION_SERVICE_URL = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
		# ------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			parent::__construct();
			$this->info['NAME'] = 'EuroBank';
		
			$this->description = _t('Converts currency using European bank rates available at %1', WLPlugCurrencyConversionEuroBank::CONVERSION_SERVICE_URL);
		}
		# ------------------------------------------------
		/**
		 * Convert currency value to another currency. Will throw an exception if value cannot be converted.
		 *
		 * @param $ps_value string Currency value with specifier (Ex. $500, USD 500, ¥1200, CAD 750)
		 * @param $ps_to string Specifier of currency to convert value to (Ex. USD, CAD, EUR)
		 * @param $pa_options array Options are:
		 *		numericValue = return floating point numeric value only, without currency specifier. Default is false.
		 *
		 * @return string Converted value with currency specifier, unless numericValue option is set.
		 */
		static public function convert($ps_value, $ps_to, $pa_options=null) {
			$va_currency_data = WLPlugCurrencyConversionEuroBank::_loadData();
			$ps_to = parent::normalizeCurrencySpecifier($ps_to);
			
			
			if (preg_match("!^([^\d]+)([\d\.\,]+)$!", trim($ps_value), $va_matches)) {
				$vs_decimal_value = (float)$va_matches[2];
				$vs_currency_specifier = trim($va_matches[1]);
			// or 1
			} else if (preg_match("!^([\d\.\,]+)([^\d]+)$!", trim($ps_value), $va_matches)) {
				$vs_decimal_value = (float)$va_matches[1];
				$vs_currency_specifier = trim($va_matches[2]);
			// or 2
			} else if (preg_match("!(^[\d\,\.]+$)!", trim($ps_value), $va_matches)) {
				$vs_decimal_value = (float)$va_matches[1];
				$vs_currency_specifier = null;
			// derp
			} else {
				throw(new Exception(_t('%1 is not a valid currency value; be sure to include a currency symbol', $ps_value)));
				return false;
			}
		
			if (!$vs_currency_specifier) {
				$o_currency = new Zend_Currency();
				$vs_currency_specifier = $o_currency->getShortName();
			}
			
			$vs_currency_specifier = parent::normalizeCurrencySpecifier($vs_currency_specifier);
		
			if (!self::canConvert($vs_currency_specifier, $ps_to)) { 
				throw(new Exception(_t('Cannot convert %1 to %2', $vs_currency_specifier, $ps_to)));
				return false;
			}
			
			$vn_value_in_euros = $vs_decimal_value / $va_currency_data[$vs_currency_specifier];
			
			$vn_converted_value = $vn_value_in_euros * $va_currency_data[$ps_to];
			
			if (caGetOption('numericValue', $pa_options, false)) {
				return (float)sprintf("%01.2f", $vn_converted_value);
			}
			
			if(Zend_Registry::isRegistered("Zend_Locale")) {
				$o_locale = Zend_Registry::get('Zend_Locale');
			} else {
				$o_locale = new Zend_Locale('en_US');
			}
			
			$vs_format = Zend_Locale_Data::getContent($o_locale, 'currencynumber');

			// this returns a string like '50,00 ¤' for locale de_DE
 			$vs_decimal_with_placeholder = Zend_Locale_Format::toNumber($vn_converted_value, array('locale' => $locale, 'number_format' => $vs_format, 'precision' => 2));

 			// if the currency placeholder is the first character, for instance in en_US locale ($10), insert a space.
 			// this has to be done because we don't print "$10" (which is expected in the locale rules) but "USD 10" ... and that looks nicer with an additional space.
 			if(substr($vs_decimal_with_placeholder,0,2)=='¤'){ // for whatever reason '¤' has length 2
 				$vs_decimal_with_placeholder = str_replace('¤', '¤ ', $vs_decimal_with_placeholder);
 			}

 			// insert currency which is not locale-dependent in our case
 			return str_replace('¤', $ps_to, $vs_decimal_with_placeholder);
		}
		# ------------------------------------------------
		/**
		 *
		 */
		static public function getCurrencyList() {
			$va_currency_data = WLPlugCurrencyConversionEuroBank::_loadData();
			return array_keys($va_currency_data);
		}
		# ------------------------------------------------
		/**
		 *
		 */
		static public function canConvert($ps_from, $ps_to) {
			$va_currency_data = WLPlugCurrencyConversionEuroBank::_loadData();
			$ps_from = parent::normalizeCurrencySpecifier($ps_from);
			$ps_to = parent::normalizeCurrencySpecifier($ps_to);
			
			if (isset($va_currency_data[$ps_from]) && isset($va_currency_data[$ps_to])) {
				return true;
			}
			return false;
		}
		# ------------------------------------------------
		/**
		 *
		 */
		static private function _loadData() {
			$vn_year = date("Y");
			$vn_day = date("j");
			
			// Does data exist in cache? Is it current?
			$o_cache = caGetCacheObject('ca_currency_conversion', 60*60*24);
			if (is_array($va_data = $o_cache->load('data')) && ($va_data['year'] == $vn_year) && ($va_data['day'] == $vn_day)) {
				return $va_data['rates'];
			}

			// Load data from source
			if ($vs_data = file_get_contents(WLPlugCurrencyConversionEuroBank::CONVERSION_SERVICE_URL)) {
				if (!($o_data = new SimpleXMLElement($vs_data))) {
					throw(new Exception(_t("Cannot parse data from %1", WLPlugCurrencyConversionEuroBank::CONVERSION_SERVICE_URL)));
					return null;
				}
				$va_data = array('rates' => array(), 'year' => $vn_year, 'day' => $vn_day);
				foreach($o_data->Cube->Cube->children() as $o_currency) {
					$o_attributes = $o_currency->attributes();
					$vs_currency = (string)$o_attributes->currency;
					$vn_rate = (string)$o_attributes->rate;
					
					$va_data['rates'][$vs_currency] = $vn_rate;
				}
				$va_data['rates']['EUR'] = 1.0;	// add Euro to list
				if ($o_cache) {
					$o_cache->save($va_data, 'data');
				}
				return $va_data['rates'];
			}
			throw(new Exception(_t("Cannot fetch data from %1", WLPlugCurrencyConversionEuroBank::CONVERSION_SERVICE_URL)));
			return null;
		}
		# ------------------------------------------------
	}
?>