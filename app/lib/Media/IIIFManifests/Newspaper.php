<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/IIIFManifests/Newspaper.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023-2024 Whirl-i-Gig
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
namespace CA\Media\IIIFManifests;

class Newspaper extends BaseIIIFManifest {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->manifest_name = 'Newspaper';
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function manifest(array $identifiers, ?array $options=null) : array {
		global $g_request;
		if(!$g_request) { return null; }
		
		if(!is_array($identifiers)) { $identifiers = [$identifiers]; }
		$json = [
			'@context' => 'http://iiif.io/api/presentation/3/context.json',
			'id' => $this->manifest_url,
			'type' => 'Manifest',
			'label' => [],
			'metadata' => [
				[
					'label' => ['en' => ['type']],
					'value' => [
						'en' => ['Newspaper', 'Newspaper Issue']
					]
				],
				[
					'label' => ['en' => ['language']],
					'value' => [
						'en' => ['English']
					]
				]
			],
			"service" => [
				[
				  "id" => "{$this->base_url}/service.php/IIIF/search/{$identifiers[0]}",
				  "type" => "SearchService2"
				]
		  ],
			
			//'requiredStatement' => ['label' => ['none' => ['TODO']]],
			//'rights' => 'TODO',
			'thumbnail' => null,
			'navDate' => null,
			//'seeAlso' => null,
			//'homepage' => null,
			//'partOf' => null,
			'items' => []
		];
	
		foreach($identifiers as $identifier) {
			if(!is_array($media = \IIIFService::getMediaInstance($identifier, $g_request))) {
				throw new \IIIFAccessException(_t('Unknown error'), 400);
			}
			
			$json['label']['en'] = $media['instance']->getWithTemplate('^ca_objects.preferred_labels');
			$json['navDate'] = $media['instance']->get('ca_objects.date.date_value', ['dateFormat' => 'iso8601']);
			
			$json['thumbnail'] = [
				[
				  "id" => $media['instance']->get('ca_object_representations.media.small.url'),
				  "type" => "Image",
				  "format" => "image/jpeg"
				]
			];
			
			$mwidth = $mheight = null;
			
			$pversion = 'original';
			$ptversion = 'preview';
			$reps = $media['instance']->getRepresentations(['original', 'page_preview', 'thumbnail', 'preview170', 'medium', 'h264_hi', 'mp3'], null, ['includeAnnotations' => true]);
			
			$replist = [];
			
			foreach($reps as $rep) {
				if(!($t_rep = \ca_object_representations::findAsInstance(['representation_id' => $rep['representation_id']]))) {
					continue;
				}
				
				$page_data = $t_rep->getFileList(null, null, null, ['versions' => [$pversion, $ptversion]]);

				$mwidth = $mheight = null;
				
				$pages = [];
				foreach($page_data as $i => $pinfo) {
					$page = [];
					
					$pagenum = $pinfo['resource_path'] ?? 1;
					$w = $pinfo["{$pversion}_width"];
					$h = $pinfo["{$pversion}_height"];
					$mimetype = $pinfo["{$pversion}_mimetype"];
					$page_url = $pinfo["{$pversion}_url"];
				
					if(is_null($mwidth) || ($w > $mwidth)) { $mwidth = $w; }
					if(is_null($mheight) || ($h > $mheight)) { $mheight = $h; }
				
					$service_url = "{$this->base_url}/service.php/IIIF/representation:{$rep['representation_id']}:{$pagenum}";
				
					$thumb_url = $thumb_width = $thumb_height = $thumb_mimetype = null;
					$thumb_width = $pinfo["{$ptversion}_width"];
					$thumb_height = $pinfo["{$ptversion}_height"];
					$thumb_mimetype = $pinfo["{$ptversion}_mimetype"];
					$thumb_url = $pinfo["{$ptversion}_url"];
				
					$base_iiif_id = $this->manifest_url.'-'.$rep['representation_id'];
				
					$media_url = $service_url.'/full/max/0/default.jpg';
					$page =  [
						'id' => "page-{$rep['representation_id']}-{$pagenum}",
						'type' => 'Canvas',
						'label' => [
							'en' => ["Page {$pagenum}"]
						],
						'width' => $w,
						'height' => $h,
						//'rendering' => [],
						'items' => [
							[
								'id' => "{$base_iiif_id}-annotation-page-{$pagenum}",
								'type' => 'AnnotationPage',
								'items' => [
									[
										'id' => "{$base_iiif_id}-annotation-page-painting-{$pagenum}",
										'target' => "{$base_iiif_id}-page-{$pagenum}",
										'motivation' => 'painting',
										'type' => 'Annotation',
										'body' => [
											'id' => $media_url,

											'type' => 'Image',
											'format' => 'image/jpeg',
											'service' => [
												'id' => "{$service_url}",
												'type' => 'ImageService3',
												'profile' => 'http://iiif.io/api/image/3/level1.json"',
											]
										]
									]
								]
							]
						],
						// 'annotations' => [
// 							[
// 								'id' => "{$base_iiif_id}-annotation-page-{$pagenum}-anno",
// 								'type' => 'AnnotationPage',
// 								'items' => [
// 									[
// 										'id' => "{$base_iiif_id}-annotation-page-painting-{$pagenum}-anno-1",
// 										'type' => 'Annotation',
// 										'motivation' => 'highlighting',
// 										'body' => [
// 											'type' => 'TextualBody',
// 											'value' => 'testing',
// 											'format' => 'text/plain'
// 										],
// 										'target' => "page-{$rep['representation_id']}-{$pagenum}#xywh=50,50,50,50"
// 									]
// 								]
// 							]
// 						]
					];
					
					$pages[] = $page;
				}
			}
			
			$json['items'] = $pages;
		}
		return $json;
	}
	# -------------------------------------------------------
}
