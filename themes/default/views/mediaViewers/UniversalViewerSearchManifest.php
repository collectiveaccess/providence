<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewerSearchManifest.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
	$va_data = $this->getVar('data');
	$vs_identifier = $this->getVar('identifier');
	$t_instance = $this->getVar('t_instance');
	$vo_request = $this->getVar('request');
	
	$va_locs = $this->getVar('locations');
	
	$va_resources = [];
	
	foreach($va_locs['locations'] as $p => $va_loc_parts) {
	    if (!is_array($va_loc_parts)) { continue; }
	    foreach($va_loc_parts as $va_loc) {
            $x = (int)(($va_loc['x1p']) * $va_data['width']);
            $y = (int)(($va_loc['y1p']) * $va_data['height']);
            $w = (int)(($va_loc['x2p']) * $va_data['width']) - (int)(($va_loc['x1p']) * $va_data['width']);
            $h = (int)(($va_loc['y2p']) * $va_data['height']) - (int)(($va_loc['y1p']) * $va_data['height']);
    
            $va_resources[] = [
              "@id" => "anno_{$vs_identifier}:{$p}",
              "@type" => "oa:Annotation",
              "motivation" => "sc:painting",
              "resource" => [
                "@type" => "cnt:ContentAsText",
                "chars"=> $va_loc['word']
              ],
              "on" => "{$vs_identifier}:{$p}#xywh={$x},{$y},{$w},{$h}"
            ];
        }
	}
	
	
	$va_manifest = [
        "@context" => "http://iiif.io/api/search/0/context.json",
        "@id" => "https://wellcomelibrary.org/annoservices/search/b18035723?q=wunder",
        "@type" => "sc:AnnotationList",
        "within" => [
            "@type" => "sc:Layer",
            "total" => sizeof($va_locs)
        ],
        "startIndex" => 0,
        "resources" => $va_resources
    ];
	
	print caFormatJson(json_encode($va_manifest, JSON_UNESCAPED_SLASHES));