<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/VictimService.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_APP_DIR__."/plugins/ns11mmServices/services/NS11mmService.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/ca/Search/EntitySearch.php");
require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__CA_MODELS_DIR__."/ca_lists.php");
require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");

class VictimService extends NS11mmService {
	# -------------------------------------------------------
	protected $opo_dm;
	protected $opn_victim_type_id;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);
		$this->opo_dm = Datamodel::load();
		
		$t_list = new ca_lists();
		$this->opn_victim_type_id = $t_list->getItemIDFromList('entity_types', 'victim');
	}
	# -------------------------------------------------------
	/**
	 * Returns a list of all victims
	 */
	public function listVictims(){
		$o_search = new EntitySearch();
		
        $from = $this->opo_request->getParameter('from', pString);
        $until = $this->opo_request->getParameter('until', pString);
        if (!$until) { $until = date('c'); }
        $vs_range = ($from && $until) ? self::utcToDb($from).' to '.self::utcToDb($until) : null;
        
		$qr_res = $o_search->search("ca_entities.type_id:".$this->opn_victim_type_id, array('limitToModifiedOn' => $vs_range));
		
		
        $skip = $this->opo_request->getParameter('skip', pInteger);
        $limit = $this->opo_request->getParameter('limit', pInteger);
        
        if ($skip > 0) { $qr_res->seek($skip); }
        if ($skip > $qr_res->numHits()) { return $this->makeResponse(array()); } 
		$va_list = array();
		while($qr_res->nextHit()) {
			$va_list[$vn_id = $qr_res->get('ca_entities.entity_id')] = array(
				'id' => $vn_id,
				'forename' => $qr_res->get('ca_entities.preferred_labels.forename'),
				'other_forenames' => $qr_res->get('ca_entities.preferred_labels.other_forenames'),
				'surname' => $qr_res->get('ca_entities.preferred_labels.surname'),
				'middlename' => $qr_res->get('ca_entities.preferred_labels.middlename'),
				'displayname' => $qr_res->get('ca_entities.preferred_labels.displayname'),
				'prefix' => $qr_res->get('ca_entities.preferred_labels.prefix'),
				'suffix' => $qr_res->get('ca_entities.preferred_labels.suffix'),
				'last_modification' => $qr_res->get('ca_entities.lastModified', array("dateFormat" => 'iso8601'))
			);
			if ($limit && (sizeof($va_list) >= $limit)) { break; }
		}
		
		return $this->makeResponse($va_list);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function get(){
		if (!is_object($t_entity = $this->_checkEntity())) { return $t_entity; }
		$vn_id = $t_entity->getPrimaryKey();
		
		$t_list = new ca_lists();
		$vn_yes_id = $t_list->getItemIDFromList("yes_no", "yes");
		$vn_no_id = $t_list->getItemIDFromList("yes_no", "no");
		$vn_male_id = $t_list->getItemIDFromList("genders", "male");
		$vn_female_id = $t_list->getItemIDFromList("genders", "female");
		
		// get victim info
		$va_data = array(
			'id' => $vn_id,
			'forename' => $t_entity->get('ca_entities.preferred_labels.forename'),
			'other_forenames' => $t_entity->get('ca_entities.preferred_labels.other_forenames'),
			'surname' => $t_entity->get('ca_entities.preferred_labels.surname'),
			'middlename' => $t_entity->get('ca_entities.preferred_labels.middlename'),
			'displayname' => $t_entity->get('ca_entities.preferred_labels.displayname'),
			'prefix' => $t_entity->get('ca_entities.preferred_labels.prefix'),
			'suffix' => $t_entity->get('ca_entities.preferred_labels.suffix'),
			'gender' => ($t_entity->get('ca_entities.gender') == $vn_male_id)  ? "M" : "F",
			'pregnant' => ($t_entity->get('ca_entities.pregnant') == $vn_yes_id) ? 1 : 0,
			'final_text_bio' => $t_entity->get('ca_entities.final_text_bio'),
			'lifespan' => $t_entity->get('ca_entities.lifespan', array('dateFormat' => 'iso8601')),
			'lifespan_as_text' => $t_entity->get('ca_entities.lifetime_text'),
			'last_modification' => $t_entity->get('ca_entities.lastModified', array("dateFormat" => 'iso8601'))
		);
		
		$va_nonpreferred_labels = $t_entity->get('ca_entities.nonpreferred_labels', array('returnAsArray' => true));
	
		foreach($va_nonpreferred_labels as $vn_entity_id => $va_labels) {
			foreach($va_labels as $vn_i => $va_label) {
				unset($va_labels[$vn_i]['form_element']);
			}
			$va_data['alternate_names'] = $va_labels;
		}
		// add place info
		$va_places = $t_entity->getRelatedItems('ca_places');
		$t_place = new ca_places();
		
		$va_place_type_list = $t_place->getTypeList();
		
		$va_place_type_idnos = array();
		foreach($va_place_type_list as $vn_type_id => $va_type) {
			$va_place_type_idnos[] = $va_type['idno'];
		}
		$va_data['place_types'] = $va_place_type_idnos;
		
		foreach($va_places as $vn_relation_id => $va_rel_info) {
			if ($t_place->load($va_rel_info['place_id'])) {
				$va_place_ids = $t_place->get('ca_places.hierarchy.place_id', array('returnAsArray' => true));
				
				array_shift($va_place_ids);
				
				$vn_i=0;
				foreach($va_place_ids as $vn_id) {
					if ($t_place->load($vn_id)) {
						if (!($vs_type_name = $va_place_type_list[$vn_place_type_id = $t_place->get('type_id')]['idno'])) {
							$vs_type_name = $vn_place_type_id;
						}
						$va_data['locations'][$va_rel_info['relationship_type_code']][$vs_type_name] = $t_place->get('ca_places.preferred_labels.name');
						$vn_i++;
					}
				}
			}
		}
		
		// add affiliations
		$va_entities = $t_entity->getRelatedItems('ca_entities', array('restrict_to_relationship_types' => array('employer', 'affiliation')));
		$t_rel_entity = new ca_entities();

		$va_units = array();		
		foreach($va_entities as $vn_relation_id => $va_rel_info) {
			if ($t_rel_entity->load($va_rel_info['entity_id'])) {
				$va_display_names = $t_rel_entity->get('ca_entities.hierarchy.preferred_labels.displayname', array('returnAsArray' => true));
				
				if ($va_rel_info['relationship_type_code'] == 'affiliation') {
					if (sizeof($va_display_names) > 1) {
						$va_units[] = array_pop($va_display_names);
					}
				} else {
					if (sizeof($va_display_names) > 1) {
						array_pop($va_display_names);
					}
				}
				$va_data['affiliations'][$va_rel_info['relationship_type_code']][] = array(
					'displayname' => $va_display_names,
					'id' => $va_rel_info['entity_id'],
					'aliases' => array_values($t_rel_entity->get('ca_entities.nonpreferred_labels.displayname', array('returnAsArray' => true)))
				);
			}
		}
		
		// add group
		$va_data['affiliations']['group'][] = array(
			'displayname' => array($t_entity->get('ca_entities.groupName')),
			'id' => -1,
			'aliases' => array()
		);

		// Map bottom level of two level affiliation to fake "unit" relationship
		// This let's the Memorial Table CMS easily make the unit value searchable
		foreach($va_units as $vs_unit) {
			$va_data['affiliations']['unit'][] = array(
				'displayname' => array($vs_unit),
				'id' => -1,
				'aliases' => array()
			);
		}
		
		return $this->makeResponse($va_data);
	}
	
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function media(){
		if (!is_object($t_entity = $this->_checkEntity())) { return $t_entity; }
		$vn_id = $t_entity->getPrimaryKey();
		
		$t_list = new ca_lists();
		$vn_exhibition_audio_type_id = $t_list->getItemIDFromList('object_types', 'MemEx_Audio');
		
		
		$vs_filter = $this->opo_request->getParameter('filter', pString);
		if (!in_array($vs_filter, array('video', 'audio', 'image', 'pdf'))) { $vs_filter = null; }
		
		$t_object = new ca_objects();
		$va_data = array('id' => $vn_id);
		
        $from = $this->opo_request->getParameter('from', pString);
        $until = $this->opo_request->getParameter('until', pString);
        if (!$until) { $until = date('c'); }
        $vs_range = ($from && $until) ? self::utcToDb($from).' to '.self::utcToDb($until) : null;
        
        $o_tep = new TimeExpressionParser();
        $vb_parsed_date = false;
        if ($vs_range) { 
        	if ($vb_parsed_date = $o_tep->parse($vs_range)) {
        		$va_range = $o_tep->getUnixTimestamps();
        	}
        }
        $t_rep = new ca_object_representations();
		$t_list = new ca_lists();
		$va_pub_target_values = $t_list->getItemsForList('object_publication_targets', array('extractValuesByUserLocale' => true));
		$va_audio_target_values = $t_list->getItemsForList('audio_publication_targets', array('extractValuesByUserLocale' => true));
		$vn_memorial_exhibition_audio_type_id = $t_list->getItemIDFromList('object_types', 'MemEx_Audio');
		
		$vn_publish_annotation_id = $t_list->getItemIDFromList('annotation_publication_targets', 'interactive_tables');

		$vn_publish_rep = $t_list->getItemIDFromList("memex_status", "publish");
        
		$va_objects = $t_entity->getRelatedItems('ca_objects');
		foreach($va_objects as $vn_relation_id => $va_object_info) {
			$va_timestamp = $t_object->getLastChangeTimestamp($va_object_info['object_id']);
			if ($vb_parsed_date && (($va_timestamp['timestamp'] <= $va_range['start']) || ($va_timestamp['timestamp'] >= $va_range['end']))) { continue; }
			if ($t_object->load($va_object_info['object_id'])) {
				$va_reps = $t_object->getRepresentations(array("preview", "preview170", "icon", "small", "medium", "large", "large_png", "original", "h264_hi", "mp3"));
				if (!is_array($va_reps) || !sizeof($va_reps)) { continue; }
				
				$va_filtered_reps = array();
				foreach($va_reps as $vn_i => $va_rep) {
					$va_tmp = explode('/', $vs_mimetype = $va_rep['info']['original']['MIMETYPE']);
					if ($vs_filter && (($va_tmp[0] != $vs_filter) && ($va_tmp[1] != $vs_filter))) { continue; }
					
					$vb_is_audio = false;
					if ($t_object->get('type_id' )== $vn_memorial_exhibition_audio_type_id) {
						$va_pub_targets = $t_object->get('ca_objects.audio_publication_targets', array('returnAsArray' => true, 'convertCodesToDisplayText' => false));
						$vb_is_audio = true;
					} else {
						$va_pub_targets = $t_object->get('ca_objects.object_publication_targets', array('returnAsArray' => true, 'convertCodesToDisplayText' => false));	
					}
					
					if (!sizeof($va_pub_targets)) { continue; }
					
					if (!$t_rep->load($va_rep['representation_id'])) { continue; }
					if ($t_rep->get("ca_object_representations.memex_status") != $vn_publish_rep) { continue; }
					
					// reset filesize property to reflect size of version, not size of original
					foreach($va_reps[$vn_i]['paths'] as $vs_version => $vs_path) {
						$va_reps[$vn_i]['info'][$vs_version]['PROPERTIES']['filesize'] = @filesize($va_reps[$vn_i]['paths'][$vs_version]);
					}
					
					unset($va_reps[$vn_i]['paths']);
					unset($va_reps[$vn_i]['tags']);
					unset($va_reps[$vn_i]['media']);
					unset($va_reps[$vn_i]['is_primary']);
					unset($va_reps[$vn_i]['name']);
					unset($va_reps[$vn_i]['status']);
					unset($va_reps[$vn_i]['locale_id']);
					unset($va_reps[$vn_i]['type_id']);
					
					$va_reps[$vn_i]['lastupdate_timestamp'] =  date('o-m-N',$va_timestamp['timestamp'])."T".date('H:i:s',$va_timestamp['timestamp'])."Z"; //date('c', $va_timestamp['timestamp']);
					$va_reps[$vn_i]['type_id'] = $vn_type_id = $t_object->get('ca_objects.type_id');
					$va_reps[$vn_i]['typename'] = $t_object->getTypeName();
					$va_reps[$vn_i]['typecode'] = $t_object->getTypeCode();
					
					$va_targets = array();
					foreach($va_pub_targets as $vn_attr_id => $va_value) {
						$va_targets[] = ($va_pub_target_values[$va_value['object_publication_targets']]['idno']) ? $va_pub_target_values[$va_value['object_publication_targets']]['idno'] : $va_audio_target_values[$va_value['audio_publication_targets']]['idno'];
					} 
					
					
					
					$va_reps[$vn_i]['publication_targets'] = $va_targets;
					
					$va_reps[$vn_i]['title'] = $t_object->get('ca_objects.memex_title');
					$va_reps[$vn_i]['credit'] = $t_object->get('ca_objects.memex_credit_line');
					$va_reps[$vn_i]['caption'] = $t_object->get('ca_objects.memex_caption');
					
					if ($t_object->get('type_id') == $vn_exhibition_audio_type_id) {
						$va_reps[$vn_i]['transcript'] = $t_object->get('ca_objects.final_text_inner_chamber');
						$va_reps[$vn_i]['attribution'] = $t_object->get('ca_objects.remembrance_attribution');
					}	
					
					if ($va_rep['num_multifiles'] > 0) {
						$va_pages = $t_rep->getFileList($va_rep['representation_id'], null, null, array('page_preview'));
						$va_page_urls = array();
						foreach($va_pages as $vn_file_id => $va_file_info) {
							$va_page_urls[] = $va_file_info['page_preview_url'];
						}
						$va_reps[$vn_i]['page_images'] = $va_page_urls;
					}
					
					$va_reps[$vn_i]['clips'] = array();
					if (is_array($va_annotations = $t_rep->getAnnotations()) && sizeof($va_annotations)) {
						foreach($va_annotations as $vn_annotation_id => $va_annotation) {
							//if ($va_annotation['access'] == 0) { continue; }
							$t_annotation = new ca_representation_annotations($vn_annotation_id);
							
							if ($vn_publish_annotation_id != $t_annotation->get('annotation_publication_targets')) { continue; } 	// skip ones not marked for publication
							
							unset($va_annotation['props']);
							$va_annotation['description'] = $t_annotation->get('description');
							$va_annotation['transcript'] = $t_annotation->get('transcript');
							
							//$va_annotation['mp3'] = $t_annotation->getAppConfig()->get('site_host')."/service.php/ns11mmServices/Victim/getClip/id/{$vn_annotation_id}";
							$va_annotation['mp3'] = $t_annotation->getMediaUrl('ca_representation_annotations.preview', 'original');
							$va_annotation['md5'] = $t_annotation->getMediaInfo('ca_representation_annotations.preview', 'original', 'MD5');
							
							$va_reps[$vn_i]['clips'][] = $va_annotation;
						} 
					}
					
					$va_filtered_reps[] = $va_reps[$vn_i];
				}
				
				if (sizeof($va_filtered_reps)) {
					$va_data['media'][$va_object_info['object_id']] = $va_filtered_reps;
				}
			}
		}
		
		return $this->makeResponse($va_data);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function relationships(){
		if (!is_object($t_entity = $this->_checkEntity())) { return $t_entity; }
		$vn_id = $t_entity->getPrimaryKey();
		$vs_group = $t_entity->get('ca_entities.groupName');
		
		$va_data = array('aliases' => $t_entity->get('ca_entities.nonpreferred_labels.displayname', array('returnAsArray' => true)));
		
		$va_entities = $t_entity->getRelatedItems('ca_entities');
		$va_timestamp = $t_entity->getLastChangeTimestamp($vn_id);
		
		$va_units = array();
		foreach($va_entities as $vn_relation_id => $va_rel_info) {
			if ($t_entity->load($va_rel_info['entity_id'])) {
				$va_data['lastupdate_timestamp'] =  date('o-m-N',$va_timestamp['timestamp'])."T".date('H:i:s',$va_timestamp['timestamp'])."Z";

				$va_display_names = $t_entity->get('ca_entities.hierarchy.preferred_labels.displayname', array('returnAsArray' => true));
				if ($va_rel_info['relationship_type_code'] == 'affiliation') {
					if (sizeof($va_display_names) > 1) {
						$va_units[] = array_pop($va_display_names);
					}
				} else {
					if (sizeof($va_display_names) > 1) {
						array_pop($va_display_names);
					}
				}
				$va_data['relationships'][$va_rel_info['relationship_type_code']][] = array(
					'id' => $va_rel_info['entity_id'],
					'displayname' => $va_display_names,
					'aliases' => $t_entity->get('ca_entities.nonpreferred_labels.displayname', array('returnAsArray' => true))
				);
			}
		}
		
		// Map bottom level of two level affiliation to fake "unit" relationship
		// This let's the Memorial Table CMS easily make the unit value searchable
		foreach($va_units as $vs_unit) {
			$va_data['relationships']['unit'][] = array(
				'displayname' => array($vs_unit),
				'id' => -1,
				'aliases' => array()
			);
		}
		
		
		// add group
		$va_data['relationships']['group'][] = array(
			'displayname' => array($vs_group),
			'id' => -1,
			'aliases' => array()
		);
		
		return $this->makeResponse($va_data);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getObjectTypeList(){
		
		$va_data = array();
		
		$t_list = new ca_lists();
		
		$va_types = $t_list->getItemsForList('object_types', array('extractValuesByUserLocale' => true));
		
		foreach($va_types as $vn_type_id => $va_type) {
			$va_data[$vn_type_id] = array(
				'type_id' => $vn_type_id,
				'typecode' => $va_type['idno'],
				'typename' => $va_type['name_plural'],
				'parent_id' => $va_type['parent_id']
			);
		}
		
		return $this->makeResponse($va_data);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getDeletions(){
		//if (!is_object($t_entity = $this->_checkEntity())) { return $t_entity; }
		//$vn_id = $t_entity->getPrimaryKey();
		
		$t_object = new ca_objects();
		//$va_data = array('id' => $vn_id);
		
        $from = $this->opo_request->getParameter('from', pString);
        $until = $this->opo_request->getParameter('until', pString);
        if (!$until) { $until = date('c'); }
        $vs_range = ($from && $until) ? self::utcToDb($from).' to '.self::utcToDb($until) : null;
     
        $o_tep = new TimeExpressionParser();
        $vb_parsed_date = false;
        if ($vs_range) { 
        	if ($vb_parsed_date = $o_tep->parse($vs_range)) {
        		$va_range = $o_tep->getUnixTimestamps();
        	}
        }
        
        $o_log = new ApplicationChangeLog();
       // $va_log = $o_log->getChangeLogForRow($t_entity, array('range' => $va_range, 'changeType' => 'D'));
        $va_log = $o_log->getDeletions('ca_objects', array('range' => $va_range));
        $va_data['deletions'] = $va_log;
        // 
//         foreach($va_log as $vs_key => $va_log_item) {
//         	foreach($va_log_item as $vn_i => $va_log) {
//         		if (!is_array($va_log['changes'])) { continue; }
//         		foreach($va_log['changes'] as $vn_j => $va_change) {
//         			$va_data[$va_change['table_name']][] = array(
//         				'datetime' => $va_log['datetime'],
//         				'row_id' => $va_change['row_id'],
//         				'description' => $va_change['description'],
//         				'idno' => $va_change['idno'] 
//         			);
//         		}
//         	}
//         }
//      	
		return $this->makeResponse($va_data);   
    }
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getClip(){
		$pn_id = $this->opo_request->getParameter('id', pInteger);
	
		$va_data = array();
		
		$t_annotation = new ca_representation_annotations($pn_id);
		if ($t_annotation->getPrimaryKey()) {
			$vs_start = $t_annotation->getPropertyValue('startTimecode');
			$vs_end = $t_annotation->getPropertyValue('endTimecode');
			
			$va_data['start'] = $vs_start;
			$va_data['end'] = $vs_end;
			
			$t_rep = new ca_object_representations($t_annotation->get('representation_id'));
			$va_data['file'] = $vs_file = $t_rep->getMediaPath('media', 'original');
			$o_media = new Media();
			if ($o_media->read($vs_file)) {
				$o_media->writeClip($vs_file = tempnam('/tmp', 'meow'), $vs_start, $vs_end);
			}
			
			header("Content-type: audio/mpeg");
			header("Content-length: ".@filesize($vs_file));
			readfile($vs_file);
			return;
		}
		
		return $this->makeResponse($va_data, 500, "No such clip");
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	private function _checkEntity(){
		$t_entity = new ca_entities($vn_id = $this->opo_request->getParameter('id', pInteger));
		
		if (!$t_entity->getPrimaryKey()) {
			return $this->makeResponse(array(), 500, 'Victim ID is invalid');
		}
		
		// is the entity a victim?
		if ($t_entity->getTypeID() != $this->opn_victim_type_id) {
			return $this->makeResponse(array(), 500, 'Entity is not of type "victim"');
		}
		
		return $t_entity;
	}
	# -------------------------------------------------------
}
