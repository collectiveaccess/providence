<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/KmlParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

 /** 
 * Parses KML markup language used by Google Earth (and others) to represent geographic data
 *
 * This class currently extracts only a subset of the data that may be contains in a KML file:
 *
 *	- Placemarks
 *  - Paths
 *
 * This module requires the SimpleXML extension.
 *
 */

require_once(__CA_LIB_DIR__."/core/Parsers/UnZipFile.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

class KmlParser {
	# --------------------------------------------------------------------------------
	private $opa_filedata;
	# --------------------------------------------------------------------------------
	public function KmlParser($ps_filepath=null) {
		if ($ps_filepath) { $this->parse($ps_filepath); }
	}
	# --------------------------------------------------------------------------------
	private function init() {
		$this->opa_filedata = array();
	}
	# --------------------------------------------------------------------------------
	public function parse($ps_filepath) {
		if(!function_exists("simplexml_load_file")) { return null; }
	
		$this->init();
		
		// Is file a KMZ file?
		$o_unzip = new UnZipFile($ps_filepath);
		$vs_tmp_dirname = tempnam(caGetTempDirPath(), 'kml');
		@unlink($vs_tmp_dirname);
		@mkdir($vs_tmp_dirname);
		if ($o_unzip->extract($vs_tmp_dirname, 'doc.kml')) {
			if(file_exists($vs_tmp_dirname.'/doc.kml')) {
				$ps_filepath = $vs_tmp_dirname.'/doc.kml';
			} else {
				return false;
			}
		}
		$o_kml = @simplexml_load_file($ps_filepath);
		if (!$o_kml) { return false; }
		caRemoveDirectory($vs_tmp_dirname, true);
		
		//
		// Placemarks
		//
		$va_namespaces = $o_kml->getNamespaces(true);
		
		foreach($va_namespaces as $vs_prefix => $vs_schema_url) {
			$o_kml->registerXPathNamespace ($vs_prefix ? $vs_prefix : 'g', $vs_schema_url);
		}
		$va_placemarks = $o_kml->xpath('//g:Placemark');
		
		$this->opa_filedata['placemarks'] = array();
		foreach($va_placemarks as $va_placemark) {
			$vs_name = ''.$va_placemark->name[0];
			$vs_description = ''.$va_placemark->description[0];
			
			if (isset($va_placemark->Point)) {
				$vs_coord = $va_placemark->Point->coordinates;
				$va_tmp = explode(',',$vs_coord);
				$this->opa_filedata['placemarks'][] = array(
					'name' 			=> $vs_name,
					'type'			=> 'POINT',
					'description' 	=> $vs_description,
					'latitude' 		=> $va_tmp[1],
					'longitude' 	=> $va_tmp[0]
				);
			} else {
				if (isset($va_placemark->LineString) && isset($va_placemark->LineString->coordinates)) {
					$vs_coords = trim($va_placemark->LineString->coordinates);
					$va_coord_lines = preg_split("/[ \n\r]+/", $vs_coords);
				} else {
					if(isset($va_placemark->Polygon) && isset($va_placemark->Polygon->outerBoundaryIs) && isset($va_placemark->Polygon->outerBoundaryIs->LinearRing) && isset($va_placemark->Polygon->outerBoundaryIs->LinearRing->coordinates)) {
						$vs_coords = trim($va_placemark->Polygon->outerBoundaryIs->LinearRing->coordinates);
						$va_coord_lines = preg_split("/[ \n\r]+/", $vs_coords);
					}
				}
				if (sizeof($va_coord_lines) > 0) {
					$va_coord_list = array();
					foreach($va_coord_lines as $vs_coord_line) {
						$va_tmp = explode(',',$vs_coord_line);
						$va_coord_list[] = array(
							'latitude' 		=> $va_tmp[1],
							'longitude' 	=> $va_tmp[0]
						);
					}
					
					$this->opa_filedata['placemarks'][] = array(
						'name' 			=> $vs_name,
						'type'			=> 'PATH',
						'description' 	=> $vs_description,
						'coordinates' 	=> $va_coord_list
					);
				}
			}
		}
		return true;
	}
	# --------------------------------------------------------------------------------
	public function getPlacemarks() {
		return $this->opa_filedata['placemarks'];
	}
	# --------------------------------------------------------------------------------
}
?>