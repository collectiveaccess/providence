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
	$vs_identifer = $this->getVar('identifier');
	$t_instance = $this->getVar('t_instance');
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
	
	foreach($va_resources as $va_resource) {
		$vs_canvas_id = "{$vs_identifer}:{$vn_page}";
		
		if ($vs_display_version == 'tilepic') {
			$vs_service_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifer}:{$vn_page}";
			$vs_thumb_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifer}:{$vn_page}/full/!1024,1024/0/default.jpg";
		} else {
			$vs_service_url = $va_resource['url'];
			$vs_thumb_url = "{$vs_base_url}/service.php/IIIF/{$vs_identifer}:{$vn_page}/full/!1024,1024/0/default.jpg";
		}
		
		$va_canvases[] =
			[
				"@id" => $vs_canvas_id,
				"@type" => "sc:Canvas",
				"label" => (string)($vn_page),
				"thumbnail" => $va_resource['preview_url'],
				"seeAlso" => [],
				"height" => $va_data['width'],
				"width" => $va_data['height'],
				"images" => [
					[
						"@id" => $vs_service_url,
						"@type" => "oa:Annotation",
						"motivation" => "sc:painting",
						"resource" => [
							"@id" => $vs_thumb_url,
							"@type" => "dctypes:Image",
							"format" => "image/jpeg",
							"height" =>  $va_data['width'],
							"width" => $va_data['height'],
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
	
	$va_manifest = [
		"@context" => "http://iiif.io/api/presentation/2/context.json",
		"@id" => "{$vs_identifer}/manifest",
		"@type" => "sc:Manifest",
		"label" => '',
		"metadata" => $va_metadata,
		"license" => "",
		"logo" => "",
		"related" => [],
		"seeAlso" => [],
		"service" => [
		],
		"sequences" => [
			[
				"@id" => "{$vs_identifer}/sequence/s0",
				"@type" => "sc:Sequence",
				"label" => "Sequence s0",
				"rendering" => [],
				"viewingHint" => "paged",
				"canvases" => $va_canvases
			]
		]
	];
	
	print caFormatJson(json_encode($va_manifest, JSON_UNESCAPED_SLASHES));