<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/LightboxSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
namespace GraphQLServices\Schemas;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 

class LightboxSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$lightboxType = new ObjectType([
				'name' => 'Lightbox',
				'description' => 'Description for a lightbox',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Unique identifier'
					],
					'title' => [
						'type' => Type::string(),
						'description' => 'Title'
					],
					'count' => [
						'type' => Type::int(),
						'description' => 'Number of items in set'
					],
					'author_fname' => [
						'type' => Type::string(),
						'description' => 'Author first name'
					],
					'author_lname' => [
						'type' => Type::string(),
						'description' => 'Author last name'
					],
					'author_email' => [
						'type' => Type::string(),
						'description' => 'Author email address'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type'
					],
					'created' => [
						'type' => Type::string(),
						'description' => 'Date created'
					],
					'content_type' => [
						'type' => Type::string(),
						'description' => 'Lightbox content type as internal name (Eg. ca_objects)'
					],
					'content_type_singular' => [
						'type' => Type::string(),
						'description' => 'Lightbox content type for display, in singular (Eg. object)'
					],
					'content_type_plural' => [
						'type' => Type::string(),
						'description' => 'Lightbox content type for display, in plural (Eg. objects)'
					]
				]
			]),		
			$lightboxMediaVersionType = new ObjectType([
				'name' => 'LightboxItemMediaVersion',
				'description' => 'Version of media associated with a lightbox item',
				'fields' => [
					'version' => [
						'type' => Type::string(),
						'description' => 'Version'
					],
					'url' => [
						'type' => Type::string(),
						'description' => 'Media URL'
					],
					'tag' => [
						'type' => Type::string(),
						'description' => 'Media as HTML tag'
					],
					'width' => [
						'type' => Type::string(),
						'description' => 'Width, in pixels'
					],
					'height' => [
						'type' => Type::string(),
						'description' => 'Height, in pixels'
					],
					'mimetype' => [
						'type' => Type::string(),
						'description' => 'MIME type'
					],
				]
			]),		
			$lightboxItemType = new ObjectType([
				'name' => 'LightboxItem',
				'description' => 'Description of a lightbox item',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Unique identifier'
					],
					'title' => [
						'type' => Type::string(),
						'description' => 'Title of set item'
					],
					'caption' => [
						'type' => Type::string(),
						'description' => 'Set-specific caption for item'
					],
					'identifier' => [
						'type' => Type::string(),
						'description' => 'Item identifier'
					],
					'rank' => [
						'type' => Type::int(),
						'description' => 'Sort ranking'
					],
					'media' => [
						'type' => Type::listOf($lightboxMediaVersionType),
						'description' => 'Media'
					],
					'detailPageUrl' => [
						'type' => Type::string(),
						'description' => 'URL for page with detailed information about item'
					]
				
				]
			]),	
			$lightboxSortOptionType = new ObjectType([
				'name' => 'LightboxSortOption',
				'description' => 'Sort option for items in lightbox',
				'fields' => [
					'label' => [
						'type' => Type::string(),
						'description' => 'Sort option label'
					],
					'sort' => [
						'type' => Type::string(),
						'description' => 'Sort option specification'
					]
				]
			]),	
			$lightboxCommentType = new ObjectType([
				'name' => 'LightboxComment',
				'description' => 'Lightbox comment',
				'fields' => [
					'content' => [
						'type' => Type::string(),
						'description' => 'Comment text'
					],
					'fname' => [
						'type' => Type::string(),
						'description' => 'First name of commenter'
					],
					'lname' => [
						'type' => Type::string(),
						'description' => 'Last name of commenter'
					],
					'email' => [
						'type' => Type::string(),
						'description' => 'Email address of commenter'
					],
					'user_id' => [
						'type' => Type::Int(),
						'description' => 'User id of commenter'
					],
					'created' => [
						'type' => Type::string(),
						'description' => 'Date created'
					]
				]
			]),		
			$lightboxContentsType = new ObjectType([
				'name' => 'LightboxContents',
				'description' => 'Lightbox contents',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Unique identifier for lightbox'
					],
					'title' => [
						'type' => Type::string(),
						'description' => 'Title of lightbox'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type'
					],
					'created' => [
						'type' => Type::string(),
						'description' => 'Date created'
					],
					'content_type' => [
						'type' => Type::string(),
						'description' => 'Lightbox content type (Eg. objects)'
					],
					'item_count' => [
						'type' => Type::int(),
						'description' => 'Number of items in lightbox'
					],
					'items' => [
						'type' => Type::listOf($lightboxItemType),
						'description' => 'Lightbox items'
					],
					'sortOptions' => [
						'type' => Type::listOf($lightboxSortOptionType),
						'description' => 'Lightbox item sort options'
					],
					'comments' => [
						'type' => Type::listOf($lightboxCommentType),
						'description' => 'Lightbox comments'
					]
				]
			]),
			$lightboxAccessType = new ObjectType([
				'name' => 'LightboxAccess',
				'description' => 'User access to lightbox',
				'fields' => [
					'access' => [
						'type' => Type::int(),
						'description' => 'Access level'
					]
				]
			]),	
			$lightboxMutationStatusType = new ObjectType([
				'name' => 'LightboxMutationStatus',
				'description' => 'Information relating to update of lightbox metadata',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Lightbox ID'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Lightbox name'
					],
					'count' => [
						'type' => Type::int(),
						'description' => 'Number of items in lightbox',
						'default' => null
					],
					'list' => [
						'type' => Type::listOf($lightboxType),
						'description' => 'List of available lightboxes',
						'default' => null
					],
				]
			]),
			$lightboxMutationNewCommentType = new ObjectType([
				'name' => 'LightboxMutationNewComment',
				'description' => 'Information relating to newly created comment',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Lightbox ID'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Lightbox name'
					],
					'count' => [
						'type' => Type::int(),
						'description' => 'Number of items in lightbox',
						'default' => null
					],
					'comment' => [
						'type' => $lightboxCommentType,
						'description' => 'New comment, as created'
					]
				]
			]),
			$lightboxCreateInputType = new InputObjectType([
				'name' => 'LightboxCreateInputType',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Lightbox name'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Lightbox code',
						'default' => ''
					]
				]
			]),
			$lightboxEditInputType = new InputObjectType([
				'name' => 'LightboxEditInputType',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Lightbox name'
					]
				]
			]),
			$lightboxReorderInputType = new InputObjectType([
				'name' => 'LightboxReorderInputType',
				'fields' => [
					'sorted_ids' => [
						'type' => Type::string(),
						'description' => 'Sorted lightbox item_ids'
					]
				]
			]),
			$lightboxItemListInputType = new InputObjectType([
				'name' => 'LightboxItemListInputType',
				'fields' => [
					'ids' => [
						'type' => Type::string(),
						'description' => 'Lightbox item ids, separated by ampersands, commas or semicolons.'
					]
				]
			]),
			$lightboxShareInputType = new InputObjectType([
				'name' => 'LightboxShareInputType',
				'fields' => [
					'users' => [
						'type' => Type::string(),
						'description' => 'User emails to share lightbox with, separated by ampersands, commas or semicolons.'
					],
					'access' => [
						'type' => Type::Int(),
						'description' => 'Access level for share. (1=read-only; 2=edit)'
					]
				]
			]),
			$lightboxCommentInputType = new InputObjectType([
				'name' => 'LightboxCommentInputType',
				'fields' => [
					'content' => [
						'type' => Type::string(),
						'description' => 'Comment text.'
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}