<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/IIIFResponses/Search.php
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

class Search extends BaseIIIFResponse {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $response_type = 'search';
	# -------------------------------------------------------
	/**
	 *
	 */
	public function response(array $data, ?array $options=null) : array {
		global $g_request;
		if(!$g_request) { return null; }
		
		$identifiers = caGetOption('identifiers', $options, null);
		
		$items = [];
		foreach($identifiers as $identifier) {
			if(!is_array($media = \IIIFService::getMediaInstance($identifier, $g_request))) {
				throw new \IIIFAccessException(_t('Unknown error'), 400);
			}
			$reps = $media['instance']->getRepresentations(['original', 'page_preview', 'thumbnail', 'preview170', 'medium', 'h264_hi', 'mp3'], null, ['includeAnnotations' => true]);
		
			foreach($reps as $rep) {
				if(!($t_rep = \ca_object_representations::findAsInstance(['representation_id' => $rep['representation_id']]))) {
					continue;
				}
				
				$page_data = $t_rep->getFileList(null, null, null, ['versions' => []]);
				
				foreach($page_data as $i => $pinfo) {
					$pagenum = $pinfo['resource_path'] ?? 1;
					$target = "page-{$rep['representation_id']}-{$pagenum}";
					foreach($data[$pagenum] as $x => $d) {
						$items[] = [
							'id' => "{$target}-content-{$i}-{$x}",
							'type' => 'Annotation',
							'motivation' => 'highlighting',
							'label' => ['en' => [_t('Page %1', $pagenum)]],
							'body' => [
								'type' => 'TextualBody',
								'value' => $d['value'],
								'format' => 'text/plain'
							],
							'target' => "{$target}#xywh={$d['x']},{$d['y']},{$d['width']},{$d['height']}"
						];
					}
				}
			}
		}
		
		$json = [
			'@context' => 'http://iiif.io/api/search/2/context.json',
			'id' => $this->response_url,
			'type' => 'AnnotationPage',
			'items' => $items
		];
	
		return $json;
	}
	# -------------------------------------------------------
}