<?php
/** ---------------------------------------------------------------------
 * app/helpers/gisHelpers.php : GIS/mapping utility  functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
   *
   */
    require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/LengthAttributeValue.php');
    
    /**
     * List of countries used for drop-down list
     */
    $g_country_list = array(
    	_t('United States') => 'US',
		_t('Canada') => 'CA',
		_t('Afghanistan') => 'AF',
		_t('Albania') => 'AL',
		_t('Algeria') => 'DZ',
		_t('American Samoa') => 'AS',
		_t('Andorra') => 'AD',
		_t('Angola') => 'AO',
		_t('Anguilla') => 'AI',
		_t('Antarctica') => 'AQ',
		_t('Antigua and Barbuda') => 'AG',
		_t('Argentina') => 'AR',
		_t('Armenia') => 'AM',
		_t('Aruba') => 'AW',
		_t('Australia') => 'AU',
		_t('Austria') => 'AT',
		_t('Azerbaijan') => 'AZ',
		_t('Azores') => 'AP',
		_t('Bahamas') => 'BS',
		_t('Bahrain') => 'BH',
		_t('Bangladesh') => 'BD',
		_t('Barbados') => 'BB',
		_t('Belarus') => 'BY',
		_t('Belgium') => 'BE',
		_t('Belize') => 'BZ',
		_t('Benin') => 'BJ',
		_t('Bermuda') => 'BM',
		_t('Bhutan') => 'BT',
		_t('Bolivia') => 'BO',
		_t('Bosnia And Herzegowina') => 'BA',
		_t('Bosnia-Herzegovina') => 'XB',
		_t('Botswana') => 'BW',
		_t('Bouvet Island') => 'BV',
		_t('Brazil') => 'BR',
		_t('British Indian Ocean Territory') => 'IO',
		_t('British Virgin Islands') => 'VG',
		_t('Brunei Darussalam') => 'BN',
		_t('Bulgaria') => 'BG',
		_t('Burkina Faso') => 'BF',
		_t('Burundi') => 'BI',
		_t('Cambodia') => 'KH',
		_t('Cameroon') => 'CM',
		_t('Cape Verde') => 'CV',
		_t('Cayman Islands') => 'KY',
		_t('Central African Republic') => 'CF',
		_t('Chad') => 'TD',
		_t('Chile') => 'CL',
		_t('China') => 'CN',
		_t('Christmas Island') => 'CX',
		_t('Cocos (Keeling) Islands') => 'CC',
		_t('Colombia') => 'CO',
		_t('Comoros') => 'KM',
		_t('Congo') => 'CG',
		_t('Congo, The Democratic Republic O') => 'CD',
		_t('Cook Islands') => 'CK',
		_t('Corsica') => 'XE',
		_t('Costa Rica') => 'CR',
		_t('Cote d` Ivoire (Ivory Coast)') => 'CI',
		_t('Croatia') => 'HR',
		_t('Cuba') => 'CU',
		_t('Cyprus') => 'CY',
		_t('Czech Republic') => 'CZ',
		_t('Denmark') => 'DK',
		_t('Djibouti') => 'DJ',
		_t('Dominica') => 'DM',
		_t('Dominican Republic') => 'DO',
		_t('East Timor') => 'TP',
		_t('Ecuador') => 'EC',
		_t('Egypt') => 'EG',
		_t('El Salvador') => 'SV',
		_t('Equatorial Guinea') => 'GQ',
		_t('Eritrea') => 'ER',
		_t('Estonia') => 'EE',
		_t('Ethiopia') => 'ET',
		_t('Falkland Islands (Malvinas)') => 'FK',
		_t('Faroe Islands') => 'FO',
		_t('Fiji') => 'FJ',
		_t('Finland') => 'FI',
		_t('France (Includes Monaco)') => 'FR',
		_t('France, Metropolitan') => 'FX',
		_t('French Guiana') => 'GF',
		_t('French Polynesia') => 'PF',
		_t('French Polynesia (Tahiti)') => 'TA',
		_t('French Southern Territories') => 'TF',
		_t('Gabon') => 'GA',
		_t('Gambia') => 'GM',
		_t('Georgia') => 'GE',
		_t('Germany') => 'DE',
		_t('Ghana') => 'GH',
		_t('Gibraltar') => 'GI',
		_t('Greece') => 'GR',
		_t('Greenland') => 'GL',
		_t('Grenada') => 'GD',
		_t('Guadeloupe') => 'GP',
		_t('Guam') => 'GU',
		_t('Guatemala') => 'GT',
		_t('Guinea') => 'GN',
		_t('Guinea-Bissau') => 'GW',
		_t('Guyana') => 'GY',
		_t('Haiti') => 'HT',
		_t('Heard And Mc Donald Islands') => 'HM',
		_t('Holy See (Vatican City State)') => 'VA',
		_t('Honduras') => 'HN',
		_t('Hong Kong') => 'HK',
		_t('Hungary') => 'HU',
		_t('Iceland') => 'IS',
		_t('India') => 'IN',
		_t('Indonesia') => 'ID',
		_t('Iran') => 'IR',
		_t('Iraq') => 'IQ',
		_t('Ireland') => 'IE',
		_t('Ireland (Eire)') => 'EI',
		_t('Israel') => 'IL',
		_t('Italy') => 'IT',
		_t('Jamaica') => 'JM',
		_t('Japan') => 'JP',
		_t('Jordan') => 'JO',
		_t('Kazakhstan') => 'KZ',
		_t('Kenya') => 'KE',
		_t('Kiribati') => 'KI',
		_t('Korea, Democratic People\'s Republic') => 'KP',
		_t('Kuwait') => 'KW',
		_t('Kyrgyzstan') => 'KG',
		_t('Laos') => 'LA',
		_t('Latvia') => 'LV',
		_t('Lebanon') => 'LB',
		_t('Lesotho') => 'LS',
		_t('Liberia') => 'LR',
		_t('Libya') => 'LY',
		_t('Liechtenstein') => 'LI',
		_t('Lithuania') => 'LT',
		_t('Luxembourg') => 'LU',
		_t('Macao') => 'MO',
		_t('Macedonia') => 'MK',
		_t('Madagascar') => 'MG',
		_t('Madeira Islands') => 'ME',
		_t('Malawi') => 'MW',
		_t('Malaysia') => 'MY',
		_t('Maldives') => 'MV',
		_t('Mali') => 'ML',
		_t('Malta') => 'MT',
		_t('Marshall Islands') => 'MH',
		_t('Martinique') => 'MQ',
		_t('Mauritania') => 'MR',
		_t('Mauritius') => 'MU',
		_t('Mayotte') => 'YT',
		_t('Mexico') => 'MX',
		_t('Micronesia, Federated States Of') => 'FM',
		_t('Moldova, Republic Of') => 'MD',
		_t('Monaco') => 'MC',
		_t('Mongolia') => 'MN',
		_t('Montserrat') => 'MS',
		_t('Morocco') => 'MA',
		_t('Mozambique') => 'MZ',
		_t('Myanmar (Burma)') => 'MM',
		_t('Namibia') => 'NA',
		_t('Nauru') => 'NR',
		_t('Nepal') => 'NP',
		_t('Netherlands') => 'NL',
		_t('Netherlands Antilles') => 'AN',
		_t('New Caledonia') => 'NC',
		_t('New Zealand') => 'NZ',
		_t('Nicaragua') => 'NI',
		_t('Niger') => 'NE',
		_t('Nigeria') => 'NG',
		_t('Niue') => 'NU',
		_t('Norfolk Island') => 'NF',
		_t('Northern Mariana Islands') => 'MP',
		_t('Norway') => 'NO',
		_t('Oman') => 'OM',
		_t('Pakistan') => 'PK',
		_t('Palau') => 'PW',
		_t('Palestinian Territory, Occupied') => 'PS',
		_t('Panama') => 'PA',
		_t('Papua New Guinea') => 'PG',
		_t('Paraguay') => 'PY',
		_t('Peru') => 'PE',
		_t('Philippines') => 'PH',
		_t('Pitcairn') => 'PN',
		_t('Poland') => 'PL',
		_t('Portugal') => 'PT',
		_t('Puerto Rico') => 'PR',
		_t('Qatar') => 'QA',
		_t('Reunion') => 'RE',
		_t('Romania') => 'RO',
		_t('Russian Federation') => 'RU',
		_t('Rwanda') => 'RW',
		_t('Saint Kitts And Nevis') => 'KN',
		_t('San Marino') => 'SM',
		_t('Sao Tome and Principe') => 'ST',
		_t('Saudi Arabia') => 'SA',
		_t('Senegal') => 'SN',
		_t('Serbia-Montenegro') => 'XS',
		_t('Seychelles') => 'SC',
		_t('Sierra Leone') => 'SL',
		_t('Singapore') => 'SG',
		_t('Slovak Republic') => 'SK',
		_t('Slovenia') => 'SI',
		_t('Solomon Islands') => 'SB',
		_t('Somalia') => 'SO',
		_t('South Africa') => 'ZA',
		_t('South Georgia And The South Sand') => 'GS',
		_t('South Korea') => 'KR',
		_t('Spain') => 'ES',
		_t('Sri Lanka') => 'LK',
		_t('St. Christopher and Nevis') => 'NV',
		_t('St. Helena') => 'SH',
		_t('St. Lucia') => 'LC',
		_t('St. Pierre and Miquelon') => 'PM',
		_t('St. Vincent and the Grenadines') => 'VC',
		_t('Sudan') => 'SD',
		_t('Suriname') => 'SR',
		_t('Svalbard And Jan Mayen Islands') => 'SJ',
		_t('Swaziland') => 'SZ',
		_t('Sweden') => 'SE',
		_t('Switzerland') => 'CH',
		_t('Syrian Arab Republic') => 'SY',
		_t('Taiwan') => 'TW',
		_t('Tajikistan') => 'TJ',
		_t('Tanzania') => 'TZ',
		_t('Thailand') => 'TH',
		_t('Togo') => 'TG',
		_t('Tokelau') => 'TK',
		_t('Tonga') => 'TO',
		_t('Trinidad and Tobago') => 'TT',
		_t('Tristan da Cunha') => 'XU',
		_t('Tunisia') => 'TN',
		_t('Turkey') => 'TR',
		_t('Turkmenistan') => 'TM',
		_t('Turks and Caicos Islands') => 'TC',
		_t('Tuvalu') => 'TV',
		_t('Uganda') => 'UG',
		_t('Ukraine') => 'UA',
		_t('United Arab Emirates') => 'AE',
		_t('United Kingdom') => 'UK',
		_t('Great Britain') => 'GB',
		_t('United States Minor Outlying Isl') => 'UM',
		_t('Uruguay') => 'UY',
		_t('Uzbekistan') => 'UZ',
		_t('Vanuatu') => 'VU',
		_t('Vatican City') => 'XV',
		_t('Venezuela') => 'VE',
		_t('Vietnam') => 'VN',
		_t('Virgin Islands (U.S.)') => 'VI',
		_t('Wallis and Furuna Islands') => 'WF',
		_t('Western Sahara') => 'EH',
		_t('Western Samoa') => 'WS',
		_t('Yemen') => 'YE',
		_t('Yugoslavia') => 'YU',
		_t('Zaire') => 'ZR',
		_t('Zambia') => 'ZM',
		_t('Zimbabwe') => 'ZW'
	);
	
	/**
     * List of states/provinces/zones by country; used for drop-down list
     */
	$g_states_by_country_list = array(
		'US' => array(
			_t("Alaska") => "AK",
			_t("Alabama") => "AL",
			_t("Arkansas") => "AR",
			_t("American Samoa") => "AS",
			_t("Arizona") => "AZ",
			_t("California") => "CA",
			_t("Colorado") => "CO",
			_t("Connecticut") => "CT",
			_t("Washington, DC") => "DC",
			_t("Delaware") => "DE",
			_t("Florida") => "FL",
			_t("Micronesia") => "FM",
			_t("Georgia") => "GA",
			_t("Guam") => "GU",
			_t("Hawaii") => "HI",
			_t("Iowa") => "IA",
			_t("Idaho") => "ID",
			_t("Illinois") => "IL",
			_t("Indiana") => "IN",
			_t("Kansas") => "KS",
			_t("Kentucky") => "KY",
			_t("Louisiana") => "LA",
			_t("Massachusetts") => "MA",
			_t("Maryland") => "MD",
			_t("Maine") => "ME",
			_t("Marshall Islands") => "MH",
			_t("Michigan") => "MI",
			_t("Minnesota") => "MN",
			_t("Missouri") => "MO",
			_t("Marianas") => "MP",
			_t("Mississippi") => "MS",
			_t("Montana") => "MT",
			_t("North Carolina") => "NC",
			_t("North Dakota") => "ND",
			_t("Nebraska") => "NE",
			_t("New Hampshire") => "NH",
			_t("New Jersey") => "NJ",
			_t("New Mexico") => "NM",
			_t("Nevada") => "NV",
			_t("New York") => "NY",
			_t("Ohio") => "OH",
			_t("Oklahoma") => "OK",
			_t("Oregon") => "OR",
			_t("Pennsylvania") => "PA",
			_t("Puerto Rico") => "PR",
			_t("Palau") => "PW",
			_t("Rhode Island") => "RI",
			_t("South Carolina") => "SC",
			_t("South Dakota") => "SD",
			_t("Tennessee") => "TN",
			_t("Texas") => "TX",
			_t("Utah") => "UT",
			_t("Vermont") => "VT",
			_t("Virginia") => "VA",
			_t("Virgin Islands") => "VI",
			_t("Washington") => "WA",
			_t("Wisconsin") => "WI",
			_t("West Virginia") => "WV",
			_t("Wyoming") => "WY",
			_t("Military Americas") => "AA",
			_t("Military Europe/ME/Canada") => "AP",
			_t("Military Pacific") => "AP"
		),
		'CA' => array(
			_t("Alberta") => "AB",
			_t("Manitoba") => "MB",
			_t("British Columbia") => "BC",
			_t("New Brunswick") => "NB",
			_t("Newfoundland and Labrador") => "NL",
			_t("Nova Scotia") => "NS",
			_t("Northwest Territories") => "NT",
			_t("Nunavut") => "NU",
			_t("Ontario") => "ON",
			_t("Prince Edward Island") => "PE",
			_t("Quebec") => "QC",
			_t("Saskatchewan") => "SK",
			_t("Yukon Territory") => "YT"
		)
	);
    
    # --------------------------------------------------------------------------------------------
 	/**
 	 * Converts $ps_value from degrees minutes seconds format to decimal
 	 */
	function caGISminutesToSignedDecimal($ps_value){
		$ps_value = trim($ps_value);
		$vs_value = preg_replace('/[^0-9A-Za-z\.\-]+/', ' ', $ps_value);
		
		if ($vs_value === $ps_value) { return $ps_value; }
		list($vn_deg, $vn_min, $vn_sec, $vs_dir) = explode(' ',$vs_value);
		$vn_pos = ($vn_deg < 0) ? -1:1;
		if (in_array(strtoupper($vs_dir), array('S', 'W'))) { $vn_pos = -1; }
		
		$vn_deg = abs(round($vn_deg,6));
		$vn_min = abs(round($vn_min,6));
		$vn_sec = abs(round($vn_sec,6));
		return round($vn_deg+($vn_min/60)+($vn_sec/3600),6)*$vn_pos;
	}
	# --------------------------------------------------------------------------------------------
	/**
 	 * Converts $ps_value from decimal with N/S/E/W to signed decimal
 	 */
	function caGISDecimalToSignedDecimal($ps_value){
		$ps_value = trim($ps_value);
		list($vn_left_of_decimal, $vn_right_of_decimal, $vs_dir) = preg_split('![\. ]{1}!',$ps_value);
		if (preg_match('!([A-Za-z]+)$!', $vn_right_of_decimal, $va_matches)) {
			$vs_dir = $va_matches[1];
			$vn_right_of_decimal = preg_replace('!([A-Za-z]+)$!', '', $vn_right_of_decimal);
		}
		$vn_pos = 1;
		if (in_array(strtoupper($vs_dir), array('S', 'W'))) { $vn_pos = -1; }
		
		return floatval($vn_left_of_decimal.'.'.$vn_right_of_decimal) * $vn_pos;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns true if $ps_value is in degrees minutes seconds format
	 */ 
	function caGISisDMS($ps_value){
		if(preg_match('/[^0-9A-Za-z\.\- ]+/', $ps_value)) {
			return true;
		}
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Parse map searches in the following formats:
	 *	[Box bounded by coordinates]
	 *		lat1,long1 to lat2,long2
	 *		lat1,long1 - lat2,long2
	 *		lat1,long1 .. lat2,long2
	 *			ex. 43.34,-74.24 .. 42.1,-75.02
	 *	
	 *		lat1,long1 ~ distance
	 *			ex. 43.34,-74.23 ~ 5km
	 *	[Area with
	 */ 
	function caParseGISSearch($ps_value){
		$ps_value = preg_replace('![ ]*,[ ]*!', ',', $ps_value);
		$ps_value = str_replace(" - ", " .. ", $ps_value);
		$ps_value = str_replace(" to ", " .. ", $ps_value);
		$ps_value = preg_replace('![^A-Za-z0-9,\.\-~ ]+!', '', $ps_value);
		
		$va_tokens = preg_split('![ ]+!', $ps_value);
		
		$vn_lat1 = $vn_long1 = $vn_lat2 = $vn_long2 = null;
		$vn_dist = null;
		$vn_state = 0;
		while(sizeof($va_tokens)) {
			$vs_token = trim(array_shift($va_tokens));
			switch($vn_state) {
				case 0:		// start
					$va_tmp = explode(',', $vs_token);
					if (sizeof($va_tmp) != 2) { return false; }
					$vn_lat1 = (float)$va_tmp[0];
					$vn_long1 = (float)$va_tmp[1];
					
					if (!sizeof($va_tokens)) {
						return array(
							'min_latitude' => $vn_lat1,
							'max_latitude' =>  $vn_lat1,
							'min_longitude' =>  $vn_long1,
							'max_longitude' =>  $vn_long1
						);
					}
					
					$vn_state = 1;
					break;
				case 1:		// conjunction
					switch($vs_token) {
						case '~':
							$vn_state = 3;
							break(2);
						case '..' :
							$vn_state = 2;
							break(2);
						default:
							$vn_state = 2;
							break;
					}
					// fall through
				case 2:	// second lat/long
					$va_tmp = explode(',', $vs_token);
					if (sizeof($va_tmp) != 2) { return false; }
					$vn_lat2 = (float)$va_tmp[0];
					$vn_long2 = (float)$va_tmp[1];
					
					if (($vn_lat1 == 0) || ($vn_lat2 == 0) || ($vn_long1 == 0) || ($vn_long2 == 0)) { return null; }
					
					return array(
						'min_latitude' => ($vn_lat1 > $vn_lat2) ? $vn_lat2 : $vn_lat1,
						'max_latitude' =>  ($vn_lat1 < $vn_lat2) ? $vn_lat2 : $vn_lat1,
						'min_longitude' =>  ($vn_long1 > $vn_long2) ? $vn_long2 : $vn_long1,
						'max_longitude' =>  ($vn_long1 < $vn_long2) ? $vn_long2 : $vn_long1,
					);
					break;
				case 3:	// distance
					//
					// TODO: The lat/long delta calculations below are very rough. We should replace with more accurate formulas.
					//
					$t_length = new LengthAttributeValue();
					$va_length_val = $t_length->parseValue($vs_token, array('displayLabel' => 'distance'));
					$vn_length = ((float)array_shift(explode(' ', preg_replace('![^\d\.]+!', '', $va_length_val['value_decimal1'])))) / 1000;		// kilometers
					$vn_lat1_km = (10000/90) * $vn_lat1;
					$vn_long1_km = (10000/90) * $vn_long1;
					
					$vn_lat1 = (($vn_lat1_km + ($vn_length/2)))/(10000/90);
					$vn_long1 = (($vn_long1_km + ($vn_length/2)))/(10000/90);
					
					$vn_lat2 = (($vn_lat1_km - ($vn_length/2)))/(10000/90);
					$vn_long2 = (($vn_long1_km - ($vn_length/2)))/(10000/90);
					
					if (($vn_lat1 == 0) || ($vn_lat2 == 0) || ($vn_long1 == 0) || ($vn_long2 == 0)) { return null; }
					
					return array(
						'min_latitude' => ($vn_lat1 > $vn_lat2) ? $vn_lat2 : $vn_lat1,
						'max_latitude' =>  ($vn_lat1 < $vn_lat2) ? $vn_lat2 : $vn_lat1,
						'min_longitude' =>  ($vn_long1 > $vn_long2) ? $vn_long2 : $vn_long1,
						'max_longitude' =>  ($vn_long1 < $vn_long2) ? $vn_long2 : $vn_long1,
						'distance' => $vn_length
					);
					
					break;
				
			}
		}
		
		return false;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	function caParseEXIFLatLong($pa_exif) {
		if(!is_array($pa_exif['GPS'])) { return null; }
		
		$vn_lat = '';
		if (is_array($pa_exif['GPS']['GPSLatitude'])) {
			foreach($pa_exif['GPS']['GPSLatitude'] as $vn_i => $vs_val) {
				$va_tmp = explode('/', $vs_val);
				if ($va_tmp[1] > 0) { 
					$vn_lat .= ' '.($va_tmp[0]/$va_tmp[1]); 
					switch($va_tmp[1]) {
						case 1:
							$vn_lat .= "°";
							break;
						case 100:
							$vn_lat .= "'";
							break;
						default:
							$vn_lat .= '"';
							break;
					}
				}
			}
			$vn_lat .= $pa_exif['GPS']['GPSLatitudeRef'];
		}
		
		$vn_long = '';
		if (is_array($pa_exif['GPS']['GPSLongitude'])) {
			foreach($pa_exif['GPS']['GPSLongitude'] as $vn_i => $vs_val) {
				$va_tmp = explode('/', $vs_val);
				if ($va_tmp[1] > 0) { 
					$vn_long .= ' '.($va_tmp[0]/$va_tmp[1]); 
					switch($va_tmp[1]) {
						case 1:
							$vn_long .= "°";
							break;
						case 100:
							$vn_long .= "'";
							break;
						default:
							$vn_long .= '"';
							break;
					}
				}
			}
			$vn_long .= $pa_exif['GPS']['GPSLongitudeRef'];
		}
		
		// TODO: extract GPSAltitude, GPSAltitudeRef, GPSImgDirection and GPSImgDirectionRef
	    
		return array(
			'latitude' => caGISminutesToSignedDecimal($vn_lat),
			'longitude' => caGISminutesToSignedDecimal($vn_long)
		);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns list of countries as associative array. Keys are localized country names. Values are country codes.
	 *
	 * @return array List of countries.
	 */
	function caGetCountryList() {
		global $g_country_list;
		
		return $g_country_list;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns list of states, provinces or zones for the specified country code.
	 *
	 * @param string $ps_country The country code. If set to a false value the entire state list, with states listed by country code, is returned.
	 * @return array A list of state/province/zones for the specified country. An empty array will be returned if there are none defined for the specified country. Null will be returned if the country code is invalid.
	 */
	function caGetStateList($ps_country=null) {
		global $g_states_by_country_list;
		
		if (!$ps_country) {
			return $g_states_by_country_list;
		}
		if ($ps_country && !isset($g_states_by_country_list[$ps_country])) {
			return null;
		}
		if (is_array($g_states_by_country_list[$ps_country])) {
			return $g_states_by_country_list[$ps_country];
		}
		
		return array();
	}
	# --------------------------------------------------------------------------------------------
?>