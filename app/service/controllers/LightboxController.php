<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/LightboxController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/LightboxSchema.php');
require_once(__CA_APP_DIR__.'/service/helpers/LightboxHelpers.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\LightboxSchema;
use GraphQLServices\Helpers\Lightbox;


class LightboxController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				'list' => [
					'type' => Type::listOf(LightboxSchema::get('Lightbox')),
					'description' => _t('List of available lightboxes'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
					
						return \GraphQLServices\Helpers\Lightbox\getLightboxList($u);
					}
				],
				'content' => [
					'type' => LightboxSchema::get('LightboxContents'),
					'description' => _t('Content of specified lightbox'),
					'args' => [
						'id' => Type::int(),
						[
							'name' => 'mediaVersions',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of media versions to return'),
							'defaultValue' => ['small']
						],
						[
							'name' => 'start',
							'type' => Type::int(),
							'description' => _t('Zero-based index of first item returned'),
							'defaultValue' => 0
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of items to return'),
							'defaultValue' => null
						],
						[
							'name' => 'sort',
							'type' => Type::string(),
							'description' => _t('Field to sort on'),
							'defaultValue' => null
						],
						[
							'name' => 'sortDirection',
							'type' => Type::string(),
							'description' => _t('Direction of sort. Valid values are ASC and DESC.'),
							'defaultValue' => null
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						try {
							if (!($u = self::authenticate($args['jwt']))) {
								throw new \ServiceException(_t('Invalid JWT'));
							}
						} catch(Exception $e) {
						
						}
						$t_set = new \ca_sets($args['id']);
						
						
						// Get configured sorts
						$conf = Configuration::load(__CA_CONF_DIR__.'/lightbox.conf');
						$browse_conf = $conf->get('lightboxBrowse');
						$sorts = caGetOption('sortBy', $browse_conf, [], ['castTo' => 'array']);
						
						$views = caGetOption('views', $browse_conf, [], ['castTo' => 'array']);
						
						
						$sort_opts = [];
						foreach($sorts as $label => $sort) {
							$sort_opts[] = [
								'label' => $label,
								'sort' => $sort
							];
						}
						
						$comments = [];
						if (is_array($raw_comments = $t_set->getComments())) {
							$comments = array_values(array_map(function($v) {
								return [
									'fname' => $v['fname'],
									'lname' => $v['lname'],
									'email' => $v['user_email'],
									'content' => $v['comment'],
									'user_id' => $v['user_id'],
									'created' => date('c', $v['created_on'])
								];
							}, $raw_comments));
						}
						
						// TODO: check access
						$lightbox = [
							'id' => $t_set->get('ca_sets.set_id'),
							'title' => $t_set->get('ca_sets.preferred_labels.name'),
							'type' => $t_set->get('ca_sets.type_id', ['convertCodesToIdno' => true]),
							'created' => date('c', $t_set->get('ca_sets.created.timestamp')),
							'content_type' => Datamodel::getTableName($t_set->get('ca_sets.table_num')),
							'item_count' => $t_set->getItemCount(),
							'items' => [],
							'sortOptions'=> $sort_opts,
							'comments' => $comments
						];
						
						
						$table_num = $t_set->get('table_num');
						$table = Datamodel::getTableName($table_num);
						$items = caExtractValuesByUserLocale($t_set->getItems([
							'thumbnailVersions' => $args['mediaVersions'], 
							'start' => $args['start'], 
							'limit' => $args['limit'],
							'sort' => $args['sort'],
							'template' => $views['images']['caption'] ?? null,
							'sortDirection' => $args['sortDirection']
						]));
						
						// set current context to allow "back" navigation to specific lightbox
						global $g_request;
						$rc = new ResultContext($g_request, $table, 'lightbox');
						$rc->setResultList(array_unique(array_map(function($v) { return $v['row_id']; }, $items)));
						$rc->setParameter('set_id', $t_set->getPrimaryKey());
						$rc->setAsLastFind(false);
 						$rc->saveContext();
						
						$lightbox['items'] = array_map(
							function($i) use ($table_num) {
								$media_versions = [];
								foreach($i as $k => $v) {
									if (preg_match('!^representation_url_(.*)$!', $k, $m)) {
										if (!$v) { continue; }
										$media_versions[] = [
											'version' => $m[1],
											'url' => $v,
											'tag' => $i['representation_tag_'.$m[1]],
											'width' => $i['representation_width_'.$m[1]],
											'height' => $i['representation_height_'.$m[1]],
											'mimetype' => $i['representation_mimetype_'.$m[1]],
										];
									}
								}
								$detailPageUrl = str_replace('service.php', 'index.php', caDetailUrl($this->request, $table_num, $i['row_id'], false, [], []));
								return [
									'item_id' => $i['item_id'],
									'title' => $i['displayTemplate'] ?? $i['set_item_label'],
									'caption' => $i['caption'],
									'id' => $i['row_id'],
									'rank' => $i['rank'],
									'identifier' => $i['idno'],
									'media' => $media_versions,
									'detailPageUrl' => $detailPageUrl
								];
							},
							$items
						);
					
						return $lightbox;
					}
				],
				'access' => [
					'type' => LightboxSchema::get('LightboxAccess'),
					'description' => _t('Access for current user to specified lightbox'),
					'args' => [
						'id' => Type::int(),
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						$set_id = $this->request->getParameter('set_id', pInteger);
						
						$jwt_data = self::decodeJWT($args['jwt']);
						if (!($user_id = $jwt_data->id)) {
							throw new \ServiceException(_t('Invalid user'));
						}

						if (($t_set = ca_sets::find(['set_id' => $args['id']], ['returnAs' => 'firstModelInstance'])) && $t_set->haveAccessToSet($user_id, __CA_SET_READ_ACCESS__)) {
							$access = (($t_set->haveAccessToSet($user_id, __CA_SET_EDIT_ACCESS__)) ? 2 : 1);
							return ['access' => $access];
						} else {
							return ['access' => null];
						}
					}
				]
			],
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				'create' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Create new lightbox'),
					'args' => [
						[
							'name' => 'data',
							'type' => LightboxSchema::get('LightboxCreateInputType'),
							'description' => _t('New values for lightbox')
						],
						[
							'name' => 'content',
							'type' => Type::string(),
							'description' => _t('Table code for content type of lightbox (ex. ca_objects)'),
							'default' => 'ca_objects'
						],
						[
							'name' => 'items',
							'type' => LightboxSchema::get('LightboxItemListInputType'),
							'description' => _t('IDs to add to lightbox, separated by ampersands, commas or semicolons')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
					
						// TOOD: check access; use user's locale
						$name = $args['data']['name'];
						$code = mb_substr(caGetOption('code', $args['data'], md5($name.time().rand(0,10000))),0 , 32);
						
						if (!caGetListItemID('set_types', $type_id = Configuration::load()->get('user_set_type'))) {
							$type_id = caGetDefaultItemID('set_types');
						}
						
						$t_set = new ca_sets();
						$t_set->set([
							'type_id' => $type_id,
							'set_code' => $code,
							'table_num' => (int)Datamodel::getTableNum($args['table']),
							'user_id' => $u->getPrimaryKey()
						]);
						$t_set->insert();
						if($t_set->numErrors()) {
							throw new ServiceException(_t('Could not create lightbox: %1', join($t_set->getErrors())));
						}
						if (!$t_set->addLabel(['name' => $name], ca_locales::getDefaultCataloguingLocaleID(), null, true)) {
							throw new ServiceException(_t('Could not add label to lightbox: %1', join($t_set->getErrors())));
						}
						
						$n = 0;
						if (is_array($add_item_ids = preg_split('![&,;]+!', $args['items']['ids'])) && sizeof($add_item_ids)) {
							$n = $t_set->addItems($add_item_ids);
						}
						return ['id' => $t_set->getPrimaryKey(), 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $n, 'list' => \GraphQLServices\Helpers\Lightbox\getLightboxList($u)];
					}
				],
				'edit' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Upload lightbox metadata'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to edit'),
							'defaultValue' => null
						],
						[
							'name' => 'data',
							'type' => LightboxSchema::get('LightboxEditInputType'),
							'description' => _t('New values for lightbox')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
					
						// TOOD: check access; use user's locale; valid input
						$id = $args['id'];
						$name = $args['data']['name'];
						
						$t_set = new ca_sets($id);
						$t_set->replaceLabel(['name' => $name], ca_locales::getDefaultCataloguingLocaleID(), null, true);
						
						return ['id' => $t_set->getPrimaryKey(), 'name' => $t_set->get('ca_sets.preferred_labels.name')];
					}
				],
				'delete' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Delete lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to edit'),
							'defaultValue' => null
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
					
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->delete(true)) {
							throw new ServiceException(_t('Could not delete lightbox: %1', join($t_set->getErrors())));
						}
						
						return ['id' => $args['id'], 'name' => 'DELETED', 'list' => \GraphQLServices\Helpers\Lightbox\getLightboxList($u)];
					}
				],
				'reorder' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Reorder lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to reorder'),
							'defaultValue' => null
						],
						[
							'name' => 'data',
							'type' => LightboxSchema::get('LightboxReorderInputType'),
							'description' => _t('Reorder values for lightbox')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
					
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->isLoaded()) {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}

						$sorted_id_str = $args['data']['sorted_ids'];
						$sorted_id_arr = preg_split('![&;,]!', $sorted_id_str);
						$sorted_id_int_arr = array_filter(array_map(function($v) { return (int)$v; }, $sorted_id_arr), function($v) { return ($v > 0); });
						
						$errors = $t_set->reorderItems($sorted_id_int_arr, ['user_id' => $u->getPrimaryKey()]);
						if(sizeof($errors) > 0) {
							throw new ServiceException(_t('Could not sort lightbox: %1', join($t_set->getErrors())));
						}
						return ['id' => $args['id'], 'name' => $t_set->get('ca_sets.preferred_labels.name')];
					}
				],
				'appendItems' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Append items to a lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to append items to. Omit if creating a new lightbox.'),
							'defaultValue' => null
						],
						[
							'name' => 'lightbox',
							'type' => LightboxSchema::get('LightboxCreateInputType'),
							'description' => _t('New values for lightbox')
						],
						[
							'name' => 'items',
							'type' => LightboxSchema::get('LightboxItemListInputType'),
							'description' => _t('IDs to add to lightbox, separated by ampersands, commas or semicolons')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						if (!is_array($add_item_ids = preg_split('![&,;]+!', $args['items']['ids'])) || !sizeof($add_item_ids)) {
							throw new ServiceException(_t('No item ids set'));
						}
					
						// TOOD: check access
						if ($set_id = $args['id']) {
							$t_set = new ca_sets($set_id);
						} else {
							// create new set
							// TOOD: check access; use user's locale
							$name = $args['lightbox']['name'];
							$code = mb_substr(caGetOption('code', $args['lightbox'], md5($name.time().rand(0,10000))),0 , 32);
						
							if (!caGetListItemID('set_types', $type_id = Configuration::load()->get('user_set_type'))) {
								$type_id = caGetDefaultItemID('set_types');
							}
						
							$t_set = new ca_sets();
							$t_set->set([
								'type_id' => $type_id,
								'set_code' => $code,
								//'table_num' => (int)Datamodel::getTableNum('ca_objects'),
								'user_id' => $u->getPrimaryKey()
							]);
							$t_set->insert();
							if($t_set->numErrors()) {
								throw new ServiceException(_t('Could not create lightbox: %1', join($t_set->getErrors())));
							}
							$t_set->addLabel(['name' => $name], ca_locales::getDefaultCataloguingLocaleID(), null, true);
						}
						
						if ($t_set->isLoaded()) {
							$t_set->addItems($add_item_ids);
						} else {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}
						
						return ['id' => $t_set->getPrimaryKey(), 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $t_set->getItemCount()];
					}
				],
				'removeItems' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Remove items from lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to remove items from'),
							'defaultValue' => null
						],
						[
							'name' => 'items',
							'type' => LightboxSchema::get('LightboxItemListInputType'),
							'description' => _t('Item ids to remove, separated by ampersands, commas or semicolons')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						
						if (!is_array($item_ids = preg_split('![&,;]+!', $args['items']['ids'])) || !sizeof($item_ids)) {
							throw new ServiceException(_t('No item ids set'));
						}
					
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->isLoaded()) {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}
						
						if (!$t_set->removeItems($item_ids, $u->getPrimaryKey())) {
							throw new ServiceException(_t('Could not remove items from lightbox: %1', join($t_set->getErrors())));
						}
						
 						return ['id' => $args['id'], 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $t_set->getItemCount()];
					}
				],
				'transferItems' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Transfer items from lightbox to lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to transfer items from'),
							'defaultValue' => null
						],
						[
							'name' => 'toId',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to transfer items to'),
							'defaultValue' => null
						],
						[
							'name' => 'items',
							'type' => LightboxSchema::get('LightboxItemListInputType'),
							'description' => _t('Items ids to transfer, separated by ampersands, commas or semicolons')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						if (!is_array($item_ids = preg_split('![&,;]+!', $args['items']['ids'])) || !sizeof($item_ids)) {
							throw new ServiceException(_t('No item ids set'));
						}
					
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->isLoaded()) {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}
						
						$t_set->transferItemsTo($args['toId'], $item_ids, $u->getPrimaryKey());
						
 						return ['id' => $args['id'], 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $t_set->getItemCount()];
					}
				],
				'share' => [
					'type' => LightboxSchema::get('LightboxMutationStatus'),
					'description' => _t('Share lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to share'),
							'defaultValue' => null
						],
						[
							'name' => 'share',
							'type' => LightboxSchema::get('LightboxShareInputType'),
							'description' => _t('Share settings')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->isLoaded()) {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}
						
						// TODO:
						
 						return ['id' => $args['id'], 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $t_set->getItemCount()];
					}
				],
				'comment' => [
					'type' => LightboxSchema::get('LightboxMutationNewComment'),
					'description' => _t('Add comment to lightbox'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of lightbox to comment on'),
							'defaultValue' => null
						],
						[
							'name' => 'comment',
							'type' => LightboxSchema::get('LightboxCommentInputType'),
							'description' => _t('Comment settings')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						if (!($u = self::authenticate($args['jwt']))) {
							throw new ServiceException(_t('Invalid JWT'));
						}
						
						// TOOD: check access
						$t_set = new ca_sets($args['id']);
						if(!$t_set->isLoaded()) {
							throw new ServiceException(_t('Could not load lightbox: %1', join($t_set->getErrors())));
						}
						
						$comment = [];
						if ($t_comment = $t_set->addComment($args['comment']['content'], null, $u->getPrimaryKey(), null, null, null, 0, null, [])){
							$user = new ca_users($t_comment->get('ca_item_comments.user_id'));
							$comment = [
								'fname' => $user->get('ca_users.fname'),
								'lname' => $user->get('ca_users.lname'),
								'email' => $user->get('ca_users.email'),
								'content' => $t_comment->get('ca_item_comments.comment'),
								'user_id' => $user->getPrimaryKey(),
								'created' => date('c', $t_comment->get('ca_item_comments.created_on', ['getDirectDate' => true]))
								
							];
						}
 						return ['id' => $args['id'], 'name' => $t_set->get('ca_sets.preferred_labels.name'), 'count' => $t_set->getItemCount(), 'comment' => $comment];
					}
				]
			],
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
}
