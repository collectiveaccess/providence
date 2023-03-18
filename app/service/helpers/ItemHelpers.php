<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/ItemHelpers.php :
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
 * ----------------------------------------------------------------------
 */
namespace GraphQLServices\Helpers\Item;

/**
 *
 */
function processTarget(\BaseModel $rec, string $table, array $t, ?array $options=null) : array {
	if(!\Datamodel::tableExists($t['table'])) { 
		throw new \ServiceException(_t('Invalid table'));
	}
	if(!($linking_table = \Datamodel::getLinkingTableName($table, $t['table']))) {
		throw new \ServiceException(_t('Cannot resolve relationship'));
	}
	$resolve_to_related = caGetOption('resolveRelativeToRelated', $options, false);
	
	$target_name = $t['name'] ?? $t['table'];
	
	$include_media = $t['includeMedia'] ?? false;
	$media_versions = $t['mediaVersions'] ?? ["thumbnail", "small", "medium", "large", "original"];

	$target_pk = \Datamodel::primaryKey($t['table']);
	$rels = $rec->getRelatedItems($t['table'], ['checkAccess' => $check_access, 'primaryIDs' => [$rec->getPrimaryKey()], 'restrictToTypes' => $t['restrictToTypes'], 'restrictToRelationshipTypes' => $t['restrictToRelationshipTypes']]);

	$rel_list = [];
	if (sizeof($rel_ids = array_map(function($v) use ($resolve_to_related, $target_pk, $t) { return $v[$resolve_to_related ? $target_pk : 'relation_id']; }, $rels)) > 0) {
		$rel_types = array_values(array_map(function($v) use ($t) {
			return [
				'relationship_typename' => $v['relationship_typename'],
				'relationship_typecode' => $v['relationship_type_code'],
				'relationship_type_id' => $v['relationship_type_id'],
			];
		}, $rels));
		
		$start = $t['start'] ?? 0;
		if($start < 0) { $start = 0; }
		if($start >= sizeof($rel_ids)) { $start = 0; }
		$limit = $t['limit'] ?? null;
		if($limit < 1) { $limit = null; }
		if(($start > 0) || ($limit > 0)) {
			$rel_ids = array_slice($rel_ids, $start, $limit);
			$rel_types = array_slice($rel_types, $start, $limit);
		}
		
		$qr = caMakeSearchResult($resolve_to_related ? $t['table'] : $linking_table, $rel_ids);
		while($qr->nextHit()) {
			$r = $qr->getInstance();
	
			$rel_type = array_shift($rel_types);
			$bundles = \GraphQLServices\Helpers\extractBundleNames($r, $t);
			$data = \GraphQLServices\Helpers\fetchDataForBundles($r, $bundles, ['primaryIDs' => [$rec->getPrimaryKey()]]);
	
			$media = [];
			if($include_media) {
				$m = $resolve_to_related ? $r : \Datamodel::getInstance($t['table'], false, $r->get($t['table'].'.'.$target_pk, ['primaryIDs' => [$rec->getPrimaryKey()]]));
				
				if(is_array($reps = $m->getRepresentations(array_merge($media_versions, ['original']), null, ['restrictToTypes' => $t['restrictMediaToTypes']]))) {
					foreach($reps as $rep_id => $rep_info) {
						$versions = [];
						foreach($rep_info['urls'] as $version => $url) {
							if(!in_array($version, $media_versions)) { continue; }
							$versions[] = [
								'version' => $version,
								'url' => $url,
								'tag' => $rep_info['urls'][$version],
								'width' => $rep_info['info'][$version]['WIDTH'],
								'height' => $rep_info['info'][$version]['HEIGHT'],
								'mimetype' => $rep_info['info'][$version]['MIMETYPE'],
								'filesize' => @filesize($rep_info['paths'][$version]),
								'duration' => $rep_info['info'][$version]['PROPERTIES']['duration'] ?? null,
							];
						}
						
						$media[] = [
							'id' => $rep_id,
							'idno' => $rep_info['idno'],
							'name' => $rep_info['label'],
							'type' => $rep_info['typename'],
							'mimetype' => $rep_info['mimetype'],
							'originalFilename' => $rep_info['original_filename'],
							'versions' => $versions,
							'isPrimary' => (bool)$rep_info['is_primary'],
							'width' => $rep_info['info']['original']['WIDTH'],
							'height' => $rep_info['info']['original']['HEIGHT'],
							'mimetype' => $rep_info['info']['original']['MIMETYPE'],
							'filesize' => @filesize($rep_info['paths']['original']),
							'duration' => $rep_info['info']['original']['PROPERTIES']['duration'] ?? null
						];
					}
				}
			}
			
			$targets = [];
			if(is_array($t['targets']) && sizeof($t['targets'])) {
				$m = \Datamodel::getInstance($t['table'], false, $r->get($t['table'].'.'.$target_pk, ['primaryIDs' => [$rec->getPrimaryKey()]]));
				foreach($t['targets'] as $st) {
					$targets[] = processTarget($m, $m->tableName(), $st, ['resolveRelativeToRelated' => $st['resolveRelativeToRelated'] ?? false]);
				}
			}
		
			if(is_array($rel_ids = $r->get("{$linking_table}.relation_id", ['returnAsArray' => true]))) {
				foreach($rel_ids as $rel_id) {
					$rel_list[] = array_merge([
						'id' => $rel_id,
						'table' => $linking_table,
						'bundles' => $data,
						'targets' => $targets,
						'media' => $media
					], $rel_type);
				}
			}
		}
	}
	return [
		'name' => $target_name,
		'table' => $t['table'],
		'relationships' => $rel_list
	];
}