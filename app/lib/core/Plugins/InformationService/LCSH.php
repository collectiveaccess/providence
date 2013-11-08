<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/WLPlugInformationServiceLCSH.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
include_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_LCSH;
$g_information_service_settings_LCSH = array(
		'vocabulary' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('Vocabulary'),
			'description' => _t('Selects which vocabulary will be searched.'),
			'options' => array(
				_t('All vocabularies') => '',
				_t('LC Subject Headings') => 'cs:http://id.loc.gov/authorities/subjects',
				_t('LC Name Authority File') => 'cs:http://id.loc.gov/authorities/names',
				_t('LC Subject Headings for Children') => 'cs:http://id.loc.gov/authorities/childrensSubjects',
				_t('LC Genre/Forms File') => 'cs:http://id.loc.gov/authorities/genreForms',
				_t('Thesaurus of Graphic Materials') => 'cs:http://id.loc.gov/vocabulary/graphicMaterials',
				_t('Preservation Events') => 'cs:http://id.loc.gov/vocabulary/preservationEvents',
				_t('Preservation Level Role') => 'cs:http://id.loc.gov/vocabulary/preservationLevelRole',
				_t('Cryptographic Hash Functions') => 'cs:http://id.loc.gov/vocabulary/cryptographicHashFunctions',
				_t('MARC Relators') => 'cs:http://id.loc.gov/vocabulary/relators',
				_t('MARC Countries') => 'cs:http://id.loc.gov/vocabulary/countries',
				_t('MARC Geographic Areas') => 'cs:http://id.loc.gov/vocabulary/geographicAreas',
				_t('MARC Languages') => 'cs:http://id.loc.gov/vocabulary/languages',
				_t('ISO639-1 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-1',
				_t('ISO639-2 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-2',
				_t('ISO639-5 Languages') => 'cs:http://id.loc.gov/vocabulary/iso639-5',
			)
		)
);

class WLPlugInformationServiceLCSH Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_LCSH;
		
		WLPlugInformationServiceLCSH::$s_settings = $g_information_service_settings_LCSH;
		parent::__construct();
		$this->info['NAME'] = 'LCSH';
		
		$this->description = _t('Accesses data services in remote LCSH databases');
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceLCSH::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 *
	 */
	public function lookup($pa_settings, $ps_search) {
	
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function getExtendedInformation($pa_settings, $ps_id) {
	
	}
	# ------------------------------------------------
}
?>