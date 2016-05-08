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
	
	$va_metadata = [
		["label" => "title", "value" => $va_data['title']]
	];
	
	if (isset($va_data['resources']) && is_array($va_data['resources']) && sizeof($va_data['resources'])) {
		$va_resources = $va_data['resources'];
	} else {
		$va_resources = [0 => []];
	}
	$va_canvases = [];
	foreach($va_resources as $vn_page => $va_resource) {
		$va_canvases[] =
			[
				"@id" => "http://wellcomelibrary.org/iiif/b18035723/canvas/c0",
				"@type" => "sc:Canvas",
				"label" => " - ",
				"thumbnail" => "http://wellcomelibrary.org/thumbs/b18035723/0/ff2085d5-a9c7-412e-9dbe-dda87712228d.jpg",
				"seeAlso" => [],
				"height" => 3543,
				"width" => 2569,
				"images" => [
					[
						"@id" => "http://seagate.whirl-i-gig.com:8082/admin/service.php/IIIF/{$vs_identifer}".($vn_page ? ":{$vn_page}" : ""),
						"@type" => "oa:Annotation",
						"motivation" => "sc:painting",
						"resource" => [
							"@id" => "http://seagate.whirl-i-gig.com:8082/admin/service.php/IIIF/{$vs_identifer}".($vn_page ? ":{$vn_page}" : "")."/full/!1024,1024/0/default.jpg",
							"@type" => "dctypes:Image",
							"format" => "image/jpeg",
							"height" =>  1024,
							"width" => 742,
							"service" => [
								"@context" => "http://iiif.io/api/image/2/context.json",
								"@id" => "http://seagate.whirl-i-gig.com:8082/admin/service.php/IIIF/{$vs_identifer}".($vn_page ? ":{$vn_page}" : ""),
								"profile" => "http://iiif.io/api/image/2/level1.json"
							]
						],
						"on" => "http://wellcomelibrary.org/iiif/b18035723/canvas/c0"
					]
				]
			];
	}
	
	$va_manifest = [
		"@context" => "http://iiif.io/api/presentation/2/context.json",
		"@id" => "http://wellcomelibrary.org/iiif/b18035723/manifest",
		"@type" => "sc:Manifest",
		"label" => $this->getVar('title'),
		"metadata" => $va_metadata,
		"license" => "",
		"logo" => "",
		"related" => [],
		"seeAlso" => [],
		"service" => [
			 [
				"@context" => "http://wellcomelibrary.org/ld/iiif-ext/0/context.json",
				"@id" => "http://wellcomelibrary.org/iiif/b18035723-0/access-control-hints-service",
				"profile" => "http://wellcomelibrary.org/ld/iiif-ext/access-control-hints",
				"accessHint" => "open"
			],
			[
				"@context" => "http://iiif.io/api/search/0/context.json",
				"@id" => "http://wellcomelibrary.org/annoservices/search/b18035723",
				"profile" => "http://iiif.io/api/search/0/search",
				"label" => "Search within this manifest",
				"service" => [
					"@id" => "http://wellcomelibrary.org/annoservices/autocomplete/b18035723",
					"profile" => "http://iiif.io/api/search/0/autocomplete",
					"label" => "Get suggested words in this manifest"
				]
			],
			[
				"@context" => "http://wellcomelibrary.org/ld/iiif-ext/0/context.json",
				"profile" => "http://universalviewer.io/tracking-extensions-profile",
				"trackingLabel" => "Format: monograph, Institution: n/a, Identifier: b18035723, Digicode: diggenetics, Collection code: n/a"
			]
		],
		"sequences" => [
			[
				"@id" => "http://wellcomelibrary.org/iiif/b18035723/sequence/s0",
				"@type" => "sc:Sequence",
				"label" => "Sequence s0",
				"rendering" => [
					[
						"@id" => "http://wellcomelibrary.org/pdf/b18035723/0/b18035723_0.pdf",
						"format" => "application/pdf",
						"label" => "Download"
					]
				],
				"viewingHint" => "paged",
				"canvases" => $va_canvases
			]
		]
	];
	
	print caFormatJson(json_encode($va_manifest, JSON_UNESCAPED_SLASHES));