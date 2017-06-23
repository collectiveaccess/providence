<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewerManifest.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
	$t_subject = $this->getVar('t_subject');
	$vo_request = $this->getVar('request');
	$va_display = caGetOption('display', $va_data, []);
	
	$vs_display_version = caGetOption('display_version', $va_display, 'tilepic');
	
	$va_metadata = [];
	
	if (isset($va_data['resources']) && is_array($va_data['resources']) && sizeof($va_data['resources'])) {
		$va_resources = $va_data['resources'];
	} else {
		$va_resources = [0 => []];
	}
	$va_canvases = [];
	$vn_page = 1;
	
	$vs_base_url = $vo_request->getBaseUrlPath();
	$vn_num_resources = sizeof($va_resources);
	
	$va_services = [];
	
	foreach($va_resources as $va_resource) {
		$vs_canvas_id = "{$vs_identifier}:{$vn_page}";
		
		if (isset($va_resource['noPages'])) {
			// If resource includes explicitly set "noPages" then assume representation identifier with that id
			// (This is used to support the "use_universal_viewer_for_image_list_length_at_least" option in media_display.conf
			//  which forces a list of image representations to be displayed as a multipage document in UniversalViewer)
			$vs_service_url = "{$vs_base_url}/service.php/IIIF/representation:".$va_resource['representation_id']."";
			$vs_thumb_url = "{$vs_base_url}/service.php/IIIF/representation:".$va_resource['representation_id']."/full/!512,512/0/default.jpg";
			$vn_width = $va_resource['width']; $vn_height = $va_resource['height'];
		} elseif ($vs_display_version == 'tilepic') {
			$vs_service_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifier}:{$vn_page}";
			$vs_thumb_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifier}:{$vn_page}/full/!512,512/0/default.jpg";
			$vn_width = $va_data['width']; $vn_height = $va_data['height'];
		} else {
			$vs_service_url = $va_resource['url'];
			$vs_thumb_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifier}:{$vn_page}/full/!512,512/0/default.jpg";
			$vn_width = $va_data['width']; $vn_height = $va_data['height'];
		}
		
		$va_canvases[] =
			[
				"@id" => $vs_canvas_id,
				"@type" => "sc:Canvas",
				"label" => (string)($vn_page),
				"thumbnail" => $va_resource['preview_url'],
				"seeAlso" => [],
				"height" => $vn_height,
				"width" => $vn_width,
				"images" => [
					[
						"@id" => $vs_service_url,
						"@type" => "oa:Annotation",
						"motivation" => "sc:painting",
						"resource" => [
							"@id" => $vs_thumb_url,
							"@type" => "dctypes:Image",
							"format" => "image/jpeg",
							"height" =>  $vn_height,
							"width" => $vn_width,
							"service" => [
								"@context" => "http://iiif.io/api/image/2/context.json",
								"@id" => $vs_service_url,
								"profile" => "http://iiif.io/api/image/2/level1.json"
							]
						],
						"on" => $vs_canvas_id
					]
				]
			];
			$vn_page++;
	}
	
    $va_services[] = [
        "@context" => "http://iiif.io/api/search/0/context.json",
        "@id" => caNavUrl($vo_request, '*', '*', 'SearchMediaData', ['identifier' => $vs_identifier, $t_subject->primaryKey() => $t_subject->getPrimaryKey()]),
        "profile" => "http://iiif.io/api/search/0/search",
        "label" => _t("Search within this manifest"),
        "service" => [
            "@id" => caNavUrl($vo_request, '*', '*', 'MediaDataAutocomplete', ['identifier' => $vs_identifier, $t_subject->primaryKey() => $t_subject->getPrimaryKey()]),
            "profile" => "http://iiif.io/api/search/0/autocomplete",
            "label" => _t("Get suggested words in this manifest")
        ]
    ];
		
	
	$va_manifest = [
		"@context" => "http://iiif.io/api/presentation/2/context.json",
		"@id" => "{$vs_identifier}/manifest",
		"@type" => "sc:Manifest",
		"label" => '',
		"metadata" => $va_metadata,
		"license" => "",
		"logo" => "",
		"related" => [],
		"seeAlso" => [],
		"service" => $va_services,
        "sequences" => [
			[
				"@id" => "{$vs_identifier}/sequence/s0",
				"@type" => "sc:Sequence",
				"label" => "Sequence s0",
				"rendering" => [],
				"viewingHint" => "paged",
				"canvases" => $va_canvases
			]
		]
	];
	
	print caFormatJson(json_encode($va_manifest, JSON_UNESCAPED_SLASHES));