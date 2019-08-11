<?php
/** ---------------------------------------------------------------------
 * app/lib/RepresentableBaseModel.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2018 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/BundlableLabelableBaseModelWithAttributes.php');
 require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
	class RepresentableBaseModel extends BundlableLabelableBaseModelWithAttributes {
		# ------------------------------------------------------
		/**
		 * Returns information about representations linked to the currently loaded item. Use this if you want to get the urls, tags and other information for all representations associated with a given item.
		 *
		 * @param array $pa_versions An array of media versions to include information for. If you omit this then a single version, 'preview170', is assumed by default.
		 * @param array $pa_version_sizes Optional array of sizes to force specific versions to. The array keys are version names; the values are arrays with two keys: 'width' and 'height'; if present these values will be used in lieu of the actual values in the database
		 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
		 *		return_primary_only - If true then only the primary representation will be returned [Default is false]
		 *      primaryOnly = Synonym for return_primary_only
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *		start = 
		 *		limit = 
		 *		restrict_to_types = An array of type_ids or type codes to restrict count to specified types of representations to
		 *		restrict_to_relationship_types = An array of relationship type_ids or relationship codes to restrict count to
		 *		.. and options supported by getMediaTag() .. [they are passed through]
		 *	
		 * @return array An array of information about the linked representations
		 */
		public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
			global $AUTH_CURRENT_USER_ID;
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$pn_start = caGetOption('start', $pa_options, 0);
			$pn_limit = caGetOption('limit', $pa_options, null);
		
		
			if (caGetBundleAccessLevel($this->tableName(), 'ca_object_representations') == __CA_BUNDLE_ACCESS_NONE__) {
				return null;
			}
		
			if (!is_array($pa_versions)) { 
				$pa_versions = array('preview170');
			}
		
		    if (caGetOption(['primaryOnly', 'return_primary_only'], $pa_options, false)) {
				$vs_is_primary_sql = ' AND (caoor.is_primary = 1)';
			} else {
				$vs_is_primary_sql = '';
			}
		
			if ($pa_options['checkAccess']) { $pa_options['return_with_access'] = $pa_options['checkAccess']; }
			if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
				$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
			} else {
				$vs_access_sql = '';
			}

			$o_db = $this->getDb();
			
			if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName()))) { return null; }
			$vs_pk = $this->primaryKey();
			$vs_limit_sql = '';
			if ($pn_limit > 0) {
				if ($pn_start > 0) {
					$vs_limit_sql = "LIMIT {$pn_start}, {$pn_limit}";
				} else {
					$vs_limit_sql = "LIMIT {$pn_limit}";
				}
			}
			
			$va_type_restriction_filters = $this->_getRestrictionSQL($vs_linking_table, (int)$vn_id, $pa_options);
		
			$qr_reps = $o_db->query($vs_sql = "
				SELECT caor.representation_id, caor.media, caoor.is_primary, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id, caor.idno, caor.idno_sort, caor.md5, caor.mimetype, caor.original_filename, caoor.rank, caoor.relation_id
				FROM ca_object_representations caor
				INNER JOIN {$vs_linking_table} AS caoor ON caor.representation_id = caoor.representation_id
				LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
				WHERE
					caoor.{$vs_pk} = ? AND deleted = 0
					{$vs_is_primary_sql}
					{$vs_access_sql}
					{$va_type_restriction_filters['sql']}
				ORDER BY
					caoor.rank, caoor.is_primary DESC
				{$vs_limit_sql}
			", $va_type_restriction_filters['params']);
			
			$va_reps = array();
			$t_rep = new ca_object_representations();
			
			if($AUTH_CURRENT_USER_ID) {
				$va_can_read = caCanRead($AUTH_CURRENT_USER_ID, 'ca_object_representations', $qr_reps->getAllFieldValues('representation_id'), null, array('returnAsArray' => true));
			} else {
				$va_can_read = $qr_reps->getAllFieldValues('representation_id');
			}
			
			// reexecute query as pdo doesn't support seek()
			$qr_reps = $o_db->query($vs_sql, $va_type_restriction_filters['params']);
			while($qr_reps->nextRow()) {
				$vn_rep_id = $qr_reps->get('representation_id');
				
				if ($va_can_read && !in_array($vn_rep_id, $va_can_read)) { continue; }
			
				$va_tmp = $qr_reps->getRow();
				$va_tmp['tags'] = array();
				$va_tmp['urls'] = array();
			
				$va_info = $qr_reps->getMediaInfo('media');
				$va_tmp['info'] = array('original_filename' => $va_info['ORIGINAL_FILENAME']);
				foreach ($pa_versions as $vs_version) {
					if (is_array($pa_version_sizes) && isset($pa_version_sizes[$vs_version])) {
						$vn_width = $pa_version_sizes[$vs_version]['width'];
						$vn_height = $pa_version_sizes[$vs_version]['height'];
					} else {
						$vn_width = $vn_height = 0;
					}
				
					if ($vn_width && $vn_height) {
						$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, array_merge($pa_options, array('viewer_width' => $vn_width, 'viewer_height' => $vn_height)));
					} else {
						$va_tmp['tags'][$vs_version] = $qr_reps->getMediaTag('media', $vs_version, $pa_options);
					}
					$va_tmp['urls'][$vs_version] = $qr_reps->getMediaUrl('media', $vs_version);
					$va_tmp['paths'][$vs_version] = $qr_reps->getMediaPath('media', $vs_version);
					$va_tmp['info'][$vs_version] = $qr_reps->getMediaInfo('media', $vs_version);
				
					$va_tmp['dimensions'][$vs_version] = caGetRepresentationDimensionsForDisplay($qr_reps, 'original', array());
				}
				
				if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
					$va_tmp['fetched_from'] = $vs_fetched_from_url;
					$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
				}

				if (isset($va_info['REPLICATION_KEYS'])) {
					$va_tmp['REPLICATION_KEYS'] = $va_info['REPLICATION_KEYS'];
				}
			
				$va_tmp['num_multifiles'] = $t_rep->numFiles($vn_rep_id);

				$va_captions = $t_rep->getCaptionFileList($vn_rep_id);
				if(is_array($va_captions) && (sizeof($va_captions)>0)){
					$va_tmp['captions'] = $va_captions;	
				}

				$va_reps[$vn_rep_id] = $va_tmp;
			}
		
			$va_labels = $t_rep->getPreferredDisplayLabelsForIDs(array_keys($va_reps));
			foreach($va_labels as $vn_rep_id => $vs_label) {
				$va_reps[$vn_rep_id]['label'] = $vs_label;
			}
			
			return $va_reps;
		}
		# ------------------------------------------------------
		/**
		 * General SQL query WHERE clauses and parameters to restrict queries to specific representation and/or relationship types
		 */
		private function _getRestrictionSQL($ps_linking_table, $pn_id, $pa_options) {
			$va_restrict_to_types = caGetOption('restrictToTypes', $pa_options, caGetOption('restrict_to_types', $pa_options, null));
			$va_restrict_to_relationship_types = caGetOption('restrictToRelationshipTypes', $pa_options, caGetOption('restrict_to_relationship_types', $pa_options, null));
		
			$vs_filter_sql = '';
			$pa_params = array($pn_id);
			if ($va_restrict_to_relationship_types || $va_restrict_to_types) {
				if ($va_restrict_to_relationship_types && ($t_rel = Datamodel::getInstanceByTableName($ps_linking_table, true)) && ($t_rel->hasField('type_id'))) {
					$va_restrict_to_relationship_types = caMakeRelationshipTypeIDList($ps_linking_table, $va_restrict_to_relationship_types);
					
					if (is_array($va_restrict_to_relationship_types) && sizeof($va_restrict_to_relationship_types)) {
						$vs_filter_sql .= " AND (caoor.type_id IN (?))";
						$pa_params[] = $va_restrict_to_relationship_types; 
					}
				}
				
				
				if ($va_restrict_to_types) {
					$va_restrict_to_types = caMakeTypeIDList('ca_object_representations', $va_restrict_to_types);
					if (is_array($va_restrict_to_types) && sizeof($va_restrict_to_types)) {
						$vs_filter_sql .= " AND (caor.type_id IN (?))";
						$pa_params[] = $va_restrict_to_types; 
					}
				}
			}
			
			return array('sql' => $vs_filter_sql, 'params' => $pa_params);
		}
		# ------------------------------------------------------
		/**
		 * Finds and returns information about representations meeting the specified criteria. Returns information in the same format as getRepresentations()
		 *
		 * @param array $pa_options Array of criteria options to use when selecting representations. Options include: 
		 *		mimetypes = array of mimetypes to return
		 *		sortby = if set, representations are return sorted using the criteria in ascending order. Valid values are 'filesize' (sort by file size), 'duration' (sort by length of time-based media)
		 *
		 * @return array List of representations. Each entry in the list is an associative array of the same format as returned by getRepresentations() and includes properties, tags and urls for the representation.
		 */
		public function findRepresentations($pa_options) {
			$va_mimetypes = array();
			if (isset($pa_options['mimetypes']) && (is_array($pa_options['mimetypes'])) && (sizeof($pa_options['mimetypes']))) {
				$va_mimetypes = array_flip($pa_options['mimetypes']);
			}
		
			$vs_sortby = null;
			if (isset($pa_options['sortby']) && $pa_options['sortby'] && in_array($pa_options['sortby'], array('filesize', 'duration'))) {
				$vs_sortby = $pa_options['sortby'];
			}
		
			$va_reps = $this->getRepresentations(array('original'));
			$va_found_reps = array();
			foreach($va_reps as $vn_i => $va_rep) {
				if(is_array($va_mimetypes) && (sizeof($va_mimetypes)) && isset($va_mimetypes[$va_rep['info']['original']['MIMETYPE']])) {
					switch($vs_sortby) {
						case 'filesize':
							$va_found_reps[$va_rep['info']['original']['FILESIZE']][] = $va_rep;
							break;
						case 'duration':
							$vn_duration = $va_rep['info']['original']['PROPERTIES']['duration'];
							$va_found_reps[$vn_duration][] = $va_rep;
							break;
						default:
							$va_found_reps[] = $va_rep;
							break;
					}
				}
			}
		
			if ($vs_sortby) {
				ksort($va_found_reps);
			
				$va_tmp = array();
				foreach($va_found_reps as $va_found_rep_groups) {
					foreach($va_found_rep_groups as $va_found_rep) {
						$va_tmp[] = $va_found_rep;
					}
				}
				$va_found_reps = $va_tmp;
			}
		
			return $va_found_reps;
		}
		# ------------------------------------------------------
		# Representations
		# ------------------------------------------------------
		/**
		 * Returns array with keys for representation_ids for all representations linked to the currently loaded row
		 *
		 * @param array $pa_options An array of options. Supported options are:
		 *		return_primary_only - If true then only the primary representation will be returned
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *		restrict_to_types = An array of type_ids or type codes to restrict count to specified types of representations to
		 *		restrict_to_relationship_types = An array of relationship type_ids or relationship codes to restrict count to
		 *
		 * @return array A list of representation_ids
		 */
		public function getRepresentationIDs($pa_options=null) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
		
			if (!is_array($pa_versions)) { 
				$pa_versions = array('preview170');
			}
		
			if (isset($pa_options['return_primary_only']) && $pa_options['return_primary_only']) {
				$vs_is_primary_sql = ' AND (caoor.is_primary = 1)';
			} else {
				$vs_is_primary_sql = '';
			}
		
			if (!is_array($pa_options['return_with_access']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) > 0) {
				$pa_options['return_with_access'] = $pa_options['checkAccess'];
			}
		
			if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
				$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
			} else {
				$vs_access_sql = '';
			}
			
			if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName()))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$va_type_restriction_filters = $this->_getRestrictionSQL($vs_linking_table, (int)$vn_id, $pa_options);
		
		
			$o_db = $this->getDb();
			$qr_reps = $o_db->query("
				SELECT caor.representation_id, caoor.is_primary
				FROM ca_object_representations caor
				INNER JOIN {$vs_linking_table} AS caoor ON caor.representation_id = caoor.representation_id
				WHERE
					caoor.{$vs_pk} = ? AND caor.deleted = 0
					{$vs_is_primary_sql}
					{$vs_access_sql}
					{$va_type_restriction_filters['sql']}
				ORDER BY
					caoor.rank, caoor.is_primary DESC
			", $va_type_restriction_filters['params']);
		
			$va_rep_ids = array();
			while($qr_reps->nextRow()) {
				$va_rep_ids[$qr_reps->get('representation_id')] = ($qr_reps->get('is_primary') == 1) ? true : false;
			}
			return $va_rep_ids;
		}
		# ------------------------------------------------------
		/**
		 * Returns number of representations attached to the currently loaded row
		 *
		 * @param array $pa_options Optional array of options. Supported options include:
		 *		restrict_to_types = An array of type_ids or type codes to restrict count to specified types of representations to
		 *		restrict_to_relationship_types = An array of relationship type_ids or relationship codes to restrict count to
		 *		return_with_type - A type to restrict the count to. Can be either an integer type_id or item_code string
		 *		return_with_access - An array of access values to restrict counts to
		 *		checkAccess - synonym for return_with_access
		 *
		 * @return integer The number of representations
		 */
		public function getRepresentationCount($pa_options=null) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			if (!is_array($pa_options)) { $pa_options = array(); }
		
			$vs_type_sql = '';
			if (isset($pa_options['return_with_type']) && $pa_options['return_with_type']) {
				if (!is_numeric($pa_options['return_with_type'])) {
					$t_list = new ca_lists();
					$pa_options['return_with_type'] = $t_list->getItemIDFromList('object_representation_types', $pa_options['return_with_type']);
				}
				if (intval($pa_options['return_with_type']) > 0) {
					$vs_type_sql = ' AND (caor.type_id = '.intval($pa_options['return_with_type']).')';
				}
			} 
		
			if (!is_array($pa_options['return_with_access']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess']) > 0) {
				$pa_options['return_with_access'] = $pa_options['checkAccess'];
			}
		
			if (is_array($pa_options['return_with_access']) && sizeof($pa_options['return_with_access']) > 0) {
				$vs_access_sql = ' AND (caor.access IN ('.join(", ", $pa_options['return_with_access']).'))';
			} else {
				$vs_access_sql = '';
			}
	
			if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName()))) { return null; }
			$vs_pk = $this->primaryKey();
		
			$va_type_restriction_filters = $this->_getRestrictionSQL($vs_linking_table, (int)$vn_id, $pa_options);
			
			$o_db = $this->getDb();
		
			$qr_reps = $o_db->query("
				SELECT count(*) c
				FROM ca_object_representations caor
				INNER JOIN {$vs_linking_table} AS caoor ON caor.representation_id = caoor.representation_id
				LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
				WHERE
					caoor.{$vs_pk} = ? AND caor.deleted = 0
					{$vs_type_sql}
					{$vs_access_sql}
					{$va_type_restriction_filters['sql']}
			", $va_type_restriction_filters['params']);

			$qr_reps->nextRow();
		
			return (int)$qr_reps->get('c');
		}
		# ------------------------------------------------------
		/**
		 * Returns primary representation for the item; versions specified in $pa_versions are included. See description
		 * of self::getRepresentations() for a description of returned values.
		 *
		 * @param array $pa_versions An array of media versions to include information for. If you omit this then a single version, 'preview170', is assumed by default.
		 * @param array $pa_version_sizes Optional array of sizes to force specific versions to. The array keys are version names; the values are arrays with two keys: 'width' and 'height'; if present these values will be used in lieu of the actual values in the database
		 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *		.. and options supported by getMediaTag() .. [they are passed through]
		 *
		 * @return array An array of information about the linked representations
		 */
		public function getPrimaryRepresentation($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(is_array($va_reps = $this->getRepresentations($pa_versions, $pa_version_sizes, array_merge($pa_options, array('return_primary_only' => 1))))) {
				return array_pop($va_reps);
			}
			return array();
		}
		# ------------------------------------------------------
		/**
		 * Returns representation_id of primary representation for the item.
		 *
		 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *
		 * @return integer A representation_id
		 */
		public function getPrimaryRepresentationID($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_rep_ids = $this->getRepresentationIDs(array_merge($pa_options, array('return_primary_only' => 1)));
			if (!is_array($va_rep_ids)) { return null; }
			foreach($va_rep_ids as $vn_representation_id => $vb_is_primary) {
				if ($vb_is_primary) { return $vn_representation_id; }
			}
			return null;
		}
		# ------------------------------------------------------
		/**
		 * Returns ca_object_representations instance loaded with primary representation for the current row
		 *
		 * @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *
		 * @return ca_object_representation A model instance for the primary representation
		 */
		public function getPrimaryRepresentationInstance($pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!($vn_rep_id = $this->getPrimaryRepresentationID($pa_options))) { return null; }
		
			$t_rep = new ca_object_representations($vn_rep_id);
		
			return ($t_rep->getPrimaryKey()) ? $t_rep : null;
		}
		# ------------------------------------------------------
		/**
		 * Returns representations linked to the currently loaded item in a SearchResult instance. 
		 * Use this if you want to efficiently access information, including attributes, labels and intrinsics, for all representations associated with a given item.
		 *
		 *  @param array $pa_options An optional array of options to use when getting representation information. Supported options are:
		 *		return_primary_only - If true then only the primary representation will be returned
		 *		return_with_access - Set to an array of access values to filter representation through; only representations with an access value in the list will be returned
		 *		checkAccess - synonym for return_with_access
		 *
		 * @return SearchResult Search result containing all representations linked to the currently loaded item
		 */
		public function getRepresentationsAsSearchResult($pa_options=null) {
			$va_representation_ids = $this->getRepresentationIDs($pa_options);
		
			if(is_array($va_representation_ids) && sizeof($va_representation_ids)) {
				return $this->makeSearchResult('ca_object_representations', array_keys($va_representation_ids));
			}
			return null;
		}
		# ------------------------------------------------------
		/** 
		 * Add media represention to currently loaded item
		 *
		 * @param $ps_media_path - the path to the media you want to add
		 * @param $pn_type_id - the item_id of the representation type, in the ca_list with list_code 'object_represention_types'
		 * @param $pn_locale_id - the locale_id of the locale of the representation
		 * @param $pn_status - the status code for the representation (as defined in item_value fields of items in the 'workflow_statuses' ca_list)
		 * @param $pn_access - the access code for the representation (as defined in item_value fields of items in the 'access_statuses' ca_list)
		 * @param $pb_is_primary - if set to true, representation is designated "primary." Primary representation are used in cases where only one representation is required (such as search results). If a primary representation is already attached to this item, then it will be changed to non-primary as only one representation can be primary at any given time. If no primary representations exist, then the new representation will always be marked primary no matter what the setting of this parameter (there must always be a primary representation, if representations are defined).
		 * @param $pa_values - array of attributes to attach to new representation
		 * @param $pa_options - an array of options passed through to BaseModel::set() when creating the new representation. Currently supported options:
		 *		original_filename - the name of the file being uploaded; will be recorded in the database and used as the filename when the file is subsequently downloaded
		 *		rank - a numeric rank used to order the representations when listed
		 *		returnRepresentation = if set the newly created ca_object_representations instance is returned rather than the link_id of the newly created relationship record
		 *		matchOn = 
		 *		centerX = Horizontal position of image center used when cropping as a percentage expressed as a decimal between 0 and 1. If omitted existing value is maintained. Note that both centerX and centerY must be specified for the center to be changed.
		 *		centerY = Vertical position of image center used when cropping as a percentage expressed as a decimal between 0 and 1. If omitted existing value is maintained. Note that both centerX and centerY must be specified for the center to be changed.
		 *
		 * @return mixed Returns primary key (link_id) of the relatipnship row linking the newly created representation to the item; if the 'returnRepresentation' is set then an instance for the newly created ca_object_representations is returned instead; boolean false is returned on error
		 */
		public function addRepresentation($ps_media_path, $pn_type_id, $pn_locale_id, $pn_status, $pn_access, $pb_is_primary, $pa_values=null, $pa_options=null) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			if (!$pn_locale_id) { $pn_locale_id = ca_locales::getDefaultCataloguingLocaleID(); }
		
		
			$t_rep = new ca_object_representations();
		
			if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
				
			$vn_rep_id = null;
			if(is_array($va_match_on = caGetOption('matchOn', $pa_options, null))) {
				$va_ids = null;
				foreach($va_match_on as $vs_match_on) {
					switch($vs_match_on) {
						case 'idno':
							if (!trim($pa_values['idno'])) { break; }
							$va_ids = ca_object_representations::find(array('idno' => trim($pa_values['idno'])), array('returnAs' => 'ids'));
							break;
						case 'label':
							if (!trim($pa_values['preferred_labels']['name'])) { break; }
							$va_ids = ca_object_representations::find(array('preferred_labels' => array('name' => trim($pa_values['preferred_labels']['name']))), array('returnAs' => 'ids'));
							break;
					}
					if(is_array($va_ids) && sizeof($va_ids)) { 
						$vn_rep_id = array_shift($va_ids);
						break; 
					}
					
				}
			}
			
			if (!$vn_rep_id) {
				$t_rep->setMode(ACCESS_WRITE);
				$t_rep->set('type_id', $pn_type_id);
				$t_rep->set('locale_id', $pn_locale_id);
				$t_rep->set('status', $pn_status);
				$t_rep->set('access', $pn_access);
				$t_rep->set('media', $ps_media_path, $pa_options);
		
				if ($o_idno = $t_rep->getIDNoPlugInInstance()) {
					$t_rep->setIdnoWithTemplate($o_idno->makeTemplateFromValue(''));
				}
				if (is_array($pa_values)) {
					if (isset($pa_values['idno'])) {
						$t_rep->set('idno', $pa_values['idno']);
						unset($pa_values['idno']);
					}
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_rep->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_rep->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
				}
		
				$t_rep->insert();
		
				if ($t_rep->numErrors()) {
					$this->errors = array_merge($this->errors, $t_rep->errors());
					return false;
				}
			
				if ($t_rep->getPreferredLabelCount() == 0) {
					$vs_label = (isset($pa_values['name']) && $pa_values['name']) ? $pa_values['name'] : '['.caGetBlankLabelText().']';
			
					$t_rep->addLabel(array('name' => $vs_label), $pn_locale_id, null, true);
					if ($t_rep->numErrors()) {
						$this->errors = array_merge($this->errors, $t_rep->errors());
						return false;
					}
				}
			} else {
				$t_rep->load($vn_rep_id);
				$t_rep->setMode(ACCESS_WRITE);
				
				$t_rep->set('status', $pn_status);
				$t_rep->set('access', $pn_access);
				if ($ps_media_path) { $t_rep->set('media', $ps_media_path, $pa_options); }
		
				if (is_array($pa_values)) {
					if (isset($pa_values['idno'])) {
						$t_rep->set('idno', $pa_values['idno']);
						unset($pa_values['idno']);
					}
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_rep->replaceAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_rep->replaceAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
				}
				$t_rep->update();
				if ($t_rep->numErrors()) {
					$this->errors = array_merge($this->errors, $t_rep->errors());
					return false;
				}
			}
			
			// Set image center if specified
			$vn_center_x = caGetOption('centerX', $pa_options, null);
			$vn_center_y = caGetOption('centerY', $pa_options, null);
			if (strlen($vn_center_x) && (strlen($vn_center_y)) && ($vn_center_x >= 0) && ($vn_center_y >= 0) && ($vn_center_x <= 1) && ($vn_center_y <= 1)) {
				$t_rep->setMediaCenter('media', (float)$vn_center_x, (float)$vn_center_y);
				$t_rep->update();
				if ($t_rep->numErrors()) {
					$this->errors = array_merge($this->errors, $t_rep->errors());
					return false;
				}
			}
			
			if (!($t_oxor = $this->_getRepresentationRelationshipTableInstance())) { return null; }
			$vs_pk = $this->primaryKey();
			
			if ($this->inTransaction()) { $t_oxor->setTransaction($this->getTransaction()); }
			$t_oxor->setMode(ACCESS_WRITE);
			$t_oxor->set($vs_pk, $vn_id);
			$t_oxor->set('representation_id', $t_rep->getPrimaryKey());
			$t_oxor->set('is_primary', $pb_is_primary ? 1 : 0);
			$t_oxor->set('rank', isset($pa_options['rank']) ? (int)$pa_options['rank'] : $t_rep->getPrimaryKey());
			if ($t_oxor->hasField('type_id')) { $t_oxor->set('type_id', isset($pa_options['type_id']) ? $pa_options['type_id'] : null); }
			$t_oxor->insert();
		
		
			if ($t_oxor->numErrors()) {
				$this->errors = array_merge($this->errors, $t_oxor->errors());
				//$t_rep->delete();
				//if ($t_rep->numErrors()) {
				//	$this->errors = array_merge($this->errors, $t_rep->errors());
				//}
				return false;
			}
			
			//
			// Perform mapping of embedded metadata for newly uploaded representation with respect
			// to ca_objects and ca_object_representation records
			//
			$va_metadata = $t_rep->get('media_metadata', array('binary' => true));
			if (caExtractEmbeddedMetadata($this, $va_metadata, $pn_locale_id)) {
				$this->update();
			}
			
			
			// Trigger automatic replication
			$va_auto_targets = $t_rep->getAvailableMediaReplicationTargets('media', 'original', array('trigger' => 'auto', 'access' => $t_rep->get('access')));
			if(is_array($va_auto_targets)) {
				foreach($va_auto_targets as $vs_target => $va_target_info) {
					$t_rep->replicateMedia('media', $vs_target);
				}
			}
		
			if (isset($pa_options['returnRepresentation']) && (bool)$pa_options['returnRepresentation']) {
				return $t_rep;
			} 
			return $t_oxor->getPrimaryKey();
		}
		# ------------------------------------------------------
		/**
		 * Convenience method to edit a representation instance. Allows to you edit a linked representation from an instance.
		 * 
		 * @param int representation_id
		 * @param string $ps_media_path
		 * @param int $pn_locale_id
		 * @param int $pn_status
		 * @param int $pn_access
		 * @param bool $pb_is_primary Sets 'primaryness' of representation. If you wish to leave the primary setting to its current value set this null or omit the parameter.
		 * @param array $pa_values
		 * @param array $pa_options
		 *		centerX = Horizontal position of image center used when cropping as a percentage expressed as a decimal between 0 and 1. If omitted existing value is maintained. Note that both centerX and centerY must be specified for the center to be changed.
		 *		centerY = Vertical position of image center used when cropping as a percentage expressed as a decimal between 0 and 1. If omitted existing value is maintained. Note that both centerX and centerY must be specified for the center to be changed.
		 *      label = Preferred label in specified locale for representation. [Default is null]
		 *      type_id = Type to force representation to. [Default is null]
		 * @return bool True on success, false on failure, null if no row has been loaded into the object model 
		 */
		public function editRepresentation($pn_representation_id, $ps_media_path, $pn_locale_id, $pn_status, $pn_access, $pb_is_primary=null, $pa_values=null, $pa_options=null) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			$va_old_replication_keys = array();
			
			$t_rep = new ca_object_representations();
			if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction());}
			if (!$t_rep->load(array('representation_id' => $pn_representation_id))) {
				$this->postError(750, _t("Representation id=%1 does not exist", $pn_representation_id), "RepresentableBaseModel->editRepresentation()");
				return false;
			} else {
				$t_rep->setMode(ACCESS_WRITE);
				if ($pn_locale_id) { $t_rep->set('locale_id', $pn_locale_id); }
				if (!is_null($pn_status)) { $t_rep->set('status', $pn_status); }
				if (!is_null($pn_access)) { $t_rep->set('access', $pn_access); }
				if ($pm_type_id = caGetOption('type_id', $pa_options, null)) {  $t_rep->set('type_id', $pm_type_id, ['allowSettingOfTypeID' => true]); }
			
				if ($ps_media_path) {
					if(is_array($va_replication_targets = $t_rep->getUsedMediaReplicationTargets('media'))) {
						foreach($va_replication_targets as $vs_target => $va_target_info) {
							$va_old_replication_keys[$vs_target] = $t_rep->getMediaReplicationKey('media', $vs_target);
						}
					}
					$t_rep->set('media', $ps_media_path, $pa_options);
				}
			
				if (is_array($pa_values)) {
					if (isset($pa_values['idno'])) {
						$t_rep->set('idno', $pa_values['idno']);
					}
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_rep->replaceAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_rep->replaceAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
				}
			
				$t_rep->update();
			
				if ($t_rep->numErrors()) {
					$this->errors = array_merge($this->errors, $t_rep->errors());
					return false;
				}
				
				// Set image center if specified
				$vn_center_x = caGetOption('centerX', $pa_options, null);
				$vn_center_y = caGetOption('centerY', $pa_options, null);
				if (strlen($vn_center_x) && (strlen($vn_center_y)) && ($vn_center_x >= 0) && ($vn_center_y >= 0) && ($vn_center_x <= 1) && ($vn_center_y <= 1)) {
					$t_rep->setMediaCenter('media', (float)$vn_center_x, (float)$vn_center_y);
					if ($t_rep->numErrors()) {
                        $this->errors = array_merge($this->errors, $t_rep->errors());
                        return false;
                    }
				}
				
				if ($pn_locale_id && ($ps_label = caGetOption('label', $pa_options, null))) {
				    $t_rep->replaceLabel(array('name' => $ps_label), $pn_locale_id, null, true, array('queueIndexing' => true));
				    if ($t_rep->numErrors()) {
                        $this->errors = array_merge($this->errors, $t_rep->errors());
                        return false;
                    }
				}
					
				if ($ps_media_path) {
					// remove any replicated media
					foreach($va_old_replication_keys as $vs_target => $vs_old_replication_key) {
						$t_rep->removeMediaReplication('media', $vs_target, $vs_old_replication_key, array('force' => true));
					}
					
					// Trigger automatic replication
					$va_auto_targets = $t_rep->getAvailableMediaReplicationTargets('media', 'original', array('trigger' => 'auto', 'access' => $t_rep->get('access')));
					if(is_array($va_auto_targets)) {
						foreach($va_auto_targets as $vs_target => $va_target_info) {
							$t_rep->replicateMedia('media', $vs_target);
						}
					}
				}
			
				if (!($t_oxor = $this->_getRepresentationRelationshipTableInstance())) { return null; }
				$vs_pk = $this->primaryKey();
				
				if ($this->inTransaction()) { $t_oxor->setTransaction($this->getTransaction());}
				
				if (!$t_oxor->load(array($vs_pk => $vn_id, 'representation_id' => $pn_representation_id))) {
					$this->postError(750, _t("Representation id=%1 is not related to %3 id=%2", $pn_representation_id, $vn_id, $this->getProperty('NAME_SINGULAR')), "RepresentableBaseModel->editRepresentation()");
					return false;
				} else {
					$t_oxor->setMode(ACCESS_WRITE);
					if (!is_null($pb_is_primary)) {
						$t_oxor->set('is_primary', (bool)$pb_is_primary ? 1 : 0);
					}
					if (isset($pa_options['rank']) && ($vn_rank = (int)$pa_options['rank'])) {
						$t_oxor->set('rank', $vn_rank);
					}
				
					$t_oxor->update();
				
					if ($t_oxor->numErrors()) {
						$this->errors = array_merge($this->errors, $t_oxor->errors());
						return false;
					}
				}
			
				return true;
			}
			return false;
		}
		# ------------------------------------------------------
		/**
		 * Remove a single representation from the currently loaded object. Note that the representation will be removed from the database completely, so if it is also linked to other items it will be removed from them as well.
		 *
		 * @param int $pn_representation_id The representation_id of the representation to remove
		 * @param array $pa_options Options are passed through to BaseMode::delete()
		 * @return bool True if delete succeeded, false if there was an error. You can get the error messages by calling getErrors() on the instance.
		 */
		public function removeRepresentation($pn_representation_id, $pa_options=null) {
			if(!$this->getPrimaryKey()) { return null; }
		
			$vb_update_is_primary = false;
			
			$va_path = array_keys(Datamodel::getPath($this->tableName(), 'ca_object_representations'));
			if (is_array($va_path) && sizeof($va_path) == 3) {
				$vs_rel_table = $va_path[1];
				if ($t_rel = Datamodel::getInstanceByTableName($vs_rel_table)) {
					if ($this->inTransaction()) { $t_rel->setTransaction($this->getTransaction()); }
					if ($t_rel->load(array($this->primaryKey() => $this->getPrimaryKey(), 'representation_id' => $pn_representation_id))) {
						if ($t_rel->hasField('is_primary') && $t_rel->get('is_primary')) {
							$vb_update_is_primary = true;
						}
						$t_rel->setMode(ACCESS_WRITE);
						$t_rel->delete();
						if ($t_rel->numErrors()) {
							$this->errors = array_merge($this->errors, $t_rel->errors());
							return false;
						}
					
						if (($vb_update_is_primary) && (is_array($va_rels = call_user_func("{$vs_rel_table}::find", [$this->primaryKey() => $this->getPrimaryKey()], ['returnAs' => 'arrays']))) && sizeof($va_rels)) {
							if(!sizeof($va_primary_rels = array_filter($va_rels, function($v) { return (bool)$v['is_primary']; }))) {	// no primary rels
								$t_rel->load($va_rels[0]['relation_id']);
								$t_rel->setMode(ACCESS_WRITE);
								$t_rel->set('is_primary', 1);
								$t_rel->update();
								if ($t_rel->numErrors()) {
									$this->errors = array_merge($this->errors, $t_rel->errors());
									return false;
								}
							}
						}	
					}
				}
			}
			$t_rep = new ca_object_representations();
			if ($this->inTransaction()) { $t_rep->setTransaction($this->getTransaction()); }
			if (!$t_rep->load($pn_representation_id)) {
				$this->postError(750, _t("Representation id=%1 does not exist", $pn_representation_id), "RepresentableBaseModel->removeRepresentation()");
				return false;
			} else {
				//
				// Only delete the related representation if nothing else is related to it
				//

				$va_rels = $t_rep->hasRelationships();

				if(is_array($va_rels) && isset($va_rels['ca_object_representation_labels'])){ // labels don't count as relationships in this case
					unset($va_rels['ca_object_representation_labels']);
				}
				if(is_array($va_rels) && isset($va_rels['ca_objects_x_object_representations']) && ($va_rels['ca_objects_x_object_representations'] < 1)){
					unset($va_rels['ca_objects_x_object_representations']);
				}

				if (!is_array($va_rels) || (sizeof($va_rels) == 0)) {
					$t_rep->setMode(ACCESS_WRITE);
					$t_rep->delete(true, $pa_options);
				
					if ($t_rep->numErrors()) {
						$this->errors = array_merge($this->errors, $t_rep->errors());
						return false;
					}
				}
						
				// remove any replicated media
				if(is_array($va_replication_targets = $t_rep->getUsedMediaReplicationTargets('media'))) {
					foreach($va_replication_targets as $vs_target => $va_target_info) {
						$t_rep->removeMediaReplication('media', $vs_target, $t_rep->getMediaReplicationKey('media', $vs_target));
					}
				}
			
				return true;
			}
		
			return false;
		}
		# ------------------------------------------------------
		/**
		 * Removes all representations from the currently loaded item.
		 *
		 * @return bool True if delete succeeded, false if there was an error. You can get the error messages by calling getErrors() on the instance.
		 */
		public function removeAllRepresentations($pa_options=null) {
			if (is_array($va_reps = $this->getRepresentations())) {
				foreach($va_reps as $vn_i => $va_rep_info) {
					if (!$this->removeRepresentation($va_rep_info['representation_id'], $pa_options)) {
						// Representation remove failed
						return false;
					}
				}
			}
			return false;
		}
		# ------------------------------------------------------
		/** 
		 * Link existing media represention to currently loaded item
		 *
		 * @param $pn_representation_id - 
		 * @param $pb_is_primary - if set to true, representation is designated "primary." Primary representation are used in cases where only one representation is required (such as search results). If a primary representation is already attached to this item, then it will be changed to non-primary as only one representation can be primary at any given time. If no primary representations exist, then the new representation will always be marked primary no matter what the setting of this parameter (there must always be a primary representation, if representations are defined).
		 * @param $pa_options - an array of options passed through to BaseModel::set() when creating the new representation. Currently supported options:
		 *		rank - a numeric rank used to order the representations when listed
		 *
		 * @return mixed Returns primary key (link_id) of the relatipnship row linking the  representation to the item; boolean false is returned on error
		 */
		public function linkRepresentation($pn_representation_id, $pb_is_primary, $pa_options=null) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			if (!$pn_locale_id) { $pn_locale_id = ca_locales::getDefaultCataloguingLocaleID(); }
		
			if (!ca_object_representations::find(array('representation_id' => $pn_representation_id), array('transaction' => $this->getTransaction()))) { return null; }
			if (!($t_oxor = $this->_getRepresentationRelationshipTableInstance())) { return null; }
			$vs_pk = $this->primaryKey();
			
			if ($this->inTransaction()) {
				$t_oxor->setTransaction($this->getTransaction());
			}
			$t_oxor->setMode(ACCESS_WRITE);
			$t_oxor->set($vs_pk, $vn_id);
			$t_oxor->set('representation_id', $pn_representation_id);
			$t_oxor->set('is_primary', $pb_is_primary ? 1 : 0);
			$t_oxor->set('rank', isset($pa_options['rank']) ? (int)$pa_options['rank'] : $pn_representation_id);
			if ($t_oxor->hasField('type_id') && ($pm_type_id = caGetOption('type_id', $pa_options, null))) {
			    $pn_type_id = null;
			    if ($pm_type_id && !is_numeric($pm_type_id)) {
                    $t_rel_type = new ca_relationship_types();
                    if ($vs_linking_table = $t_rel_type->getRelationshipTypeTable($this->tableName(), $t_oxor->tableName())) {
                        $pn_type_id = $t_rel_type->getRelationshipTypeID($vs_linking_table, $pm_type_id);
                    } else {
                        $this->postError(2510, _t('Type id "%1" is not valid', $pm_type_id), 'RepresentableBaseModel->linkRepresentation()');
                        return false;
                    }
                } else {
                    $pn_type_id = $pm_type_id;
                }
			    $t_oxor->set('type_id', $pn_type_id); 
			}
			$t_oxor->insert();
		
		
			if ($t_oxor->numErrors()) {
				$this->errors = array_merge($this->errors, $t_oxor->errors());
				return false;
			}
			return $t_oxor->getPrimaryKey();
		}
		# ------------------------------------------------------
		/**
		 * Returns number of representations attached to the current item of the specified class. 
		 *
		 * @param string $ps_class The class of representation to return a count for. Valid classes are "image", "audio", "video" and "document"
		 * @param array $pa_options Options for selection of representations to count; same as options for self::getRepresentations()
		 *
		 * @return int Number of representations
		 */
		public function numberOfRepresentationsOfClass($ps_class, $pa_options=null) {
			return sizeof($this->representationsOfClass($ps_class, $pa_options));
		}
		# ------------------------------------------------------
		/**
		 * Returns number of representations attached to the current item with the specified mimetype. 
		 *
		 * @param string $ps_mimetype The mimetype to return a count for. 
		 * @param array $pa_options Options for selection of representations to count; same as options for self::getRepresentations()
		 *
		 * @return int Number of representations
		 */
		public function numberOfRepresentationsWithMimeType($ps_mimetype, $pa_options=null) {
			return sizeof($this->representationsWithMimeType($ps_mimetype, $pa_options));
		}
		# ------------------------------------------------------
		/**
		 * Returns information for representations of the specified class attached to the current item. 
		 *
		 * @param string $ps_class The class of representation to return information for. Valid classes are "image", "audio", "video" and "document"
		 * @param array $pa_options Options for selection of representations to return; same as options for self::getRepresentations()
		 *
		 * @return array An array with information about matching representations, in the same format as that returned by self::getRepresentations()
		 */
		public function representationsOfClass($ps_class, $pa_options=null) {
			if (!($vs_mimetypes_regex = caGetMimetypesForClass($ps_class, array('returnAsRegex' => true)))) { return array(); }
		
			$va_rep_list = array();
			if (is_array($va_reps = $this->getRepresentations($pa_options))) {
				foreach($va_reps as $vn_rep_id => $va_rep) {
					if (preg_match("!{$vs_mimetypes_regex}!", $va_rep['mimetype'])) {	
						$va_rep_list[$vn_rep_id] = $va_rep;
					}
				}
			}
			return $va_rep_list;
		}
		# ------------------------------------------------------
		/**
		 * Returns information for representations attached to the current item with the specified mimetype. 
		 *
		 * @param array $pa_mimetypes List of mimetypes to return representations for. 
		 * @param array $pa_options Options for selection of representations to return; same as options for self::getRepresentations()
		 *
		 * @return array An array with information about matching representations, in the same format as that returned by self::getRepresentations()
		 */
		public function representationsWithMimeType($pa_mimetypes, $pa_options=null) {
			if (!$pa_mimetypes) { return array(); }
			if (!is_array($pa_mimetypes) && $pa_mimetypes) { $pa_mimetypes = array($pa_mimetypes); }
			$va_rep_list = array();
			if (is_array($va_reps = $this->getRepresentations(caGetOption('versions', $pa_options, null), null, $pa_options))) {
				foreach($va_reps as $vn_rep_id => $va_rep) {
					if (in_array($va_rep['mimetype'], $pa_mimetypes)) {	
						$va_rep_list[$vn_rep_id] = $va_rep;
					}
				}
			}
			return $va_rep_list;
		}
		# ------------------------------------------------------
		/**
		 * Returns information for representation attached to the current item with the specified MD5 hash. 
		 *
		 * @param string $ps_md5 The MD5 hash to return representation info for. 
		 * @param array $pa_options Options for selection of representations to return; same as options for self::getRepresentations()
		 *
		 * @return array An array with information about the matching representation, in the same format as that returned by self::getRepresentations(), or null if there is no match
		 */
		public function representationWithMD5($ps_md5, $pa_options=null) {
			$va_rep_list = array();
			if (is_array($va_reps = $this->getRepresentations($pa_options))) {
				foreach($va_reps as $vn_rep_id => $va_rep) {
					if ($ps_mimetype == $va_rep['md5']) {	
						return $va_rep;
					}
				}
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the preferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param array $pa_ids indexed array of primary key values to fetch labels for
		 * @param array $pa_versions
		 * @param array $pa_options
		 * @return array List of media
		 */
		public function getPrimaryMediaForIDs($pa_ids, $pa_versions, $pa_options = null) {
			if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_access_values = $pa_options["checkAccess"];
			if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values)) {
				$vs_access_where = ' AND orep.access IN ('.join(',', $va_access_values).')';
			}
			$o_db = $this->getDb();
			
			
			if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName()))) { return null; }
			$vs_pk = $this->primaryKey();
		
			$qr_res = $o_db->query("
				SELECT orep.representation_id, oxor.{$vs_pk}, orep.media
				FROM ca_object_representations orep
				INNER JOIN {$vs_linking_table} AS oxor ON oxor.representation_id = orep.representation_id
				WHERE
					(oxor.{$vs_pk} IN (".join(',', $pa_ids).")) AND oxor.is_primary = 1 AND orep.deleted = 0 {$vs_access_where}
			");
		
			$va_media = array();
			while($qr_res->nextRow()) {
				$va_media_tags = array();
				foreach($pa_versions as $vs_version) {
					$va_media_tags['representation_id'] = $qr_res->get('ca_object_representations.representation_id');
					$va_media_tags['tags'][$vs_version] = $qr_res->getMediaTag('ca_object_representations.media', $vs_version);
					$va_media_tags['info'][$vs_version] = $qr_res->getMediaInfo('ca_object_representations.media', $vs_version);
					$va_media_tags['urls'][$vs_version] = $qr_res->getMediaUrl('ca_object_representations.media', $vs_version);
				}
				$va_media[$qr_res->get($vs_pk)] = $va_media_tags;
			}
		
			// Preserve order of input ids
			$va_media_sorted = array();
			foreach($pa_ids as $vn_id) {
				$va_media_sorted[$vn_id] = $va_media[$vn_id];
			} 
		
			return $va_media_sorted;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns number of representations attached to each item referenced by primary key in $pa_ids
		 * 
		 * @param array $pa_ids indexed array of primary key values to fetch labels for
		 * @param array $pa_options
		 * @return array List of representation counts indexed by primary key
		 */
		public function getMediaCountsForIDs($pa_ids, $pa_options = null) {
			if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }
			if (!is_array($pa_options)) { $pa_options = array(); }
			$va_access_values = $pa_options["checkAccess"];
			if (isset($va_access_values) && is_array($va_access_values) && sizeof($va_access_values)) {
				$vs_access_where = ' AND orep.access IN ('.join(',', $va_access_values).')';
			}
			$o_db = $this->getDb();
			
			if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName()))) { return null; }
			$vs_pk = $this->primaryKey();
		
			$qr_res = $o_db->query("
				SELECT oxor.{$vs_pk}, count(*) c
				FROM ca_object_representations orep
				INNER JOIN {$vs_linking_table} AS oxor ON oxor.representation_id = orep.representation_id
				WHERE
					(oxor.{$vs_pk} IN (".join(',', $pa_ids).")) AND orep.deleted = 0 {$vs_access_where}
				GROUP BY oxor.{$vs_pk}
			");
		
			$va_counts = array();
			while($qr_res->nextRow()) {
				$va_counts[$qr_res->get($vs_pk)] = (int)$qr_res->get('c');
			}
			return $va_counts;
		}		
		# ------------------------------------------------------
		/** 
		 * Returns HTML form bundle for batch editor-only representation access and status bundle
		 *
		 * @param HTTPRequest $po_request The current request
		 * @param string $ps_form_name
		 * @param string $ps_placement_code
		 * @param array $pa_bundle_settings
		 * @param array $pa_options Array of options. Supported options are 
		 *			noCache = If set to true then label cache is bypassed; default is true
		 *
		 * @return string Rendered HTML bundle
		 */
		public function getObjectRepresentationAccessStatusHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
		
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');
		
			if(!is_array($pa_options)) { $pa_options = array(); }
		
			$o_view->setVar('id_prefix', $ps_form_name);
			$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
			$o_view->setVar('settings', $pa_bundle_settings);
		
			$o_view->setVar('t_subject', $this);
		
		
			return $o_view->render('ca_object_representations_access_status.php');
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		static public function getRepresentationRelationshipTableName($ps_table_name) {
			$va_path = Datamodel::getPath($ps_table_name, 'ca_object_representations');
			if (!is_array($va_path) || (sizeof($va_path) != 3)) { return null; }
			$va_path = array_keys($va_path);
			return $va_path[1];
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		private function _getRepresentationRelationshipTableInstance() {
			$va_path = Datamodel::getPath($this->tableName(), 'ca_object_representations');
			if (!is_array($va_path) || (sizeof($va_path) != 3)) { return null; }
			$va_path = array_keys($va_path);
			return Datamodel::getInstanceByTableName($va_path[1], true);
		}
		# ------------------------------------------------------
        /**
         *
         */
        public function getBundleFormValues($ps_bundle_name, $ps_placement_code, $pa_bundle_settings, $pa_options=null) {		
            foreach(array('restrict_to_types', 'restrict_to_relationship_types') as $vs_k) {
                $pa_options[$vs_k] = $pa_bundle_settings[$vs_k];
            }
            $va_reps = $this->getRepresentations(array('thumbnail', 'original'), null, $pa_options);
        
            $t_item = new ca_object_representations();
            $va_rep_type_list = $t_item->getTypeList();
            $va_errors = array();
            
            $vs_bundle_template = caGetOption('display_template', $pa_bundle_settings, null);

            // Paging
            $vn_primary_id = 0;
            $va_initial_values = array();
            if (is_array($va_reps) && sizeof($va_reps)) {
                $o_type_config = Configuration::load($t_item->getAppConfig()->get('annotation_type_config'));
                $va_annotation_type_mappings = $o_type_config->getAssoc('mappings');

                // Get display template values
                $va_display_template_values = array();
                if($vs_bundle_template && is_array($va_relation_ids = caExtractValuesFromArrayList($va_reps, 'relation_id')) && sizeof($va_relation_ids)) {
                    if ($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($this->tableName())) {
                        $va_display_template_values = caProcessTemplateForIDs($vs_bundle_template, $vs_linking_table, $va_relation_ids, array_merge($pa_options, array('returnAsArray' => true, 'returnAllLocales' => false, 'includeBlankValuesInArray' => true)));
                    }
                }

                $vn_i = 0;
                foreach ($va_reps as $va_rep) {
                    $vn_num_multifiles = $va_rep['num_multifiles'];
                    if ($vs_extracted_metadata = caFormatMediaMetadata(caSanitizeArray(caUnserializeForDatabase($va_rep['media_metadata']), array('removeNonCharacterData' => true)))) {
                        $vs_extracted_metadata = "<h3>"._t('Extracted metadata').":</h3>\n{$vs_extracted_metadata}\n";
                    }
                    $vs_md5 = isset($va_rep['info']['original']['MD5']) ? "<h3>"._t('MD5 signature').':</h3>'.$va_rep['info']['original']['MD5'] : '';

                    if ($va_rep['is_primary']) {
                        $vn_primary_id = $va_rep['representation_id'];
                    }
                
                    $va_initial_values[$va_rep['representation_id']] = array(
                        'idno' => $va_rep['idno'], 
                        '_display' => ($vs_bundle_template && isset($va_display_template_values[$vn_i])) ? $va_display_template_values[$vn_i] : '',
                        'status' => $va_rep['status'], 
                        'status_display' => $t_item->getChoiceListValue('status', $va_rep['status']), 
                        'access' => $va_rep['access'],
                        'access_display' => $t_item->getChoiceListValue('access', $va_rep['access']), 
                        'rep_type_id' => $va_rep['type_id'],
                        'rep_type' => $t_item->getTypeName($va_rep['type_id']), 
                        'rep_label' => $va_rep['label'],
                        'is_primary' => (int)$va_rep['is_primary'],
                        'is_primary_display' => ($va_rep['is_primary'] == 1) ? _t('PRIMARY') : '', 
                        'locale_id' => $va_rep['locale_id'], 
                        'icon' => $va_rep['tags']['thumbnail'], 
                        'mimetype' => $va_rep['info']['original']['PROPERTIES']['mimetype'], 
                        'annotation_type' => isset($va_annotation_type_mappings[$va_rep['info']['original']['PROPERTIES']['mimetype']]) ? $va_annotation_type_mappings[$va_rep['info']['original']['PROPERTIES']['mimetype']] : null,
                        'type' => $va_rep['info']['original']['PROPERTIES']['typename'], 
                        'dimensions' => $va_rep['dimensions']['original'], 
                        'filename' => $va_rep['info']['original_filename'] ? $va_rep['info']['original_filename'] : _t('Unknown'),
                        'num_multifiles' => ($vn_num_multifiles ? (($vn_num_multifiles == 1) ? _t('+ 1 additional preview') : _t('+ %1 additional previews', $vn_num_multifiles)) : ''),
                        'metadata' => $vs_extracted_metadata,
                        'md5' => $vs_md5 ? "{$vs_md5}" : "",
                        'typename' => $va_rep_type_list[$va_rep['type_id']]['name_singular'],
                        'fetched_from' => $va_rep['fetched_from'],
                        'fetched_on' => date('c', $va_rep['fetched_on']),
                        'fetched' => $va_rep['fetched_from'] ? _t("<h3>Fetched from:</h3> URL %1 on %2", '<a href="'.$va_rep['fetched_from'].'" target="_ext" title="'.$va_rep['fetched_from'].'">'.$va_rep['fetched_from'].'</a>', date('c', $va_rep['fetched_on'])) : ""
                    );

                    $vn_i++;
                }
            }
            return $va_initial_values;
		}
		# ------------------------------------------------------
	}
