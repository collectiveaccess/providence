<?php
/* ----------------------------------------------------------------------
 * titleGeneratorPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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

class SDKTitleGeneratorPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	private $opo_config;
	# -------------------------------------------------------
	public function __construct($ps_plugin_path) {
		$this->description = _t('Generates titles based upon Kinemathek-specific rules.');
		parent::__construct();

		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/SDKTitleGenerator.conf');
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
		$this->_rewriteManifestLabel($pa_params);
		$this->_rewriteWorkLabel($pa_params);
		$this->_rewriteObjectLabel($pa_params);
	}
	public function hookAfterBundleInsert(&$pa_params) {
		$this->_rewriteManifestLabel($pa_params);
		$this->_rewriteWorkLabel($pa_params);
		$this->_rewriteObjectLabel($pa_params);
	}
	# -------------------------------------------------------
	private function _rewriteObjectLabel(&$pa_params) {
		switch($pa_params['instance']->tableName()) {
			case 'ca_occurrences':
				$t_list = new ca_lists();
				$t_locale = new ca_locales();
				$t_rel_types = new ca_relationship_types();
				$pn_locale_de = $t_locale->localeCodeToID('de_DE');
				$vn_type_id = $pa_params['instance']->getTypeID();
				switch($vn_type_id) {
					// manifestation is updated/inserted -> pass title to object if exists
					case $t_list->getItemIDFromList('occurrence_types', 'av_manifestation'):
						$va_related = $pa_params['instance']->getRelatedItems(
							"ca_objects",
							array(
								'restrict_to_relationship_types' => array(
									'exemplar'
								 )
							)
						);
						if(sizeof($va_related)==0){
							return;
						}
						foreach ($va_related as $va_obj_data){
							$t_o = new ca_objects($va_obj_data["object_id"]);
							$vs_label = $pa_params['instance']->getLabelForDisplay();
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
				$va_related = $pa_params['instance']->getRelatedItems(
					"ca_occurrences",
					array(
						'restrict_to_relationship_types' => array(
							'exemplar'
						 )
					)
				);
				if(sizeof($va_related)==0){
					return;
				}
				foreach ($va_related as $va_occ_data){ // should only be one, if any
					$t_occ = new ca_occurrences($va_occ_data["occurrence_id"]);
					$vs_label = $t_occ->getLabelForDisplay();
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
	private function _rewriteWorkLabel(&$pa_params) {
		switch($pa_params['instance']->tableName()) {
			case 'ca_occurrences':
				$t_list = new ca_lists();
				$t_locale = new ca_locales();
				$t_rel_types = new ca_relationship_types();
				$pn_locale_de = $t_locale->localeCodeToID('de_DE');
				$vn_type_id = $pa_params['instance']->getTypeID();
				switch($vn_type_id) {
					// manifestation is updated/inserted -> pass title to work if exists
					case $t_list->getItemIDFromList('occurrence_types', 'av_manifestation'):
						$va_related_works = $pa_params['instance']->getRelatedItems(
							"ca_occurrences",
							array(
								'restrict_to_type' => 'av_work',
								'restrict_to_relationship_types' => array(
									'manifest'
								 )
							)
						);
						// there should be only one work for each manifestation, right?
						if(sizeof($va_related_works)==0){
							return;
						}
						$va_work_data = array_pop($va_related_works);
						$t_work = new ca_occurrences($va_work_data["occurrence_id"]);
						$vs_label = $pa_params['instance']->getLabelForDisplay();
						if(strlen($vs_label)>0){
							$t_work->removeAllLabels();
							$t_work->addLabel(array(
								'name' => $vs_label
							), $pn_locale_de, null, true);
						}
						break;
					// work is updated -> fetch title from primary manifestation if exists
					case $t_list->getItemIDFromList('occurrence_types', 'av_work'):
						$va_related_manifests = $pa_params['instance']->getRelatedItems(
							"ca_occurrences",
							array(
								'restrict_to_type' => 'av_manifestation',
								'restrict_to_relationship_types' => array(
									'manifest'
								 )
							)
						);
						// let's assume there is only one related manifestation
						if(sizeof($va_related_manifests)==0){
							return;
						}
						$va_manifest_data = array_pop($va_related_manifests);
						$t_manifest = new ca_occurrences($va_manifest_data["occurrence_id"]);
						$vs_label = $t_manifest->getLabelForDisplay();
						if(strlen($vs_label)>0){
							$pa_params['instance']->removeAllLabels();
							$pa_params['instance']->addLabel(array(
								'name' => $vs_label
							), $pn_locale_de, null, true);
						}
						break;
				}

				break;
		}
	}
	# -------------------------------------------------------
	private function _rewriteManifestLabel(&$pa_params) {
		switch($pa_params['instance']->tableName()) {
			case 'ca_occurrences':
				$t_list = new ca_lists();
				$t_locale = new ca_locales();
				$t_rel_types = new ca_relationship_types();
				$pn_locale_de = $t_locale->localeCodeToID('de_DE');
				$vn_type_id = $pa_params['instance']->getTypeID();
				switch($vn_type_id) {
					case $t_list->getItemIDFromList('occurrence_types', 'av_manifestation'):
						$vs_serientitel = $this->_getSerialTitle($pa_params['instance']);
						$vs_sendetitel = $this->_getTvDistTitle($pa_params['instance']);
						$vs_sortierungfolge = $this->_getEpisodePart($pa_params['instance']);
						$vs_label =
							(strlen($vs_serientitel)>0 ? $vs_serientitel : "").
							(strlen($vs_serientitel)>0 && strlen($vs_sortierungfolge)>0 ? " - ".$vs_sortierungfolge : "").
							((strlen($vs_sortierungfolge)>0 || strlen($vs_serientitel)>0) && strlen($vs_sendetitel)>0 ? ": " : "").
							(strlen($vs_sendetitel)>0 ? $vs_sendetitel : "").
							(strlen($vs_serientitel)==0 && strlen($vs_sortierungfolge)>0 ? "(".$vs_sortierungfolge.")" : "");
						if(strlen($vs_label)>0){
							$pa_params['instance']->removeAllLabels();
							$pa_params['instance']->addLabel(array(
								'name' => $vs_label
							), $pn_locale_de, null, true);
						}
						break;
					default:
						break;
				}
		}
	}
	# -------------------------------------------------------
	private function _getTvDistTitle($t_manifest){
		$va_containers = $t_manifest->getAttributesByElement("title");
		$t_list = new ca_lists();
		foreach($va_containers as $vo_container){
			$vb_take_this_container = false;
			foreach($vo_container->getValues() as $vo_value){
				if($vo_value->getElementCode() == "title_type"){
					if($vo_value->getItemID() == $t_list->getItemIDFromList('title_types', 'tv_dist_title')){
						$vb_take_this_container = true;
					}
				}
			}
			if($vb_take_this_container){
				foreach($vo_container->getValues() as $vo_value){
					if($vo_value->getElementCode() == "title_title"){
						return $vo_value->getDisplayValue();
					}
				}
			}
		}
	}
	# -------------------------------------------------------
	private function _getSerialTitle($t_manifest){
		$va_containers = $t_manifest->getAttributesByElement("title");
		$t_list = new ca_lists();
		foreach($va_containers as $vo_container){
			$vb_take_this_container = false;
			foreach($vo_container->getValues() as $vo_value){
				if($vo_value->getElementCode() == "title_type"){
					if($vo_value->getItemID() == $t_list->getItemIDFromList('title_types', 'serial_title')){
						$vb_take_this_container = true;
					}
				}
			}
			if($vb_take_this_container){
				foreach($vo_container->getValues() as $vo_value){
					if($vo_value->getElementCode() == "title_title"){
						return $vo_value->getDisplayValue();
					}
				}
			}
		}
	}
	# -------------------------------------------------------
	private function _getEpisodePart($t_manifest){
		$va_related_works = $t_manifest->getRelatedItems(
			"ca_occurrences",
			array(
				'restrict_to_type' => 'av_work',
				'restrict_to_relationship_types' => array(
					'manifest'
				 )
			)
		);
		// there should be only one work for each manifestation, right?
		if(sizeof($va_related_works)==0){
			return;
		}
		$va_work_data = array_pop($va_related_works);
		$t_work = new ca_occurrences($va_work_data["occurrence_id"]);
		$va_containers = $t_work->getAttributesByElement("episode");
		$t_list = new ca_lists();
		$vs_num = $vs_description = "";
		foreach($va_containers as $vo_container){ // there is only 1 container
			foreach($vo_container->getValues() as $vo_value){
				if($vo_value->getElementCode() == "episode_description"){
					$vs_description = $t_list->getItemForDisplayByItemID($vo_value->getItemID());
				}
				if($vo_value->getElementCode() == "episode_number"){
					$vs_num = $vo_value->getDisplayValue();
				}
			}
			if(strlen($vs_description)>0 && strlen($vs_num)>0){
				return $vs_description." ".$vs_num;
			} else {
				return "";
			}
		}
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
