<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/UserGeneratedContentController.php :
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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/UserGeneratedContentSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\UserGeneratedContentSchema;


class UserGeneratedContentController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				'content' => [
					'type' => UserGeneratedContentSchema::get('UserGeneratedContentContents'),
					'description' => _t('Return comments and tags for a record'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of record to generate grid for')
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table of record to generate grid for. (Ex. ca_entities)')
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
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = null;
						if($args['jwt']) {
							try {
								$u = self::authenticate($args['jwt']);
							} catch(Exception $e) {
								$u = new ca_users();
							}
						}
						
						$table = $args['table'];
						$id = $args['id'];
						
						$start = (int)$args['start'];
						$limit = (int)$args['limit'];
											
						if (!($t_subject = Datamodel::getInstance($table, true, $id))) {
							throw new ServiceException(_t('Invalid table or id'));
						}
						if (!$t_subject->isReadable($u)) {
							throw new ServiceException(_t('Access denied'));
						}
						
						#
						# User-generated comments and tags
						#
						$user_comments = $t_subject->getComments(null, true);
						$comments = [];
						if (is_array($user_comments)) {
							$user_comments = array_reverse($user_comments);
							foreach($user_comments as $user_comment){
								if($user_comment["comment"] || $user_comment["media1"] || $user_comment["media2"] || $user_comment["media3"] || $user_comment["media4"]){
									$user_comment["date"] = date("n/j/Y @ g:ia", $user_comment["created_on"]);
									$user_comment["duration"] = caFormatInterval(time()-$user_comment["created_on"], 2);
									
									# -- get name of commenter
									if($user_comment["user_id"]){
										$t_user = new ca_users($user_comment["user_id"]);
										$user_comment["author"] = $t_user->getName();
										$user_comment["fname"] = $t_user->get("fname");
										$user_comment["lname"] = $t_user->get("lname");
										$user_comment["email"] = $t_user->get("email");
									}elseif($user_comment["name"]){
										$user_comment["author"] = $user_comment["name"];
									}
									$comments[] = $user_comment;
								}
							}
						}
		
		
						$user_tags = $t_subject->getTags(null, true);
						$tags = [];
		
						if (is_array($user_tags)) {
							foreach($user_tags as $user_tag){
								if(!array_key_exists($user_tag["tag"], $tags)){
									$tags[$user_tag["tag"]] = ['tag' => $user_tag["tag"]];
								}
							}
						}
						return ['comments' => $comments, 'tags' => array_values($tags)];
					}
				],
				'suggestTags' => [
					'type' => UserGeneratedContentSchema::get('UserGeneratedContentTagSuggestions'),
					'description' => _t('Return suggestions for tags based upon user input'),
					'args' => [
						[
							'name' => 'text',
							'type' => Type::string(),
							'description' => _t('User input')
						],
						[
							'name' => 'limit',
							'type' => Type::int(),
							'description' => _t('Maximum number of suggestions to return'),
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
						$u = null;
						if($args['jwt']) {
							try {
								$u = self::authenticate($args['jwt']);
							} catch(Exception $e) {
								$u = new ca_users();
							}
						}
						
						$text = $args['text'];
						
						$limit = (int)$args['limit'];
											
						$tags = ca_objects::suggestTags($text, ['limit' => $limit]);
						
						return ['tags' => array_values($tags)];
					}
				]
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
				'addComment' => [
					'type' => UserGeneratedContentSchema::get('UserGeneratedSaveResult'),
					'description' => _t('Save comments'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of record to generate grid for')
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table of record to generate grid for. (Ex. ca_entities)')
						],
						[
							'name' => 'comment',
							'type' => Type::string(),
							'description' => _t('Text of comment'),
							'defaultValue' => null
						],
						[
							'name' => 'name',
							'type' => Type::string(),
							'description' => _t('Submitting user\'s name'),
							'defaultValue' => null
						],
						[
							'name' => 'email',
							'type' => Type::string(),
							'description' => _t('Submitting user\'s email'),
							'defaultValue' => null
						],
						[
							'name' => 'location',
							'type' => Type::string(),
							'description' => _t('Submitting user\'s location'),
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
						$u = null;
						if($args['jwt']) {
							try {
								$u = self::authenticate($args['jwt']);
							} catch(Exception $e) {
								$u = new ca_users();
							}
						}
						global $g_ui_locale_id;
						
						$table = $args['table'];
						$id = $args['id'];
						
						$comment = $args['comment'];
						$name = $args['name'];
						$email = $args['email'];
						$location = $args['location'];
						
						$user_id = $u ? $u->getPrimaryKey() : null;
						$user_ip = $_SERVER['REMOTE_ADDR'];
						
						$dont_moderate = Configuration::load()->get("dont_moderate_comments");
						
						if (!($t_subject = Datamodel::getInstance($table, true, $id))) {
							throw new ServiceException(_t('Invalid table or id'));
						}
						if (!$t_subject->isReadable($u)) {
							throw new ServiceException(_t('Access denied'));
						}
						
						
						$email_sent = false;
						$message = null;
						$errors = [];
						$comments_added = 0;
						
		
						if(!$user_id && !$name && !$email){
							$errors[] = _t("Please enter your name and email");
						}
						if(!$comment){
							$errors[] = _t("Please enter your comment");
						}
						
						$t_item_comment = new ca_item_comments();
						
						if(!sizeof($errors)){ 
							if($user_id && $t_item_comment->load(["row_id" => $id, "user_id" => $user_id, 'comment' => $comment])){
								$errors[] = _t('Comment already submitted');
							} elseif($t_item_comment->load(["row_id" => $id, "ip_addr" => $user_ip, 'comment' => $comment])) {
								$errors[] = _t('Comment already submitted');
							}
						}
						if(sizeof($errors)){
							return [
								'error' => join('; ', $errors), 'message' => null, 
								'emailSent' => 0, 'commentsAdded' => 0
							];
						}
		
						if($t_subject->addComment(
							$comment, null, $user_id, null, $name, $email, 
							((in_array($table, ["ca_sets", "ca_set_items"])) || $dont_moderate) ? 1 : 0, 
							null, [], null, null, null, null, $location
						)) {
							$comments_added++;
						}
						
						$o_view = new View($this->request, array($this->request->getViewsDirectoryPath()));
						$o_view->setVar("comment", $comment);
						$o_view->setVar("tags", null);
						$o_view->setVar("name", $name);
						$o_view->setVar("email", $email);
						$o_view->setVar("item", $t_subject);
							
						# --- set/lightbox comments should be emailed to everyone with access to the set
						if(in_array($table, ["ca_sets", "ca_set_items"])){
							$set_users = $t_subject->getSetUsers();
							$emails = [];
							# --- gather array of users to send comment notification to
							foreach($set_users as $set_user){
								if($this->request->getUserID() != $set_user["user_id"]){
									$emails[$va_set_user["email"]] = $set_user["name"];
								}
							}
							if(sizeof($emails) > 0){
								# --- send email to other users with access to set
								# -- generate email subject line from template
								$subject_line = $o_view->render("mailTemplates/set_comment_notification_subject.tpl");
			
								# -- generate mail text from template - get both the text and the html versions
								$mail_message_text = $o_view->render("mailTemplates/set_comment_notification.tpl");
								$mail_message_html = $o_view->render("mailTemplates/set_comment_notification_html.tpl");
		
								if(caSendmail($emails, Configuration::load()->get("ca_admin_email"), $subject_line, $mail_message_text, $mail_message_html)) {
									$email_sent = true;
								}
							}
						}else{
							# --- check if email notification should be sent to admin - don't send for set/ligthbox comments
							if(!Configuration::load()->get("dont_email_notification_for_new_comments")){
								# --- send email confirmation
								# -- generate email subject line from template
								$subject_line = $o_view->render("mailTemplates/admin_comment_notification_subject.tpl");
			
								# -- generate mail text from template - get both the text and the html versions
								$mail_message_text = $o_view->render("mailTemplates/admin_comment_notification.tpl");
								$mail_message_html = $o_view->render("mailTemplates/admin_comment_notification_html.tpl");
		
								if(caSendmail(Configuration::load()->get("ca_admin_email"), Configuration::load()->get("ca_admin_email"), $subject_line, $mail_message_text, $mail_message_html)) {
									$email_sent = true;
								}
							}
						}
						if(($_table == "ca_sets") || Configuration::load()->get("dont_moderate_comments")){
							$message = _t("Thank you for contributing.").$dup_rank_message;
						}else{
							$message = _t("Thank you for contributing.  Your comments will be posted on this page after review by site staff.").$dup_rank_message;
						}
						
						return [
							'error' => join('; ', $errors), 'message' => $message,
							'emailSent' => $email_sent, 'commentsAdded' => $comments_added
						];
					}
				],
				
				'addTags' => [
					'type' => UserGeneratedContentSchema::get('UserGeneratedSaveResult'),
					'description' => _t('Save comments'),
					'args' => [
						[
							'name' => 'id',
							'type' => Type::int(),
							'description' => _t('ID of record to generate grid for')
						],
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table of record to generate grid for. (Ex. ca_entities)')
						],
						[
							'name' => 'tag',
							'type' => Type::string(),
							'description' => _t('Single tag, or commas-separated list of tags'),
							'defaultValue' => null
						],
						[
							'name' => 'tags',
							'type' => Type::listOf(Type::string()),
							'description' => _t('List of tags'),
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
						$u = null;
						if($args['jwt']) {
							try {
								$u = self::authenticate($args['jwt']);
							} catch(Exception $e) {
								$u = new ca_users();
							}
						}
						global $g_ui_locale_id;
						
						$table = $args['table'];
						$id = $args['id'];
						
						$tag = $args['tag'];
						$tags = $args['tags'];
						
						if(!is_array($tags) || !sizeof($tags)) {
							$tags = $tag ? preg_split("![ ]*[,;]+[ ]*!", $tag) : [];
						} else {
							// split any tags in the list and consolidate
							$tags = array_reduce($tags, function($c, $v) { 
								return array_unique(array_merge($c, preg_split("![ ]*[,;]+[ ]*!", $v)));
							}, []);
						}
						
						$user_id = $u ? $u->getPrimaryKey() : null;
						$user_ip = $_SERVER['REMOTE_ADDR'];
						
						$dont_moderate = Configuration::load()->get("dont_moderate_tags");
						
						if (!($t_subject = Datamodel::getInstance($table, true, $id))) {
							throw new ServiceException(_t('Invalid table or id'));
						}
						if (!$t_subject->isReadable($u)) {
							throw new ServiceException(_t('Access denied'));
						}
						
						
						$message = null;
						$errors = [];
						$tags_added = 0;
						
						if(!sizeof($tags)){
							return [
								'error' => _t("Please enter your tags"), 'message' => null, 
								'emailSent' => 0, 'tagsAdded' => 0
							];
						}
						
						foreach($tags as $t){
							if(!($t = trim($t))) { continue; }
							if($t_subject->addTag(
								$t, $user_id, $g_ui_locale_id, 
								((in_array($table, ["ca_sets", "ca_set_items"])) || $dont_moderate) ? 1 : 0, null
							)) {
								$tags_added++;
							} else {
								$errors[] = join('; ', $t_subject->getErrors());
							}
						}
						
						$message = _t("Thank you for your contribution.").$dup_rank_message;
						return [
							'error' => join('; ', $errors), 'message' => $message,
							'emailSent' => $email_sent, 'commentsAdded' => $comments_added, 'tagsAdded' => $tags_added
						];
					}
				]
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
}
