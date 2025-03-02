<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/TGN.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage InformationService
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once( __CA_LIB_DIR__ . "/Plugins/IWLPlugInformationService.php" );
require_once( __CA_LIB_DIR__ . "/Plugins/InformationService/BaseGettyLODServicePlugin.php" );

global $g_information_service_settings_TGN;
$g_information_service_settings_TGN = array();

class WLPlugInformationServiceTGN extends BaseGettyLODServicePlugin implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------

	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_TGN;
		$g_information_service_settings_TGN['additionalFilter'] = [
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('Additional search filter'),
			'description' => _t('Additional search filter. For example to limit to children of a particular term enter "gvp:broaderExtended aat:300312238"')
		];
		$g_information_service_settings_TGN['sparqlSuffix'] = [
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('Additional sparql suffix'),
			'description' => _t('Applied after the initial search. Useful to combine filters. For example to limit to children of particular terms enter "?ID gvp:broaderPreferredExtended ?Extended FILTER (?Extended IN (aat:300261086, aat:300264550))"')
		];

		WLPlugInformationServiceTGN::$s_settings = $g_information_service_settings_TGN;
		parent::__construct();
		$this->info['NAME'] = 'TGN';

		$this->description = _t( 'Provides access to Getty Linked Open Data TGN service' );
	}

	# ------------------------------------------------
	protected function getConfigName() {
		return 'tgn';
	}
	# ------------------------------------------------

	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceTGN::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/**
	 * Clean results
	 *
	 * @param $pa_results
	 * @param $pa_options
	 * @param $pa_params
	 *
	 * @return array|bool
	 */
	public function _cleanResults( $pa_results, $pa_options, $pa_params ) {
		if ( ! is_array( $pa_results ) ) {
			return false;
		}

		if ( $pa_params['isRaw'] ) {
			return $pa_results;
		}

		$va_return = array();
		foreach ( $pa_results as $va_values ) {
			$vs_id = '';
			if ( preg_match( "/[a-z]{3,4}\/[0-9]+$/", $va_values['ID']['value'], $va_matches ) ) {
				$vs_id = str_replace( '/', ':', $va_matches[0] );
			}

			$vs_label = ( caGetOption( 'format', $pa_options, null, [ 'forceToLowercase' => true ] ) !== 'short' )
					? '[' . str_replace( 'tgn:', '', $vs_id ) . '] ' . $va_values['TermPrefLabel']['value'] . "; " . $va_values['Parents']['value'] . " (" . $va_values['Type']['value'] . ")"
				: $va_values['TermPrefLabel']['value'];

			$va_return['results'][] = array(
				'label' => htmlentities( str_replace( ', ... World', '', $vs_label ) ),
				'url'   => $va_values['ID']['value'],
				'idno'  => $vs_id,
			);
		}

		return $va_return;
	}

	public function _buildQuery( $ps_search, $pa_options, $pa_params ) {
		$vs_additional_filter = $pa_options['settings']['additionalFilter'] ?? null;
		if ($vs_additional_filter){
			$vs_additional_filter = "$vs_additional_filter ;";
		}
		$vs_sparql_suffix = $pa_options['settings']['sparqlSuffix'] ?? null;
		$vs_query = urlencode( 'SELECT ?ID (coalesce(?labEn,?labGVP) as ?TermPrefLabel) ?Parents ?Type {
			?ID a skos:Concept; ' . $pa_params['search_field'] . ' "' . $ps_search . '"; skos:inScheme tgn: ; ' . $vs_additional_filter . '
			optional {?ID gvp:prefLabelGVP [xl:literalForm ?labGVP]}
			optional {?ID xl:prefLabel [xl:literalForm ?labEn; dct:language gvp_lang:' . $pa_params['default_lang'] . ']}
			{?ID gvp:parentStringAbbrev ?Parents}
			{?ID gvp:displayOrder ?Order}
			{?ID gvp:placeTypePreferred [gvp:prefLabelGVP [xl:literalForm ?Type]]
			' . $vs_sparql_suffix . '

			}
		} ORDER BY ASC(?Order)
		LIMIT ' . $pa_params['limit'] );

		return $vs_query;
	}

	public function _getParams( $pa_options, $pa_service_conf = null ) {
		$va_params = parent::_getParams( $pa_options, $pa_service_conf );
		if ( ! ( $vs_default_lang = $this->opo_linked_data_conf->get( 'getty_default_language' ) ) ) {
			$va_params['default_lang'] = 'en';
		}

		return $va_params;
	}


	# ------------------------------------------------

	/**
	 * Get display value
	 *
	 * @param string $ps_text
	 *
	 * @return string
	 */
	public function getDisplayValueFromLookupText( $ps_text ) {
		if ( ! $ps_text ) {
			return '';
		}
		$va_matches = array();

		if ( preg_match( "/^\[[0-9]+\]\s+([\p{L}\p{P}\p{Z}]+)\;.+$/", $ps_text, $va_matches ) ) {
			return $va_matches[1];
		}

		return $ps_text;
	}
	# ------------------------------------------------
}
