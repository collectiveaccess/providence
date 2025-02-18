<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/IIIFResponses/Clips.php
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Media\IIIFResponses;

class Clips extends BaseIIIFResponse {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $response_type = 'clips';
	# -------------------------------------------------------
	/**
	 *
	 */
	public function response(array $data, ?array $options=null) : array {
		global $g_request;
		if(!$g_request) { return null; }
		
		$identifiers = caGetOption('identifiers', $options, null);
		
		$clip_list = caGetOption('clip_list', $options, []);
	
		$items = [];
		foreach($clip_list as $clip) {
			$items[sprintf('%05d', (int)$clip['page']).$clip['label']] = [
				'type' => 'Annotation',
				'body' => [
					 [
						'value' => $clip['preview'],
						'type' => 'Image', 
						'format' => 'image/jpeg'
					],
					[
						'type' => 'TextualBody',
          				'value' => $clip['label'] ?? '',
          				'format' => 'text/html'
					]
				],
				'motivation' => 'commenting',
				'target' => [
					'type' => 'SpecificResource',
					'source' => [
						'id' => "page-{$clip['representation_id']}-{$clip['page']}",
						'type' => 'Canvas',
						'partOf' => [
							'id' => \IIIFService::manifestUrl(),
							'type' => 'Manifest'
						]
					],
					'selector' => [
						'type' => 'FragmentSelector',
						'conformsTo' => 'http://www.w3.org/TR/media-frags/',
						'value' => "xywh={$clip['x']},{$clip['y']},{$clip['w']},{$clip['h']}"
					]
				],
				'id' => $clip['identifier']
			];
		}
		ksort($items);
		$items = array_values($items);
		
		$json = [
			'@context' => 'http://iiif.io/api/presentation/3/context.json"',
			'id' => $this->response_url,
			'type' => 'AnnotationPage',
			"label" => [
				"none" => [
				  "Clippings"
				]
			],
			'items' => $items
		];
	
		return $json;
	}
	# -------------------------------------------------------
}