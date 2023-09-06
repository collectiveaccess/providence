<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewerSearchManifest.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017-2023 Whirl-i-Gig
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
$data = $this->getVar('data');
$identifier = $this->getVar('identifier');
$t_instance = $this->getVar('t_instance');
$request = $this->getVar('request');
$locs = $this->getVar('locations');
$resources = [];

foreach($locs['locations'] as $p => $loc_parts) {
	if (!is_array($loc_parts)) { continue; }
	foreach($loc_parts as $loc) {
		$x = (int)(($loc['x1p']) * $data['width']);
		$y = (int)(($loc['y1p']) * $data['height']);
		$w = (int)(($loc['x2p']) * $data['width']) - (int)(($loc['x1p']) * $data['width']);
		$h = (int)(($loc['y2p']) * $data['height']) - (int)(($loc['y1p']) * $data['height']);

		$resources[] = [
		  "@id" => "anno_{$identifier}:{$p}",
		  "@type" => "oa:Annotation",
		  "motivation" => "sc:painting",
		  "resource" => [
			"@type" => "cnt:ContentAsText",
			"chars"=> $loc['word']
		  ],
		  "on" => "{$identifier}:{$p}#xywh={$x},{$y},{$w},{$h}"
		];
	}
}


$manifest = [
	"@context" => "http://iiif.io/api/search/0/context.json",
	"@id" => "{$identifier}_results",
	"@type" => "sc:AnnotationList",
	"within" => [
		"@type" => "sc:Layer",
		"total" => sizeof($locs)
	],
	"startIndex" => 0,
	"resources" => $resources
];

print caFormatJson(json_encode($manifest, JSON_UNESCAPED_SLASHES));
