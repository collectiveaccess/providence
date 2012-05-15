<?php
/* ----------------------------------------------------------------------
 * nhfXMLPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__.'/ca_object_lots.php');
 	require_once(__CA_MODELS_DIR__.'/ca_collections.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');
 	
 
	class nhfXMLPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Transforms XML input as needed for Northeast Historic Films (http://www.oldfilm.org).');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/nhfXML.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the titleGeneratorPlugin plugin always initializes ok
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
		 * 
		 */
		public function hookXMLPreprocessRecord(&$pa_params) {
			$va_record = $pa_params['record'];	// Grab the data to be imported (the "record") out of the params
		
			switch($pa_params['mapping_code']) {
				# ----------------------------------------------------
				case 'nhf_pbcore_sound_silent_fixup': 
					if ($vs_value = $va_record['pbcoreInstantiation']['pbcoreAnnotation']['annotation']) {
						if (preg_match('!^si!',$vs_value)) {
							$va_record['pbcoreInstantiation']['pbcoreAnnotation']['annotation'] = 'Silent';
						} else {
							if (preg_match('!^sd!',$vs_value)) {
								$va_record['pbcoreInstantiation']['pbcoreAnnotation']['annotation'] = 'Sound';
							} else {
								$va_record['pbcoreInstantiation']['pbcoreAnnotation']['annotation'] = 'Unknown';
							}
						}
					}
					break;
				# ----------------------------------------------------
				case 'nhf_pbcore_import':
					// Grab the first part of the pbcore identifier - this is the accession idno
					$va_idno = explode('.', $va_record['pbcoreIdentifier']['identifier']);
					$t_lot = new ca_object_lots();
					
					$vs_lot_idno = $vs_collection_idno = null;
					if ($t_lot->load(array('idno_stub' => $va_idno[0]))) {	// load the accession
						$vs_lot_idno = $t_lot->get('idno_stub');
						$va_collections = $t_lot->getRelatedItems('ca_collections');	// grab the first collection (should be only collection) linked to the accession
						foreach($va_collections as $vn_rel_id => $va_rel) {
							$vs_collection_idno = $va_rel['idno'];
							break;
						}
						
						// Insert the values into the record with appropriate tags (match those in mapping)
						$va_record['accession']['identifier'][] = $vs_lot_idno;
						$va_record['collection']['identifier'][]= $vs_collection_idno;
					}
					break;
				# ----------------------------------------------------
				case 'nhf_pbcore_procite_import':
					
					if (is_array($va_instantiations = $va_record['pbcoreInstantiation'])) {
						$va_keys = array_keys($va_instantiations);
						if (!is_numeric($va_keys[0])) {
							$va_record['pbcoreInstantiation']  = $va_instantiations = array($va_instantiations);
						}
						foreach($va_instantiations as $vn_i => $va_instantiation) {
							if ($vs_phys_type = $va_instantiation['formatPhysical']) {
								switch($vs_phys_type) {
									case 'Betacam SP':
										$va_record['pbcoreInstantiation'][$vn_i]['formatPhysical'] = 'BetaCamSP';
										break;
									default:
										$va_tmp = explode(':', $vs_phys_type);
										$va_record['pbcoreInstantiation'][$vn_i]['formatPhysical'] = trim(trim($va_tmp[1]).' '.trim($va_tmp[0]));
										break;
								}
							}
							
							if ($va_annotations = $va_instantiation['pbcoreAnnotation']) {
								$va_rewritten_annotations = array();
								foreach($va_annotations as $vn_j => $va_annotation) {
									$va_tmp = explode(':', $va_annotation['annotation']);
									
									switch(trim($va_tmp[0])) {
										case 'Sound':
											$va_record['pbcoreInstantiation'][$vn_i]['sound_or_silent'] = (($va_tmp[1] == 'Sound') ? 'Sound' : 'Silent');
											break;
										default:
											$va_rewritten_annotations = $va_annotation;
											break;
									}
								}
								$va_record['pbcoreInstantiation'][$vn_i]['pbcoreAnnotation'] = $va_rewritten_annotations;
							}
						}
					}
										
					if ($va_coverage = $va_record['pbcoreCoverage']) {
						$va_keys = array_keys($va_coverage);
						if (!is_numeric($va_keys[0])) {
							$va_record['pbcoreCoverage']  = $va_coverage = array($va_coverage);
						}
						foreach($va_coverage as $vn_i => $va_coverage_item) {
							$vs_coverage = $va_coverage_item['coverage'];
							$va_tmp = explode(':', $vs_coverage);
							if (sizeof($va_tmp) > 1) {
								$vs_coverage = trim(trim($va_tmp[1]).', '.trim($va_tmp[0]));
							}
							$va_record['pbcoreCoverage'][$vn_i]['coverage'] = $vs_coverage;
						}
					}
					if ($va_rights = $va_record['pbcoreRightsSummary']) {
						$va_keys = array_keys($va_rights);
						if (!is_numeric($va_keys[0])) {
							$va_record['pbcoreRightsSummary']  = $va_rights = array($va_rights);
						}
						
						$va_rewritten_rights = null;
						foreach($va_rights as $vn_i => $va_rights_item) {
							$vs_rights = $va_rights_item['rightsSummary'];
							$va_tmp = explode(':', $vs_rights);
							if (sizeof($va_tmp) > 1) {
								$vs_rights_code = str_replace(' ', '', $va_tmp[1]);
							} else {
								$va_rewritten_rights = $va_rights_item;
							}
						}
						
						$va_rewritten_rights['NHF_RightsSummary'] = $vs_rights_code;
						$va_record['pbcoreRightsSummary'] = array($va_rewritten_rights);
						
					}
					if ($va_relations = $va_record['pbcoreRelation']) {
						foreach($va_relations as $vn_j => $va_relation) {
							switch($va_relation['relationType']) {
								case 'Is Part of Collection':
									$t_collection = new ca_collections();
			
									if ($t_collection->load(array('idno' => $va_relation['relationIdentifier']))) {	// load the collection
										$vs_collection_idno = $t_collection->get('idno');
										
										$va_record['collection']['identifier'][]= $vs_collection_idno;
									}
									break;
								case 'Is Part of Accession Lot':
									$t_lot = new ca_object_lots();
			
									if ($t_lot->load(array('idno_stub' => $va_relation['relationIdentifier']))) {	// load the accession
										$va_record['accession']['identifier'][] = $t_lot->get('idno_stub');
									}
									break;
							}
							
						}
					}
					
					break;
				# ----------------------------------------------------
			}
		//	print_r($va_record); return null;
			// Put the modified record back into the parameter data for ingestion
			$pa_params['record'] = $va_record;
			return $pa_params;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function hookXMLAfterRecordImport(&$pa_params) {
			$va_record = $pa_params['record'];	// Grab the data that was imported (the "record") out of the params
			
			switch($pa_params['mapping_code']) {
				case 'nhf_pbcore_import':
					// Link the instantiation to collection and accession
					$vs_instantiation_idno = $va_record['pbcoreInstantiation']['pbcoreFormatID']['formatIdentifier'];
					
					$t_object = new ca_objects();
					
					if ($t_object->load(array('idno' => $vs_instantiation_idno))) {
						$vs_lot_idno = $va_record['accession']['identifier'][0];
						$vs_collection_idno = $va_record['collection']['identifier'][0];
						
						$t_lot = new ca_object_lots();
						$t_collection = new ca_collections();
						
						if ($t_lot->load(array('idno_stub' => $vs_lot_idno))) {
							$t_object->setMode(ACCESS_WRITE);
							$t_object->set('lot_id', $t_lot->getPrimaryKey());
							$t_object->update();
							
							if ($t_object->numErrors()) {
								print "ERROR SETTING INSTANTIATION-ACCESSION LINK: ".join('; ', $t_object->getErrors())."\n";
							}
						}
						if ($t_collection->load(array('idno' => $vs_collection_idno))) {
							$t_rel = new ca_relationship_types();
							
							$t_object->addRelationship('ca_collections', $t_collection->getPrimaryKey(), $t_rel->getRelationshipTypeID('ca_objects_x_collections', 'contains'));
							
							if ($t_object->numErrors()) {
								print "ERROR SETTING INSTANTIATION-COLLECTION LINK: ".join('; ', $t_object->getErrors())."\n";
							}
						}
					}
					break;
			}
		}
		# -------------------------------------------------------
	}
?>