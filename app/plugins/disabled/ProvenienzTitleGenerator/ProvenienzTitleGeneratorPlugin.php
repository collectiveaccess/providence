<?php
/* ----------------------------------------------------------------------
 * ProvenienzTitleGenerator.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');

class ProvenienzTitleGeneratorPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	private $opo_config;
	# -------------------------------------------------------
	public function __construct($ps_plugin_path) {
		$this->description = _t('Generates titles based upon project-specific rules.');
		parent::__construct();

		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/ProvenienzTitleGenerator.conf');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true - the SDKTitleGeneratorPlugin plugin always initializes ok
	 */
	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => ((bool)$this->opo_config->get('enabled'))
		);
	}
	# -------------------------------------------------------
	/**
	 * Generate/Update title on manifestation/work save
	 */
	public function hookAfterBundleUpdate(&$pa_params) {
		$this->_rewriteObjectLabel($pa_params);
	}
	public function hookAfterBundleInsert(&$pa_params) {
		$this->_rewriteObjectLabel($pa_params);
	}
	# -------------------------------------------------------
	private function _rewriteObjectLabel(&$pa_params) {
		switch($pa_params['instance']->tableName()) {
			case 'ca_entities':
				$t_list = new ca_lists();
				$t_locale = new ca_locales();
				$t_rel_types = new ca_relationship_types();
				$pn_locale_de = $t_locale->localeCodeToID('de_DE');
				$vn_type_id = $pa_params['instance']->getTypeID();
				switch($vn_type_id) {
					// person is updated/inserted -> pass title to object if exists
					case $t_list->getItemIDFromList('entity_types', 'person'):
						$va_related = $pa_params['instance']->getRelatedItems(
							"ca_objects",
							array(
								'restrict_to_relationship_types' => array(
									'kuenstler_traeger'
								),
								'restrict_to_types' => array(
									'traegerobjekt'
								 )
							)
						);
						if(sizeof($va_related)==0){
							return;
						}
						foreach ($va_related as $va_obj_data){
							$t_o = new ca_objects($va_obj_data["object_id"]);
							$vs_label = $this->_getLabel($t_o);
							if(strlen($vs_label)>0){
								$t_o->removeAllLabels();
								$t_o->addLabel(array(
									'name' => $vs_label
								), $pn_locale_de, null, true);
							}
						}
						break;
					default:
						break;
				}
				break;
			case 'ca_objects':
				$t_list = new ca_lists();
				$t_locale = new ca_locales();
				$t_rel_types = new ca_relationship_types();
				$pn_locale_de = $t_locale->localeCodeToID("de_DE");
				
				if($pa_params['instance']->getTypeID() == $t_list->getItemIDFromList('object_types', 'traegerobjekt')){
					$vs_label = $this->_getLabel($pa_params['instance']);
					if(strlen($vs_label)>0){
						$pa_params['instance']->removeAllLabels();
						$pa_params['instance']->addLabel(array(
							'name' => $vs_label
						), $pn_locale_de, null, true);
					}
				}
				
				break;
			default: 
				break;
		}
	}
	# -------------------------------------------------------
	private function _getLabel($t_object){
		$vs_titel = $t_object->get("ca_objects.titel");
		$vs_datierung = $t_object->get("ca_objects.datierung");
		$va_entities = $t_object->getRelatedItems("ca_entities",array(
			'restrict_to_relationship_types' => array(
				'kuenstler_traeger'
			)
		));
		$va_persons = array();
		if(sizeof($va_entities)==0){
			$vs_person = "";
		} else {
			foreach($va_entities as $va_entity){
				$t_entity = new ca_entities($va_entity["entity_id"]);
				$va_persons[] = $t_entity->getLabelForDisplay();
			}
			$vs_person = join(", ", $va_persons);
		}
		
		return
			((strlen($vs_person)>0 && strlen($vs_titel)>0) ? $vs_person.": " : "").
			((strlen($vs_person)>0 && !strlen($vs_titel)>0) ? $vs_person." " : "").
			(strlen($vs_titel)>0 ? '"'.$vs_titel.'" ' : "").
			(strlen($vs_datierung)>0 ? '('.$vs_datierung.')' : "");
		
	}
	# -------------------------------------------------------
	/**
	 * Get plugin user actions
	 */
	static public function getRoleActionList() {
		return array();
	}
	# -------------------------------------------------------
}
?>
