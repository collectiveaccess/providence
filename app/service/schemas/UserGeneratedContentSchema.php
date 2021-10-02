<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/UserGeneratedContentSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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

class UserGeneratedContentSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [	
			$UserGeneratedContentCommentType = new ObjectType([
				'name' => 'UserGeneratedContentComment',
				'description' => 'User generated comment',
				'fields' => [
					'comment' => [
						'type' => Type::string(),
						'description' => 'Comment text'
					],
					'date' => [
						'type' => Type::string(),
						'description' => 'Date comment was submitted'
					],
					'duration' => [
						'type' => Type::string(),
						'description' => 'Time since comment was submitted'
					],
					'author' => [
						'type' => Type::string(),
						'description' => 'Name of author'
					],
					'email' => [
						'type' => Type::string(),
						'description' => 'Email of author (if available)'
					]
				]
			]),
			$UserGeneratedContentTagType = new ObjectType([
				'name' => 'UserGeneratedContentTag',
				'description' => 'User generated tag',
				'fields' => [
					'tag' => [
						'type' => Type::string(),
						'description' => 'Tag'
					]
				]
			]),
			$UserGeneratedContentContentsType = new ObjectType([
				'name' => 'UserGeneratedContentContents',
				'description' => 'User generated content list contents',
				'fields' => [
					'comments' => [
						'type' => Type::listOf($UserGeneratedContentCommentType),
						'description' => 'User generated comments'
					],
					'tags' => [
						'type' => Type::listOf($UserGeneratedContentTagType),
						'description' => 'User generated tags'
					]
				]
			]),
			$UserGeneratedContentSaveResultType = new ObjectType([
				'name' => 'UserGeneratedSaveResult',
				'description' => 'Result of user generated content save',
				'fields' => [
					'error' => [
						'type' => Type::string(),
						'description' => 'Error message'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Status message for display to user'
					],
					'emailSent' => [
						'type' => Type::boolean(),
						'description' => 'Was notification email sent to user(s)?'
					],
					'commentsAdded' => [
						'type' => Type::int(),
						'description' => 'Number of comments added to record'
					],
					'tagsAdded' => [
						'type' => Type::int(),
						'description' => 'Number of tags added to record'
					]
				]
			]),
			$UserGeneratedContentTagSuggestionsTypes = new ObjectType([
				'name' => 'UserGeneratedContentTagSuggestions',
				'description' => 'Result of user generated content save',
				'fields' => [
					'tags' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Suggested tags'
					]
				]
			]),
		];
	}
	# -------------------------------------------------------
}
