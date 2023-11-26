<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/IIIFManifests/MixedMedia.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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

class MixedMedia extends BaseIIIFManifest {
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
			'label' => ['none' => []],
			'metadata' => [],
			//'requiredStatement' => ['label' => ['none' => ['TODO']]],
			//'rights' => 'TODO',
			//'thumbnail' => null,
			//'seeAlso' => null,
			//'homepage' => null,
			//'partOf' => null,
			'items' => []
		];
	
		foreach($identifiers as $identifier) {
			if(!is_array($media = \IIIFService::getMediaInstance($identifier, $g_request))) {
				throw new \IIIFAccessException(_t('Unknown error'), 400);
			}
			
			// $item = [
// 				'id' => $this->base_url.$identifier,
// 				'type' => 'Canvas',
// 				'label' => ['none' => [$media['instance']->get('preferred_labels')]],
// 				'width' => null,
// 				'height' => null
// 			];
			$mwidth = $mheight = null;
			
			switch($media['type']) {
				case 'representation':
				
					break;
				case 'attribute':
				
					break;
				default:
					$reps = $media['instance']->getRepresentations(['original', 'thumbnail', 'preview170', 'medium', 'h264_hi', 'mp3'], null, ['includeAnnotations' => true]);
					
					$replist = [];
					
					foreach($reps as $rep) {
						$w = $rep['info']['original']['WIDTH'];
						$h = $rep['info']['original']['HEIGHT'];
						
						if(is_null($mwidth) || ($w > $mwidth)) { $mwidth = $w; }
						if(is_null($mheight) || ($h > $mheight)) { $mheight = $h; }
						
						$page = 1; // @TODO: fix
						
						$service_url = "{$this->base_url}/service.php/IIIF/representation:{$rep['representation_id']}:{$page}";
						
						$thumb_width = $rep['info']['thumbnail']['WIDTH'];
						$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
						$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
						
						$base_iiif_id = $this->manifest_url.'-'.$rep['representation_id'];
						
						$rep_mimetype = $rep['info']['original']['MIMETYPE'];
						
						$rep_media_class = caGetMediaClass($rep_mimetype, ['forIIIF' => true]);
						
						$services = null;
						$media_url = null;
						$placeholder_url = $placeholder_width = $placeholder_height = $placeholder_mimetype = null;
						$thumb_url = $thumb_width = $thumb_height = $thumb_mimetype = null;
						$d = null;
						
						$annotations = [];
						switch($rep_media_class) {
							case 'Image':
								$services = [
									[
										'id' => $service_url,
										'type' => 'ImageService2',
										'profile' => 'http://iiif.io/api/image/2/level2.json"'
									]
								];
								$media_url = $service_url.'/full/max/0/default.jpg';
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
												
								$thumb_url = $rep['urls']['thumbnail'];				
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
							case 'Video':
								if(!($media_url = ($rep['urls']['h264_hi'] ?? null))) {
									$media_url = $rep['urls']['original'];
								}
								$d = $rep['info']['original']['PROPERTIES']['duration'];
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								if(is_array($rep['captions']) && sizeof($rep['captions'])) {
									foreach($rep['captions'] as $ci => $caption_info) {
										$annotations[] =
											[
												'id' => $base_iiif_id.'-annotation-subtitles-'.$ci,
												'type' => 'AnnotationPage',
												'items' => [
													[
													  'id' => $base_iiif_id.'-annotation-subtitles-vtt-'.$ci,
													  'type' => 'Annotation',
													  'motivation' => 'supplementing',
													  'body' => [
														'id' => $caption_info['url'],
														'type' => 'Text',
														'format' => 'text/vtt',
														'label' => [
														  'en' => ['Subtitles']
														],
														'language' => substr($caption_info['locale_code'], 0, 2)
													  ],
													  'target' => $base_iiif_id
													],
												],
											];
									}
								}
								if($rep['num_annotations'] > 0) {
									$annotations[] =
											[
												'id' => $base_iiif_id.'-annotation-clips-'.$ci,
												'type' => 'AnnotationPage',
												'items' => [
													[
													  'id' => $base_iiif_id.'-annotation-clips-json-'.$ci,
													  'type' => 'Annotation',
													  'motivation' => 'supplementing',
													  'body' => [
														'id' => preg_replace("!/IIIF/manifest/.*$!", "/IIIF/cliplist/", $this->manifest_url)."representation:".$rep['representation_id'],
														'type' => 'Text',
														'format' => 'application/json',
														'label' => [
														  'en' => ['Clips']
														],
														'language' => substr($caption_info['locale_code'], 0, 2)
													  ],
													  'target' => $base_iiif_id
													],
												],
											];
								}
								break;
							case 'Sound':
								if(!($media_url = ($rep['urls']['mp3'] ?? null))) {
									$media_url = $rep['urls']['original'];
								}
								$d = $rep['info']['original']['PROPERTIES']['duration'];
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
							case 'Text':
								if(!($media_url = $rep['urls']['compressed'] ?? null)) {
									$media_url = $rep['urls']['original'];
								}
								
								$placeholder_url = $rep['urls']['medium'];								
								$placeholder_width = $rep['info']['medium']['WIDTH'];
								$placeholder_height = $rep['info']['medium']['HEIGHT'];
								$placeholder_mimetype = $rep['info']['medium']['MIMETYPE'];
								
								$thumb_url = $rep['urls']['thumbnail'];
								$thumb_width = $rep['info']['thumbnail']['WIDTH'];
								$thumb_height = $rep['info']['thumbnail']['HEIGHT'];
								$thumb_mimetype = $rep['info']['thumbnail']['MIMETYPE'];
								break;
						}
						
						if($rep['label'] === '[BLANK]') { $rep['label'] = ''; }
						$repinfo = [
							'id' => $base_iiif_id,
							'type' => 'Canvas',
							'label' => ['none' => [$rep['label']]],
							'width' => $w,
							'height' => $h,
							'duration' => $d,
							'thumbnail' => [[
								'id' => $thumb_url,
								'type' => 'Image',
								'format' => $thumb_mimetype,
								'width' => $thumb_width,
								'height' => $thumb_height
							]],
							'items' => [
								[
									'id' => $base_iiif_id.'-item-page',
									'type' => 'AnnotationPage',
									'items' => [
										[
											'id' => $base_iiif_id.'-annotation',
											'type' => 'Annotation',
											'motivation' => 'painting',
											'target' => $base_iiif_id,
											'body' => [
												'id' => $media_url,
												'type' => $rep_media_class,
												'format' => $rep_mimetype,
												'width' => $w,
												'height' => $h,
												'duration' => $d,
												'service' => $services
											],
										]
									],
								]
							],
							'annotations' => $annotations,
							'placeholderCanvas' => [
								'id' => $base_iiif_id.'-placeholder',
								'type' => 'Canvas',
								'width' => $placeholder_width,
								'height' => $placeholder_height,
								'items' => [
									[
										'id' => $base_iiif_id.'-placeholder-annotation-page',
										'type' => 'AnnotationPage',
										'items' => [
											[
												'id' => $base_iiif_id.'-placeholder-annotation',
												'type' => 'Annotation',
												'motivation' => 'painting',
												'body' => [
												  'id' => $placeholder_url,
												  'type' => 'Image',
												  'format' => $placeholder_mimetype,
												  'width' => $placeholder_width,
												  'height' => $placeholder_height
												],
												'target' => $base_iiif_id.'-placeholder'
											]
										]
									]
								]
							]
						];
						
						if(!$services) {
							unset($repinfo['items'][0]['items'][0]['body']['service']);
						}
						if(!$d) {
							unset($repinfo['duration']);
							unset($repinfo['items'][0]['items'][0]['body']['duration']);
						}
						
						if(!$w) {
							unset($repinfo['items'][0]['items'][0]['body']['width']);
							unset($repinfo['items'][0]['items'][0]['body']['height']);
						}
						
						$replist[] = $repinfo;
					}
					break;
			}
			
			$json['items'] = $replist;
		}
		return $json;
	}
	# -------------------------------------------------------
}