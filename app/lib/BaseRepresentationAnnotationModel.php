<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRepresentationAnnotationModel.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/IBundleProvider.php");
require_once(__CA_LIB_DIR__."/BundlableLabelableBaseModelWithAttributes.php");

class BaseRepresentationAnnotationModel extends BundlableLabelableBaseModelWithAttributes implements IBundleProvider {
	# ------------------------------------------------------
	public function __construct($id=null, ?array $options=null) {
		parent::__construct($id, $options);
		
 		$o_config = $this->getAppConfig();
 		$this->opo_type_config = Configuration::load(__CA_CONF_DIR__.'/annotation_types.conf');
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions($pa_options=null) {
		parent::initLabelDefinitions($pa_options);
		$this->BUNDLES['ca_objects'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects'));
		$this->BUNDLES['ca_objects_table'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_objects_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related objects list'));
		$this->BUNDLES['ca_object_representations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object representations list'));
		$this->BUNDLES['ca_entities_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities list'));
		$this->BUNDLES['ca_places_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places list'));
		$this->BUNDLES['ca_occurrences_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences list'));
		$this->BUNDLES['ca_collections_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related collections list'));
		$this->BUNDLES['ca_list_items_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related list items list'));
		$this->BUNDLES['ca_storage_locations_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related storage locations list'));
		$this->BUNDLES['ca_loans_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related loans list'));
		$this->BUNDLES['ca_movements_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related movements list'));
		$this->BUNDLES['ca_object_lots_related_list'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related object lots list'));
		$this->BUNDLES['ca_entities'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related entities'));
		$this->BUNDLES['ca_places'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related places'));
		$this->BUNDLES['ca_occurrences'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related occurrences'));
		$this->BUNDLES['ca_representation_annotation_properties'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Annotation properties'));
		
		$this->BUNDLES['ca_list_items'] = array('type' => 'related_table', 'repeating' => true, 'label' => _t('Related vocabulary terms'));

		$this->BUNDLES['startTimecode'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Start timecode'));
		$this->BUNDLES['endTimecode'] = array('type' => 'special', 'repeating' => false, 'label' => _t('End timecode'));		

	}
	# ------------------------------------------------------
	/**
	 * Override set() to do idno_stub lookups on lots
	 *
	 * @param mixed $pm_fields
	 * @param mixed $pm_value
	 * @param array $pa_options Most options are handled by subclasses. Options defined here include:
	 *		assumeIdnoForRepresentationID = set to force lookup of representation_id values as ca_representations.idno values first not matter what, before consideration as a numeric representation_id. The default is false, in which case integer values are considered representation_ids and non-numeric values possible idnos.
	 *		tryObjectIdnoForRepresentationID = try to set representation_id using the primary representation of the object with idno as specified for representaton_id
	 *		
	 * @return int 
	 */
	public function set($pm_fields, $pm_value="", $pa_options=null) {
		if (!is_array($pm_fields)) {
			$pm_fields = array($pm_fields => $pm_value);
		}
		$pb_assume_idno_for_representation_id = caGetOption('assumeIdnoForRepresentationID', $pa_options, false);
		$pb_try_object_idno = caGetOption('tryObjectIdnoForRepresentationID', $pa_options, false);
		foreach($pm_fields as $vs_fld => $vs_val) {
			if (($vs_fld == 'representation_id') && ($pb_assume_idno_for_representation_id || preg_match("![^\d]+!", $vs_val))) {
				$t_rep = new ca_object_representations();
				if ($t_rep->load(array('idno' => $vs_val, 'deleted' => 0))) {
					$vn_representation_id = (int)$t_rep->getPrimaryKey();
					$pm_fields[$vs_fld] = $vn_representation_id;
					if ($vn_rc = parent::set($pm_fields, null, $pa_options)) {
						$this->set('type_code', $vs_type = $this->getAnnotationType());
					}
					return $vn_rc;
				}
			}
			
			if (($vs_fld == 'representation_id') && ($pb_try_object_idno) && ($t_object = ca_objects::find(['idno' => "{$vs_val}"], ['allowWildcards' => true, 'returnAs' => 'firstModelInstance']))) {
				if ($vn_representation_id = (int)$t_object->getPrimaryRepresentationID()) {
					$pm_fields[$vs_fld] = $vn_representation_id;
					if ($vn_rc = parent::set($pm_fields, null, $pa_options)) {
						$this->set('type_code', $vs_type = $this->getAnnotationType());
					}
					return $vn_rc;
				}
			}
		}
		return parent::set($pm_fields, null, $pa_options);
	}
 	# ------------------------------------------------------
	public function load($pm_id=null, $pb_use_cache=true) {
		$vn_rc = parent::load($pm_id, $pb_use_cache);
		
		$vs_type = $this->getAnnotationType();
		$this->opo_annotations_properties = $this->loadProperties($vs_type);
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		$this->set('type_code', $vs_type = $this->getAnnotationType());
		$this->opo_annotations_properties = $this->loadProperties($vs_type);
		
		if (!$this->opo_annotations_properties) {
			$this->postError(1101, _t('No type code set'), $this->tableName().'->insert');
			return false;
		}
		if (!$this->opo_annotations_properties->validate()) {
			$this->errors = $this->opo_annotations_properties->errors;
			return false;
		}
		$this->set('props', $this->opo_annotations_properties->getPropertyValues());
		$vn_rc = parent::insert($pa_options);
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Update annotation. If time code of annotation has changed media preview will be regenerated. 
	 * You can force the media preview to be regenerated whether the time code has changed or not
	 * by passing the 'forcePreviewGeneration' option.
	 *
	 * @param array $pa_options An array of options:
	 *		forcePreviewGeneration = if set preview media will be regenerated whether time code has changed or not. Default is false.
	 * @return bool True on success, false on failure
	 */
	public function update($pa_options=null) {
		$this->set('type_code', $vs_type = $this->getAnnotationType());
		if (!$this->opo_annotations_properties) {
			$this->postError(1101, _t('No type code set'), $this->tableName().'->update');
			return false;
		}
		if (!$this->opo_annotations_properties->validate()) {
			$this->errors = $this->opo_annotations_properties->errors;
			return false;
		}
		$this->set('props', $this->opo_annotations_properties->getPropertyValues());
		
		if (!$this->getAppConfig()->get('dont_generate_annotation_previews') && $this->getPrimaryKey() && ($this->changed('props') || (isset($pa_options['forcePreviewGeneration']) && $pa_options['forcePreviewGeneration']))) {
			$vs_start = $this->getPropertyValue('startTimecode');
			$vs_end = $this->getPropertyValue('endTimecode');
			
			$va_data['start'] = $vs_start;
			$va_data['end'] = $vs_end;
			
			$t_rep = new ca_object_representations($this->get('representation_id'));
			if (($vs_file = $t_rep->getMediaPath('media', 'original')) && file_exists($vs_file)) {
				$o_media = new Media();
				if ($o_media->read($vs_file)) {
					if ($o_media->writeClip($vs_file = tempnam(caGetTempDirPath(), 'annotationPreview'), $vs_start, $vs_end)) {
						$this->set('preview', $vs_file);
					}
				}
			}
		}	
		
		$vn_rc = parent::update($pa_options);
		if (!$this->numErrors()) {
			$this->opo_annotations_properties = $this->loadProperties($vs_type);
		}
		if ($vs_file) { @unlink($vs_file); }
		return $vn_rc;
	}
	# ------------------------------------------------------
	public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
		
		if (!$this->numErrors()) {
			$this->opo_annotations_properties = null;
		}
		
		return $vn_rc;
	}
	# ------------------------------------------------------
	/**
	 * Override BundlableLabelableBaseModelWithAttributes::get() to allow getting 
	 * annotation properties in simple get()-style notations like
	 *   ca_representations_annotations.props.w
	 */
	public function get($ps_field, $pa_options=null) {
		$va_tmp = explode('.', $ps_field);

		// remove table name if needed
		if(isset($va_tmp[0]) && ($va_tmp[0] == $this->tableName())) {
			array_shift($va_tmp);
		}

		if((sizeof($va_tmp)==2) && isset($va_tmp[0]) && ($va_tmp[0] == 'props')) {

			if($va_tmp[1] == 'display') {
				return $this->getPropertiesForDisplay($pa_options);
			}

			$vm_val = $this->getPropertyValue($va_tmp[1]);
			
			// this should be moved into the property implementation but for now, points is the only occurrence 
			// of this kind of thing and getProperty() doesn't support any options so this is a reasonable quick&dirty way. 
			if(is_array($vm_val) && !caGetOption('returnAsArray',$pa_options)) {
				switch($va_tmp[1]) {
					case 'points' :
						$va_return = array();
						foreach($vm_val as $va_point) {
							// round values for display
							$va_return[] = round($va_point['x'],2).','.round($va_point['y'],2);
						}
						if(!($vs_delimiter = caGetOption('delimiter',$pa_options))){
							$vs_delimiter = '; ';
						}
						return join($vs_delimiter, $va_return);
						break;
					default:
						return $vm_val;
				}
			}
			return $vm_val;
		}

		return parent::get($ps_field, $pa_options);
	}
	# ------------------------------------------------------
	/**
	 * Returns true if currently set property values are valid and can be inserted into database, 
	 * false if they violate some annotation-type-specific constraint
	 */
	public function validatePropertyValues() {
		if (!($vn_rc = $this->opo_annotations_properties->validate())) {
			$this->errors = $this->opo_annotations_properties->errors();
		}
		return $vn_rc;
	}
	# ------------------------------------------------------
 	public function getPropertyList() {
 		return is_object($this->opo_annotations_properties) ? $this->opo_annotations_properties->getPropertyList() : array();
 	}
 	# ------------------------------------------------------
 	public function getPropertyHTMLFormElement($ps_property, $pa_attributes=null, $pa_options=null) {
 		return $this->opo_annotations_properties->htmlFormElement($ps_property, $pa_attributes);
 	}
 	# ------------------------------------------------------
 	public function getPropertyHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $ps_property, $pa_attributes=null, $pa_options=null) {
 		$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
		$o_view = new View($po_request, "{$vs_view_path}/bundles/");
		
		$o_view->setVar('property', $ps_property);
		$o_view->setVar('id_prefix', $ps_form_name);
		$o_view->setVar('placement_code', $ps_placement_code);
		
		$pa_attributes['name'] = "{$ps_placement_code}{$ps_form_name}{$ps_property}";
		$pa_attributes['id'] = $ps_property;
 		$o_view->setVar('form_element', $this->getPropertyHTMLFormElement($ps_property, $pa_attributes));
 		
		return $o_view->render('ca_representation_annotation_properties.php');
 	}
 	# ------------------------------------------------------
 	public function getPropertyValue($ps_property, $options=null) {
 		return $this->opo_annotations_properties->getProperty($ps_property, false, $options);
 	}
 	# ------------------------------------------------------
 	public function getPropertyValueForID(int $annotation_id, string $property) {
 		$qr = $this->getDb()->query("SELECT * FROM ".$this->tableName()." WHERE annotation_id = ?", [$annotation_id]);
 		
 		if($qr->nextRow()) {
 			$properties = caUnserializeForDatabase($qr->get('props'));
 			return $properties[$property] ?? null;
 		}
 		return null;
 	}
 	# ------------------------------------------------------
 	public function getPropertyValues() {
 		return $this->opo_annotations_properties->getPropertyValues();
 	}
 	# ------------------------------------------------------
 	public function getPropertiesForDisplay($pa_options=null) {
 		if($this->opo_annotations_properties instanceof IRepresentationAnnotationPropertyCoder) {
 			return $this->opo_annotations_properties->getPropertiesForDisplay($pa_options);	
 		} else {
 			return '';
 		}
 	}
 	# ------------------------------------------------------
 	public function setPropertyValue($ps_property, $pm_value) {
 		return $this->opo_annotations_properties->setProperty($ps_property, $pm_value);
 	}
 	# ------------------------------------------------------
 	public function getAnnotationType($pn_representation_id=null) {
 		if (!$pn_representation_id) {
			if (!$vn_representation_id = $this->get('representation_id')) {
				return false;
			}
		} else {
			$vn_representation_id = $pn_representation_id;
		}
 		$t_rep = new ca_object_representations();
 		
 		return $t_rep->getAnnotationType($vn_representation_id);
 	}
 	# ------------------------------------------------------
 	public function getPropertiesForType($ps_type) {
 		$va_types = $this->opo_type_config->getAssoc('types');
 		return array_keys($va_types[$ps_type]['properties']);
 	}
 	# ------------------------------------------------------
 	public function loadProperties($ps_type, $pa_parameters=null) {
 		$vs_classname = $ps_type.'RepresentationAnnotationCoder';
 		if (!file_exists(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php')) {
 			return false;
 		}
 		include_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php');
 		
 		$this->opo_annotations_properties = new $vs_classname;
 		$this->opo_annotations_properties->setPropertyValues(is_array($pa_parameters) ? $pa_parameters : $this->get('props'));
 		return $this->opo_annotations_properties;
 	}
 	# ------------------------------------------------------
 	/**
 	 * Matching method to ca_objects::getRepresentations(), except this one only returns a single representation - the currently loaded one
 	 */
 	public function getRepresentations($pa_versions=null, $pa_version_sizes=null, $pa_options=null) {
 		if (!($vn_object_id = $this->getPrimaryKey())) { return null; }
 		if (!is_array($pa_options)) { $pa_options = array(); }
 		
 		if (!is_array($pa_versions)) { 
 			$pa_versions = array('preview170');
 		}
 		
 		$o_db = $this->getDb();
 		
 		$params = [(int)$this->get('representation_id')];
 		
 		$vs_access_sql = '';
		if (isset($pa_options['checkAccess']) && is_array($pa_options['checkAccess']) && sizeof($pa_options['checkAccess'])) {
			$vs_access_sql = ' AND coar.access IN (?)';
			$params[] = $pa_options['checkAccess'];
		}
 		
 		$qr_reps = $o_db->query("
 			SELECT caor.representation_id, caor.media, caor.access, caor.status, l.name, caor.locale_id, caor.media_metadata, caor.type_id
 			FROM ca_object_representations caor
 			LEFT JOIN ca_locales AS l ON caor.locale_id = l.locale_id
 			WHERE
 				caor.representation_id = ? 
 				{$vs_access_sql}
 			ORDER BY
 				l.name ASC 
 		", $params);
 		
 		$va_reps = array();
 		while($qr_reps->nextRow()) {
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
 				
 				$va_dimensions = array();
 				if (isset($va_tmp['info'][$vs_version]['WIDTH']) && isset($va_tmp['info'][$vs_version]['HEIGHT'])) {
					if (($vn_w = $va_tmp['info'][$vs_version]['WIDTH']) && ($vn_h = $va_tmp['info'][$vs_version]['WIDTH'])) {
						$va_dimensions[] = $va_tmp['info'][$vs_version]['WIDTH'].'p x '.$va_tmp['info'][$vs_version]['HEIGHT'].'p';
					}
				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth']) && ($vn_depth = $va_tmp['info'][$vs_version]['PROPERTIES']['bitdepth'])) {
 					$va_dimensions[] = intval($vn_depth).' bpp';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['colorspace']) && ($vs_colorspace = $va_tmp['info'][$vs_version]['PROPERTIES']['colorspace'])) {
 					$va_dimensions[] = $vs_colorspace;
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['resolution']) && is_array($va_resolution = $va_tmp['info'][$vs_version]['PROPERTIES']['resolution'])) {
 					if (isset($va_resolution['x']) && isset($va_resolution['y']) && $va_resolution['x'] && $va_resolution['y']) {
 						// TODO: units for resolution? right now assume pixels per inch
 						if ($va_resolution['x'] == $va_resolution['y']) {
 							$va_dimensions[] = $va_resolution['x'].'ppi';
 						} else {
 							$va_dimensions[] = $va_resolution['x'].'x'.$va_resolution['y'].'ppi';
 						}
 					}
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['duration']) && ($vn_duration = $va_tmp['info'][$vs_version]['PROPERTIES']['duration'])) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_duration).'s';
 				}
 				if (isset($va_tmp['info'][$vs_version]['PROPERTIES']['pages']) && ($vn_pages = $va_tmp['info'][$vs_version]['PROPERTIES']['pages'])) {
 					$va_dimensions[] = $vn_pages.' '.(($vn_pages == 1) ? _t('page') : _t('pages'));
 				}
 				if (!isset($va_tmp['info'][$vs_version]['PROPERTIES']['filesize']) || !($vn_filesize = $va_tmp['info'][$vs_version]['PROPERTIES']['filesize'])) {
 					$vn_filesize = @filesize($qr_reps->getMediaPath('media', $vs_version));
 				}
 				if ($vn_filesize) {
 					$va_dimensions[] = sprintf("%4.1f", $vn_filesize/(1024*1024)).'mb';
 				}
 				$va_tmp['dimensions'][$vs_version] = join('; ', $va_dimensions);
 			}
 			
 				
			if (isset($va_info['INPUT']['FETCHED_FROM']) && ($vs_fetched_from_url = $va_info['INPUT']['FETCHED_FROM'])) {
				$va_tmp['fetched_from'] = $vs_fetched_from_url;
				$va_tmp['fetched_on'] = (int)$va_info['INPUT']['FETCHED_ON'];
				$va_tmp['fetched_by'] = caGetOption('FETCHED_BY', $va_info['INPUT'], null);
				$va_tmp['fetched_original_url'] = caGetOption('FETCHED_ORIGINAL_URL', $va_info['INPUT'], null);
			}
 			
 			//$va_tmp['num_multifiles'] = $t_rep->numFiles($this->get('representation_id'));
 			$va_reps[] = $va_tmp;
 		}
 		return $va_reps;
 	}
 	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function useInEditor() {
		return $this->opo_annotations_properties->useInEditor();
	}
	# ------------------------------------------------------
	public function getTypeList($pa_options=null) {
		$o_annotation_type_conf = Configuration::load(Configuration::load()->get('annotation_type_config'));
		$va_type_list = array();
		foreach($o_annotation_type_conf->get('types') as $vs_type_code => $va_type_info) {
			if(!isset($va_type_info['typeID'])) { continue; }
			$va_type_list[$va_type_info['typeID']] =
				array_merge(array('idno' => $vs_type_code), $va_type_info);
		}
		return $va_type_list;
	}
	# ------------------------------------------------------
	public function getTypeID($pn_id=null) {
		$o_annotation_type_conf = Configuration::load(Configuration::load()->get('annotation_type_config'));
		$va_available_types = $o_annotation_type_conf->get('types');
		if(isset($va_available_types[$this->get('type_code')]['typeID'])) {
			return $va_available_types[$this->get('type_code')]['typeID'];
		}

		return null;
	}
 	# ------------------------------------------------------
 	# STATIC
 	# ------------------------------------------------------
 	static public function getPropertiesCoderInstance($ps_type) {
 		$vs_classname = $ps_type.'RepresentationAnnotationCoder';
 		if (!file_exists(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php')) {
 			return false;
 		}
 		include_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php');
 		
 		return new $vs_classname;
 	}
 	# ------------------------------------------------------
	/**
	 *
	 */
	public function renderBundleForDisplay($bundle_name, $row_id, $values, $options=null) {
		switch($bundle_name) {
			case 'startTimecode':
			case 'endTimecode':
				$seconds = $this->getPropertyValueForID($row_id, $bundle_name);
				$tc = new TimecodeParser($seconds);
				return $tc->getText(caGetOption(['asTimecode', 'format'], $options, null), $options);
				break;
		}
		return null;
	}
 	# ------------------------------------------------------
}
