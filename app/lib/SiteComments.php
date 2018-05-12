<?php
/* ----------------------------------------------------------------------
 * app/lib/ca/SiteComments.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
 	require_once(__CA_MODELS_DIR__.'/ca_item_comments.php');
 
	class SiteComments extends BaseObject {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct() {
			$this->opo_config = Configuration::load();
		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Adds a general (site-wide) comment. Returns true
		 * if comment was successfully added, false if an error occurred in which case the errors will be available
		 * via the standard error methods (getErrors() and friends.)
		 *
		 * Most of the parameters are optional with the exception of $ps_comment - the text of the comment. Note that 
		 * comment text is monolingual; if you want to do multilingual comments (which aren't really comments then, are they?) then
		 * you should add multiple comments.
		 *
		 * The parameters are:
		 *
		 * @param $ps_comment [string] Text of the comment (mandatory)
		 * @param $pn_rating [integer] A number between 1 and 5 indicating the user's rating of the row; larger is better (optional - default is null)
		 * @param $pn_user_id [integer] A valid ca_users.user_id indicating the user who posted the comment; is null for comments from non-logged-in users (optional - default is null)
		 * @param $pn_locale_id [integer] A valid ca_locales.locale_id indicating the language of the comment. If omitted or left null then the value in the global $g_ui_locale_id variable is used. If $g_ui_locale_id is not set and $pn_locale_id is not set then an error will occur (optional - default is to use $g_ui_locale_id)
		 * @param $ps_name [string] Name of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
		 * @param $ps_email [string] E-mail address of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
		 * @param $pn_access [integer] Determines public visibility of comments; if set to 0 then comment is not visible to public; if set to 1 comment is visible (optional - default is 0)
		 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the comment; if omitted or set to null then moderation status will not be set unless app.conf setting dont_moderate_comments = 1 (optional - default is null)
		 */
		public function addComment($ps_comment, $pn_rating=null, $pn_user_id=null, $pn_locale_id=null, $ps_name=null, $ps_email=null, $pn_access=0, $pn_moderator=null) {
			global $g_ui_locale_id;
			if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
			
			$t_comment = new ca_item_comments();
			$t_comment->setMode(ACCESS_WRITE);
			$t_comment->set('table_num', 255);
			$t_comment->set('row_id', 0);
			$t_comment->set('user_id', $pn_user_id);
			$t_comment->set('locale_id', $pn_locale_id);
			$t_comment->set('comment', $ps_comment);
			$t_comment->set('rating', $pn_rating);
			$t_comment->set('email', $ps_email);
			$t_comment->set('name', $ps_name);
			$t_comment->set('access', $pn_access);
			
			if (!is_null($pn_moderator)) {
				$t_comment->set('moderated_by_user_id', $pn_moderator);
				$t_comment->set('moderated_on', 'now');
			}elseif($this->opo_config->get("dont_moderate_comments")){
				$t_comment->set('moderated_on', 'now');
			}
			
			$t_comment->insert();
			
			if ($t_comment->numErrors()) {
				$this->errors = $t_comment->errors;
				return false;
			}
			return true;
		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Edits an existing comment as specified by $pn_comment_id. 
		 * Note that all parameters are mandatory in the sense that the value passed (or the default value if not passed)
		 * will be written into the comment. For example, if you don't bother passing $ps_name then it will be set to null, even
		 * if there's an existing name value in the field. The only exception is $pn_locale_id; if set to null or omitted then 
		 * editComment() will attempt to use the locale value in the global $g_ui_locale_id variable. If this is not set then
		 * an error will be posted and editComment() will return false.
		 *
		 * The parameters are:
		 *
		 * @param $pn_comment_id [integer] a valid comment_id to be edited; must be related to the currently loaded row (mandatory)
		 * @param $ps_comment [string] the text of the comment (mandatory)
		 * @param $pn_rating [integer] a number between 1 and 5 indicating the user's rating of the row; higher is better (optional - default is null)
		 * @param $pn_user_id [integer] A valid ca_users.user_id indicating the user who posted the comment; is null for comments from non-logged-in users (optional - default is null)
		 * @param $pn_locale_id [integer] A valid ca_locales.locale_id indicating the language of the comment. If omitted or left null then the value in the global $g_ui_locale_id variable is used. If $g_ui_locale_id is not set and $pn_locale_id is not set then an error will occur (optional - default is to use $g_ui_locale_id)
		 * @param $ps_name [string] Name of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
		 * @param $ps_email [string] E-mail address of user posting comment. Only needs to be set if $pn_user_id is *not* set; used to identify comments posted by non-logged-in users (optional - default is null)
		 * @param $pn_access [integer] Determines public visibility of comments; if set to 0 then comment is not visible to public; if set to 1 comment is visible (optional - default is 0)
		 * @param $pn_moderator [integer] A valid ca_users.user_id value indicating who moderated the comment; if omitted or set to null then moderation status will not be set (optional - default is null)
		 */
		public function editComment($pn_comment_id, $ps_comment, $pn_rating=null, $pn_user_id=null, $pn_locale_id=null, $ps_name=null, $ps_email=null, $pn_access=null, $pn_moderator=null) {
			global $g_ui_locale_id;
			if (!$pn_locale_id) { $pn_locale_id = $g_ui_locale_id; }
			
			$t_comment = new ca_item_comments($pn_comment_id);
			if (!$t_comment->getPrimaryKey()) {
				$this->postError(2800, _t('Comment id is invalid'), 'BaseModel->editComment()');
				return false;
			}
			
			$t_comment->setMode(ACCESS_WRITE);
			
			$t_comment->set('comment', $ps_comment);
			$t_comment->set('rating', $pn_rating);
			$t_comment->set('user_id', $pn_user_id);
			$t_comment->set('name', $ps_name);
			$t_comment->set('email', $ps_email);
			
			if (!is_null($pn_moderator)) {
				$t_comment->set('moderated_by_user_id', $pn_moderator);
				$t_comment->set('moderated_on', 'now');
			}
			
			if (!is_null($pn_locale_id)) { $t_comment->set('locale_id', $pn_locale_id); }
			
			$t_comment->update();
			if ($t_comment->numErrors()) {
				$this->errors = $t_comment->errors;
				return false;
			}
			return true;
		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Permanently deletes the comment specified by $pn_comment_id. 
		 * If $pn_user_id is specified then only comments created by the specified user will be deleted; if the comment being
		 * deleted is not created by the user then false is returned and an error posted.
		 *
		 * @param $pn_comment_id [integer] a valid comment_id to be removed; must be related to the currently loaded row (mandatory)
		 * @param $pn_user_id [integer] a valid ca_users.user_id value; if specified then only comments by the specified user will be deleted (optional - default is null)
		 */
		public function removeComment($pn_comment_id, $pn_user_id=null) {
			
			$t_comment = new ca_item_comments($pn_comment_id);
			if (!$t_comment->getPrimaryKey()) {
				$this->postError(2800, _t('Comment id is invalid'), 'BaseModel->removeComment()');
				return false;
			}
			
			if ($pn_user_id) {
				if ($t_comment->get('user_id') != $pn_user_id) {
					$this->postError(2820, _t('Comment was not created by specified user'), 'BaseModel->removeComment()');
					return false;
				}
			}
			
			$t_comment->setMode(ACCESS_WRITE);
			$t_comment->delete();
			
			if ($t_comment->numErrors()) {
				$this->errors = $t_comment->errors;
				return false;
			}
			return true;
		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Removes all site-wide comments. 
		 * If the optional $ps_user_id parameter is passed then only comments created by the specified user will be removed.
		 *
		 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only comments by the specified user will be removed. (optional - default is null)
		 */
		public function removeAllComments($pn_user_id=null) {
			$va_comments = $this->getComments($pn_user_id);
			
			foreach($va_comments as $va_comment) {
				if (!$this->removeComment($va_comment['comment_id'], $pn_user_id)) {
					return false;
				}
			}
			return true;
		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Returns all site-wide comments.
		 * If the optional $ps_user_id parameter is passed then only comments created by the specified user will be returned.
		 * If the optional $pb_moderation_status parameter is passed then only comments matching the criteria will be returned:
		 *		Passing $pb_moderation_status = TRUE will cause only moderated comments to be returned
		 *		Passing $pb_moderation_status = FALSE will cause only unmoderated comments to be returned
		 *		If you want both moderated and unmoderated comments to be returned then omit the parameter or pass a null value
		 *
		 * @param $pn_user_id [integer] A valid ca_users.user_id value. If specified, only comments by the specified user will be returned. (optional - default is null)
		 * @param $pn_moderation_status [boolean] To return only unmoderated comments set to FALSE; to return only moderated comments set to TRUE; to return all comments set to null or omit
		 */
		public function getComments($pn_user_id=null, $pb_moderation_status=null) {
			$o_db = new Db();
			
			$vs_user_sql = ($pn_user_id) ? ' AND (user_id = '.intval($pn_user_id).')' : '';
			
			$vs_moderation_sql = '';
			if (!is_null($pb_moderation_status)) {
				$vs_moderation_sql = ($pb_moderation_status) ? ' AND (ca_item_comments.moderated_on IS NOT NULL)' : ' AND (ca_item_comments.moderated_on IS NULL)';
			}
			
			$qr_comments = $o_db->query("
				SELECT *
				FROM ca_item_comments
				WHERE
					(table_num = 255) {$vs_user_sql} {$vs_moderation_sql}
			");
			
			return $qr_comments->getAllRows();
		}
		# -------------------------------------------------------
	}
?>
