<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/AAT.php :
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

global $g_information_service_settings_AAT;
$g_information_service_settings_AAT = array();

class WLPlugInformationServiceAAT extends BaseGettyLODServicePlugin implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------

	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_AAT;
		$g_information_service_settings_AAT['additionalFilter'] = [
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('Additional search filter'),
			'description' => _t('Additional search filter. For example to limit to children of a particular term enter "gvp:broaderExtended aat:300312238"')
		];

		WLPlugInformationServiceAAT::$s_settings = $g_information_service_settings_AAT;
		parent::__construct();
		$this->info['NAME'] = 'AAT';

		$this->description = _t( 'Provides access to Getty Linked Open Data AAT service' );
	}

	# ------------------------------------------------
	protected function getConfigName() {
		return 'aat';
	}
	# ------------------------------------------------

	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceAAT::$s_settings;
	}

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
				? $va_values['TermPrefLabel']['value']
				: '[' . str_replace( 'aat:', '', $vs_id ) . '] ' . $va_values['TermPrefLabel']['value'] . " ["
				  . $va_values['Parents']['value'] . "]";
			$vs_label = preg_replace( '/\,\s\.\.\.\s[A-Za-z\s]+Facet\s*/', '', $vs_label );
			$vs_label = preg_replace( '/[\<\>]/', '', $vs_label );

			$va_return['results'][] = array(
				'label' => htmlentities( $vs_label ),
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
		$vs_query = urlencode( 'SELECT ?ID ?TermPrefLabel ?Parents ?ParentsFull {
	?ID a skos:Concept; ' . $pa_params['search_field'] . ' "' . $ps_search . '"; skos:inScheme aat: ; ' . $vs_additional_filter . '
	gvp:prefLabelGVP [xl:literalForm ?TermPrefLabel].
	{?ID gvp:parentStringAbbrev ?Parents}
	{?ID gvp:parentString ?ParentsFull}
	{?ID gvp:displayOrder ?Order}
} LIMIT ' . $pa_params['limit'] );
		return $vs_query;
	}

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

		if ( preg_match( "/^\[[0-9]+\]\s+([\p{L}\p{P}\p{Z}]+)\s+\[/", $ps_text, $va_matches ) ) {
			return $va_matches[1];
		}

		return $ps_text;
	}
	# ------------------------------------------------
}
