<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/RepresentationAnnotationSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2015 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/Search/BaseSearchResult.php");
include_once(__CA_LIB_DIR__."/Parsers/TimecodeParser.php");

class RepresentationAnnotationSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_representation_annotations';
	
	/**
	 * Annotation properties instance
	 */
	protected $opo_annotations_properties = null;
	protected $opo_type_config = null;
	
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
 		$o_config = Configuration::load();
 		$this->opo_type_config = Configuration::load(__CA_CONF_DIR__.'/annotation_types.conf');
 		
		parent::__construct();
	}
	# -------------------------------------
	/**
	 *
	 */
	public function nextHit() {
		if ($vn_rc = parent::nextHit()) {
			$this->opo_annotations_properties = $this->loadProperties($this->getAnnotationType());
		}
		return $vn_rc;
	}
	# -------------------------------------
	/**
	 *
	 */
 	public function getPropertyValue($ps_property, $pb_return_raw_value=false) {
 		return $this->opo_annotations_properties->getProperty($ps_property, $pb_return_raw_value);
 	}
	# -------------------------------------
	/**
	 *
	 */
 	public function getPropertyValues() {
 		return $this->opo_annotations_properties->getPropertyValues();
 	}
	# -------------------------------------
	/**
	 *
	 */
 	public function getPropertiesForDisplay($pa_options=null) {
 		if($this->opo_annotations_properties instanceof IRepresentationAnnotationPropertyCoder) {
 			return $this->opo_annotations_properties->getPropertiesForDisplay($pa_options);	
 		} else {
 			return '';
 		}
 	}
	# -------------------------------------
	/**
	 *
	 */
 	public function getAnnotationType($pn_representation_id=null) {
 		if (!$pn_representation_id) {
			if (!$vn_representation_id = $this->get('ca_representation_annotations.representation_id')) {
				return false;
			}
		} else {
			$vn_representation_id = $pn_representation_id;
		}
 		$t_rep = new ca_object_representations();
 		
 		return $t_rep->getAnnotationType($vn_representation_id);
 	}
	# -------------------------------------
	/**
	 *
	 */
 	public function getPropertiesForType($ps_type) {
 		$va_types = $this->opo_type_config->getAssoc('types');
 		return array_keys($va_types[$ps_type]['properties']);
 	}
	# -------------------------------------
	/**
	 *
	 */
 	public function loadProperties($ps_type, $pa_parameters=null) {
 		$vs_classname = $ps_type.'RepresentationAnnotationCoder';
 		if (!file_exists(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php')) {
 			return false;
 		}
 		include_once(__CA_LIB_DIR__.'/RepresentationAnnotationPropertyCoders/'.$vs_classname.'.php');
 		
 		$this->opo_annotations_properties = new $vs_classname;
 		$this->opo_annotations_properties->setPropertyValues(is_array($pa_parameters) ? $pa_parameters : array_shift($this->get('ca_representation_annotations.props', array('unserialize' => true, 'returnWithStructure' => true, 'returnAsArray' => true))));

 		return $this->opo_annotations_properties;
 	}
	# -------------------------------------
	/**
	 *
	 * @see TimecodeParser
	 */
	public function get($ps_field, $pa_options=null) {
		
		$va_tmp = explode(".", $ps_field);
		
		if ((sizeof($va_tmp) == 3) && ($va_tmp[1] == 'props')) {
			// De-serialize annotation properties ("props") for get()
			$vm_val = parent::get($ps_field, array_merge($pa_options, array('unserialize' => true)));
			$vs_annotation_type = $this->getAnnotationType();
		
			if (preg_match("!^TimeBased!", $vs_annotation_type)) {
				$va_props = $this->getPropertiesForType($vs_annotation_type);
				if (in_array($va_tmp[2], $va_props)) {
					if ($vs_timecode_format = caGetOption('asTimecode', $pa_options, false)) { 
						if (!is_array($vm_val)) { $vm_val = array($vm_val); }
						$o_tp = new TimecodeParser();
						foreach($vm_val as $vn_i => $vn_seconds) {
							$o_tp->setParsedValueInSeconds($vn_seconds);
							$vm_val[$vn_i] = $o_tp->getText($vs_timecode_format);
						}
						if (!caGetOption('returnAsArray', $pa_options, false)) { return array_shift($vm_val); }
					}
				}
			}
			return $vm_val;
		} elseif((sizeof($va_tmp) == 2) && in_array($va_tmp[1], array('start', 'end', 'duration')) && ($vs_annotation_type = $this->getAnnotationType(parent::get('ca_representation_annotations.representation_id'))) && preg_match("!^TimeBased!", $vs_annotation_type)) {
			// Support three virtual fields for timebased annotations: "start" and "end" timecode + "duration" for clip
			// All allow an "asTimecode" option to control the format of the returned value. The option is passed to TimecodeParser as the format
			// Default is to return as number of seconds; pass asTimecode=hms or time for h/m/s format (eg. 1h 5m 10s); or asTimecode=delimited for colon delimited (h:m:s)
			
			$o_tp = new TimecodeParser();
			$vs_timecode_format = caGetOption('asTimecode', $pa_options, false);
		
			switch($va_tmp[1]) {
				case 'start':
				case 'end':
					$vm_val = parent::get("ca_representation_annotations.props.{$va_tmp[1]}Timecode", array_merge($pa_options, array('unserialize' => true)));
					if (!is_array($vm_val)) { $vm_val = array($vm_val); }
					foreach($vm_val as $vn_i => $vn_seconds) {
						$o_tp->setParsedValueInSeconds($vn_seconds);
						$vm_val[$vn_i] = $o_tp->getText($vs_timecode_format);
					}
					break;
				case 'duration':
					$vn_duration_in_seconds = (float)$this->getPropertyValue('endTimecode', true) - (float)$this->getPropertyValue('startTimecode', true);
				
					$o_tp->setParsedValueInSeconds($vn_duration_in_seconds);
					$vm_val = array($o_tp->getText($vs_timecode_format));
					
					break;
			}
				
			if (!caGetOption('returnAsArray', $pa_options, false)) { return array_shift($vm_val); }
			return $vm_val;
		} else {
			return parent::get($ps_field, $pa_options);
		}
	}
	# -------------------------------------
}