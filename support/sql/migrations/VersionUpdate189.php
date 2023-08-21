<?php

/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate186.php :
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
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__ . '/BaseVersionUpdater.php');


class VersionUpdate186 extends BaseVersionUpdater
{
	# -------------------------------------------------------
	protected $opn_schema_update_to_version_number = 186;
	protected $messages = [];
	# -------------------------------------------------------

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function applyDatabaseUpdate($pa_options = null)
	{

		$db	 = new Db();
		$this->runMigrations($db);
		foreach ($this->messages as $migration => $message) {
			foreach ($message as $status => $items) {
				foreach ($items as $table => $details) {
					$report = "Status: $status; Table: $table ; Migration: $migration";
					if (is_array($details)) {
						foreach ($details as $field => $detail) {
							$report = "$report - $field - $detail";
						}
					} else {
						$report = "$report - $details";
					}
					$return[] = $report;
				}
			}
		}
		return parent::applyDatabaseUpdate($pa_options) + $return;
	}
	# -------------------------------------------------------

	/**
	 *
	 * @return string HTML to display after update
	 */
	public function getPostupdateMessage()
	{
		return _t("Sucessfully synchronized database schema");
	}
	# -------------------------------------------------------


	/**
	 * @param Db $db
	 * @param	$table_name
	 * @param	$index_name
	 *
	 * @return mixed|null
	 */
	public function getIndexCount(Db $db, $table_name, $index_name)
	{
		$checkQuery = <<<SQL
SELECT COUNT(1) index_count
   FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema=DATABASE() 
	 AND table_name=?
	 AND index_name = ?
SQL;
		$stmt	   = $db->query($checkQuery, [ $table_name, $index_name ]);
		return $stmt->get('index_count');
	}

	private function runMigrations(Db $db)
	{
		$this->addForeignKeys($db, $this->getForeignKeys());
		$this->modifyColumns($db, $this->getModifyColumns());
		$this->addUniqueConstraint($db, $this->getUniqueConstraints());
		$this->dropIndexes($db, $this->getDropIndexes());
		$this->dropForeignKeys($db, $this->getDropForeignKeys());
		$this->createIndexes($db, $this->getCreateIndexSimple());
		$this->removeDefaultValues($db);
	}

	private function addForeignKeys(Db $db, array $foreign_keys)
	{
		foreach ($foreign_keys as $info) {
			$sql = "ALTER TABLE $info[0] ADD CONSTRAINT $info[1] FOREIGN KEY $info[2] REFERENCES $info[3] $info[4]";
			if ($this->getIndexCount($db, $info[0], $info[1]) === 0) {
				$db->query($sql);
				$this->messages[ __METHOD__ ]['added'][ $info[0] ] = $info[1];
			} else {
				$this->messages[ __METHOD__ ]['skipped'][ $info[0] ] = $info[1];
			}
		}
	}

	private function modifyColumns(Db $db, array $modify_columns)
	{
		foreach ($modify_columns as $info) {
			$db->query("ALTER TABLE $info[0] MODIFY $info[1] $info[2]");
			$this->messages[__METHOD__]['added'][ $info[0] ][ $info[1] ] = $info[2];
		}
	}

	private function addUniqueConstraint(Db $db, array $unique_constraint_1)
	{
		foreach ($unique_constraint_1 as $info) {
			$sql = "ALTER TABLE $info[0] ADD CONSTRAINT $info[0] UNIQUE ($info[2])";
			if ($this->getIndexCount($db, $info[0], $info[1]) === 0) {
				$db->query($sql);
				$this->messages[ __METHOD__ ]['added'][ $info[0] ] = $info[1];
			} else {
				$this->messages[ __METHOD__ ]['skipped'][ $info[0] ] = $info[1];
			}
		}
	}

	private function dropIndexes(Db $db, $indexes)
	{
		foreach ($indexes as $info) {
			if ($this->getIndexCount($db, $info[1], $info[0]) > 0) {
				$db->query("ALTER TABLE $info[1] DROP INDEX $info[0]");
				$this->messages[ __METHOD__ ]['added'][ $info[1] ] = $info[0];
			} else {
				$this->messages[ __METHOD__ ]['skipped'][ $info[1] ] = $info[0];
			}
		}
	}

	private function createIndexes(Db $db, $indexes)
	{
		foreach ($indexes as $info) {
			$sql = "CREATE INDEX $info[0] ON $info[1] ($info[2])";
			if ($this->getIndexCount($db, $info[1], $info[0]) === 0) {
				$db->query($sql);
				$this->messages[ __METHOD__ ]['added'][ $info[1] ] = $info[0];
			} else {
				$this->messages[ __METHOD__ ]['skipped'][ $info[1] ] = $info[0];
			}
		}
	}

	private function dropForeignKeys(Db $db, $drop_foreign_keys)
	{
		foreach ($drop_foreign_keys as $info) {
			if ($this->getIndexCount($db, $info[1], $info[0]) > 0) {
				$db->query("ALTER TABLE $info[0] DROP FOREIGN KEY $info[1]");
				$this->messages[ __METHOD__ ]['added'][ $info[1] ] = $info[0];
			} else {
				$this->messages[ __METHOD__ ]['skipped'][ $info[1] ] = $info[0];
			}
		}
	}

	/**
	 * @return array[]
	 */
	public function getForeignKeys(): array
	{
		return [
			[
				'ca_attribute_value_multifiles',
				'fk_ca_attribute_value_multifiles_value_id',
				'value_id',
				'ca_attribute_values',
				'value_id',
			],
			[
				'ca_bookmark_folders',
				'fk_ca_bookmark_folders_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_bookmarks',
				'fk_ca_bookmarks_folder_id',
				'folder_id',
				'ca_bookmark_folders',
				'folder_id',
			],
			[
				'ca_bundle_display_labels',
				'fk_ca_bundle_display_labels_display_id',
				'display_id',
				'ca_bundle_displays',
				'display_id',
			],
			[
				'ca_bundle_display_labels',
				'fk_ca_bundle_display_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_bundle_display_placements',
				'fk_ca_bundle_display_placements_display_id',
				'display_id',
				'ca_bundle_displays',
				'display_id',
			],
			[
				'ca_bundle_displays',
				'fk_ca_bundle_displays_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_bundle_displays_x_user_groups',
				'fk_ca_bundle_displays_x_ug_display_id',
				'display_id',
				'ca_bundle_displays',
				'display_id',
			],
			[
				'ca_bundle_displays_x_user_groups',
				'fk_ca_bundle_displays_x_ug_group_id',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_bundle_displays_x_users',
				'fk_ca_bundle_displays_x_u_display_id',
				'display_id',
				'ca_bundle_displays',
				'display_id',
			],
			[
				'ca_bundle_displays_x_users',
				'fk_ca_bundle_displays_x_u_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_collections',
				'fk_ca_collections_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_collections',
				'fk_ca_collections_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_collections',
				'fk_ca_collections_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_collections',
				'fk_ca_collections_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_editor_ui_bundle_placements',
				'fk_ca_editor_ui_bundle_placements_screen_id',
				'screen_id',
				'ca_editor_ui_screens',
				'screen_id',
			],
			[
				'ca_editor_ui_labels',
				'fk_ca_editor_ui_labels_ca_locales',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_editor_ui_labels',
				'fk_ca_editor_ui_labels_ui_id',
				'ui_id',
				'ca_editor_uis',
				'ui_id',
			],
			[
				'ca_editor_ui_screen_labels',
				'fk_ca_editor_ui_screen_labels_ca_locales',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_editor_ui_screen_labels',
				'fk_ca_editor_ui_screen_labels_screen_id',
				'screen_id',
				'ca_editor_ui_screens',
				'screen_id',
			],
			[
				'ca_editor_ui_screens',
				'fk_ca_editor_ui_screens_ui_id',
				'ui_id',
				'ca_editor_uis',
				'ui_id',
			],
			[
				'ca_editor_ui_screens_x_roles',
				'fk_ca_editor_ui_screens_x_r_role_id',
				'role_id',
				'ca_user_roles',
				'role_id',
			],
			[
				'ca_editor_ui_screens_x_roles',
				'fk_ca_editor_ui_screens_x_r_screen_id',
				'screen_id',
				'ca_editor_ui_screens',
				'screen_id',
			],
			[
				'ca_editor_ui_screens_x_user_groups',
				'fk_ca_editor_ui_screens_x_ug_group_id',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_editor_ui_screens_x_user_groups',
				'fk_ca_editor_ui_screens_x_ug_screen_id',
				'screen_id',
				'ca_editor_ui_screens',
				'screen_id',
			],
			[
				'ca_editor_ui_screens_x_users',
				'fk_ca_editor_ui_screens_x_u_screen_id',
				'screen_id',
				'ca_editor_ui_screens',
				'screen_id',
			],
			[
				'ca_editor_ui_screens_x_users',
				'fk_ca_editor_ui_screens_x_u_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_editor_uis',
				'fk_ca_editor_uis_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_editor_uis_x_roles',
				'fk_ca_editor_uis_x_roles_ca_user_roles',
				'role_id',
				'ca_user_roles',
				'role_id',
			],
			[
				'ca_editor_uis_x_roles',
				'fk_ca_editor_uis_x_roles_ui_id',
				'ui_id',
				'ca_editor_uis',
				'ui_id',
			],
			[
				'ca_editor_uis_x_user_groups',
				'fk_ca_editor_uis_x_user_groups_ca_user_groups',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_editor_uis_x_user_groups',
				'fk_ca_editor_uis_x_user_groups_ui_id',
				'ui_id',
				'ca_editor_uis',
				'ui_id',
			],
			[
				'ca_editor_uis_x_users',
				'fk_ca_editor_uis_x_users_ca_users',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_editor_uis_x_users',
				'fk_ca_editor_uis_x_users_ui_id',
				'ui_id',
				'ca_editor_uis',
				'ui_id',
			],
			[
				'ca_entities',
				'fk_ca_entities_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_entities',
				'fk_ca_entities_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_entities',
				'fk_ca_entities_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_entities',
				'fk_ca_entities_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_item_comments',
				'fk_ca_item_comments_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_item_comments',
				'fk_ca_item_comments_moderated_by_user_id',
				'moderated_by_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_item_comments',
				'fk_ca_item_comments_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_item_tags',
				'fk_ca_item_tags_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_items_x_tags',
				'fk_ca_items_x_tags_moderated_by_user_id',
				'moderated_by_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_items_x_tags',
				'fk_ca_items_x_tags_tag_id',
				'tag_id',
				'ca_item_tags',
				'tag_id',
			],
			[
				'ca_items_x_tags',
				'fk_ca_items_x_tags_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_loans',
				'fk_ca_loans_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_loans',
				'fk_ca_loans_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_loans',
				'fk_ca_loans_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_loans',
				'fk_ca_loans_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_media_upload_session_files',
				'fk_ca_media_upload_session_files_session_id',
				'session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_media_upload_sessions',
				'fk_ca_media_upload_sessions_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_metadata_alert_rule_labels',
				'fk_ca_metadata_alert_rule_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_metadata_alert_rule_labels',
				'fk_ca_metadata_alert_rule_labels_rule_id',
				'rule_id',
				'ca_metadata_alert_rules',
				'rule_id',
			],
			[
				'ca_metadata_alert_rules',
				'fk_ca_metadata_alert_rules_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_metadata_alert_rules_x_user_groups',
				'fk_ca_metadata_alert_rules_x_ug_group_id',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_metadata_alert_rules_x_user_groups',
				'fk_ca_metadata_alert_rules_x_ug_rule_id',
				'rule_id',
				'ca_metadata_alert_rules',
				'rule_id',
			],
			[
				'ca_metadata_alert_rules_x_users',
				'fk_ca_metadata_alert_rules_x_u_rule_id',
				'rule_id',
				'ca_metadata_alert_rules',
				'rule_id',
			],
			[
				'ca_metadata_alert_rules_x_users',
				'fk_ca_metadata_alert_rules_x_u_user_id_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_metadata_dictionary_entry_labels',
				'fk_ca_md_entry_labels_entry_id',
				'entry_id',
				'ca_metadata_dictionary_entries',
				'entry_id',
			],
			[
				'ca_metadata_dictionary_entry_labels',
				'fk_ca_md_entry_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_movements',
				'fk_ca_movements_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_movements',
				'fk_ca_movements_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_movements',
				'fk_ca_movements_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_notification_subjects',
				'fk_ca_notification_subjects_notification_id',
				'notification_id',
				'ca_notifications',
				'notification_id',
			],
			[
				'ca_object_lots',
				'fk_ca_object_lots_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_object_lots',
				'fk_ca_object_lots_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_object_lots',
				'fk_ca_object_lots_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_object_lots',
				'fk_ca_object_lots_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_object_representation_captions',
				'fk_ca_object_rep_captions_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_object_representation_captions',
				'fk_ca_object_representation_cap_representation_id',
				'representation_id',
				'ca_object_representations',
				'representation_id',
			],
			[
				'ca_object_representation_multifiles',
				'fk_ca_object_representation_mf_representation_id',
				'representation_id',
				'ca_object_representations',
				'representation_id',
			],
			[
				'ca_object_representation_sidecars',
				'fk_ca_object_representation_sc_representation_id',
				'representation_id',
				'ca_object_representations',
				'representation_id',
			],
			[
				'ca_object_representations',
				'fk_ca_object_reps_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_object_representations',
				'fk_ca_object_reps_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_object_representations',
				'fk_ca_object_reps_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_object_representations',
				'fk_ca_object_reps_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_objects',
				'fk_ca_objects_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_objects',
				'fk_ca_objects_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_objects',
				'fk_ca_objects_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_objects',
				'fk_ca_objects_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_occurrences',
				'fk_ca_occurrences_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_occurrences',
				'fk_ca_occurrences_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_occurrences',
				'fk_ca_occurrences_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_occurrences',
				'fk_ca_occurrences_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_places',
				'fk_ca_places_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_places',
				'fk_ca_places_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_places',
				'fk_ca_places_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_places',
				'fk_ca_places_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_representation_transcriptions',
				'fk_ca_representation_transcriptions_representation_id',
				'representation_id',
				'ca_object_representations',
				'representation_id',
			],
			[
				'ca_representation_transcriptions',
				'fk_ca_representation_transcriptions_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_search_form_labels',
				'fk_ca_search_form_labels_form_id',
				'form_id',
				'ca_search_forms',
				'form_id',
			],
			[
				'ca_search_form_labels',
				'fk_ca_search_form_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_search_form_placements',
				'fk_ca_search_form_placements_form_id',
				'form_id',
				'ca_search_forms',
				'form_id',
			],
			[
				'ca_search_forms',
				'fk_ca_search_forms_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_search_forms_x_user_groups',
				'fk_ca_search_forms_x_ug_form_id',
				'form_id',
				'ca_search_forms',
				'form_id',
			],
			[
				'ca_search_forms_x_user_groups',
				'fk_ca_search_forms_x_ug_group_id',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_search_forms_x_users',
				'fk_ca_search_forms_x_u_form_id',
				'form_id',
				'ca_search_forms',
				'form_id',
			],
			[
				'ca_search_forms_x_users',
				'fk_ca_search_forms_x_u_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_search_log',
				'fk_ca_search_log_form_id',
				'form_id',
				'ca_search_forms',
				'form_id',
			],
			[
				'ca_search_log',
				'fk_ca_search_log_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_set_item_labels',
				'fk_ca_set_item_labels_item_id',
				'item_id',
				'ca_set_items',
				'item_id',
			],
			[
				'ca_set_item_labels',
				'fk_ca_set_item_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_set_items',
				'fk_ca_set_items_set_id',
				'set_id',
				'ca_sets',
				'set_id',
			],
			[
				'ca_set_labels',
				'fk_ca_set_labels_locale_id',
				'locale_id',
				'ca_locales',
				'locale_id',
			],
			[
				'ca_set_labels',
				'fk_ca_set_labels_set_id',
				'set_id',
				'ca_sets',
				'set_id',
			],
			[
				'ca_sets',
				'fk_ca_sets_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_sets_x_user_groups',
				'fk_ca_sets_x_ug_group_id',
				'group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_sets_x_user_groups',
				'fk_ca_sets_x_ug_set_id',
				'set_id',
				'ca_sets',
				'set_id',
			],
			[
				'ca_sets_x_users',
				'fk_ca_sets_x_users_set_id',
				'set_id',
				'ca_sets',
				'set_id',
			],
			[
				'ca_sets_x_users',
				'fk_ca_sets_x_users_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_site_page_media',
				'fk_ca_site_page_media_page_id',
				'page_id',
				'ca_site_pages',
				'page_id',
			],
			[
				'ca_site_pages',
				'fk_ca_site_pages_template_id',
				'template_id',
				'ca_site_templates',
				'template_id',
			],
			[
				'ca_storage_locations',
				'fk_ca_storage_locations_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
			[
				'ca_storage_locations',
				'fk_ca_storage_locations_submission_session_id',
				'submission_session_id',
				'ca_media_upload_sessions',
				'session_id',
			],
			[
				'ca_storage_locations',
				'fk_ca_storage_locations_submission_status_id',
				'submission_status_id',
				'ca_list_items',
				'item_id',
			],
			[
				'ca_storage_locations',
				'fk_ca_storage_locations_submission_user_id',
				'submission_user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_user_groups',
				'fk_ca_user_groups_user_id',
				'user_id',
				'ca_users',
				'user_id',
			],
			[
				'ca_users',
				'fk_ca_users_entity_id',
				'entity_id',
				'ca_entities',
				'entity_id',
			],
			[
				'ca_movements',
				'fk_ca_movements_submission_group_id',
				'submission_group_id',
				'ca_user_groups',
				'group_id',
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getModifyColumns(): array
	{
		return [
			[
				'ca_attribute_values',
				'value_sortable',
				'VARCHAR(100) NULL ',
			],
			[
				'ca_collections',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_editor_ui_labels',
				'locale_id',
				'SMALLINT UNSIGNED NOT NULL',
			],
			[
				'ca_editor_ui_screen_labels',
				'locale_id',
				'SMALLINT UNSIGNED NOT NULL',
			],
			[
				'ca_editor_ui_screens_x_roles',
				'role_id',
				'SMALLINT UNSIGNED NOT NULL',
			],
			[
				'ca_editor_uis_x_roles',
				'role_id',
				'SMALLINT UNSIGNED NOT NULL',
			],
			[
				'ca_entities',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_entity_labels',
				'checked',
				'TINYINT UNSIGNED DEFAULT \'0\' NOT NULL',
			],
			[
				'ca_list_items',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_loans',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_metadata_elements',
				'deleted',
				'TINYINT UNSIGNED DEFAULT \'0\' NOT NULL',
			],
			[
				'ca_movements',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_notification_subjects',
				'read_on',
				'INT unsigned NULL',
			],
			[
				'ca_object_representations',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_objects',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_occurrences',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_places',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_sets_x_users',
				'pending_access',
				'TINYINT unsigned NULL',
			],
			[
				'ca_site_page_media',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_sql_search_word_index',
				'index_id',
				'BIGINT unsigned AUTO_INCREMENT',
			],
			[
				'ca_storage_locations',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_tour_stops',
				'idno_sort_num',
				'BIGINT DEFAULT 0 NOT NULL',
			],
			[
				'ca_user_groups',
				'for_public_use',
				'TINYINT unsigned DEFAULT \'0\' NOT NULL',
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getUniqueConstraints(): array
	{
		return [
			[
				'ca_history_tracking_current_values',
				'u_all',
				'row_id, table_num, policy, type_id, is_future',
			],
			[
				'ca_object_representation_labels',
				'u_all',
				'representation_id, name(255), type_id, locale_id',
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getDropIndexes(): array
	{
		return [
			[
				'idno',
				'ca_loans',
			],
			[
				'idno_sort',
				'ca_loans',
			],
			[
				'i_name',
				'ca_metadata_dictionary_entries',
			],
			[
				'idno',
				'ca_movements',
			],
			[
				'idno_sort',
				'ca_movements',
			],
			[
				'i_idno_stub_sort_num',
				'ca_object_lots',
			],
			[
				'i_idno_sort_num',
				'ca_site_page_media',
			],
			[
				'i_locale_id',
				'ca_site_pages',
			],
			[
				'idno',
				'ca_storage_locations',
			],
			[
				'idno_sort',
				'ca_storage_locations',
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getCreateIndexSimple(): array
	{
		return [
			[
				'i_idno',
				'ca_loans',
				'idno',
			],
			[
				'i_idno_sort',
				'ca_loans',
				'idno_sort',
			],
			[
				'i_bundle_name',
				'ca_metadata_dictionary_entries',
				'bundle_name',
			],
			[
				'i_idno',
				'ca_movements',
				'idno',
			],
			[
				'i_idno_sort',
				'ca_movements',
				'idno_sort',
			],
			[
				'i_locale_id',
				'ca_object_representation_labels',
				'locale_id',
			],
			[
				'i_name',
				'ca_object_representation_labels',
				'name(128)',
			],
			[
				'i_name_sort',
				'ca_object_representation_labels',
				'name_sort',
			],
			[
				'i_representation_id',
				'ca_object_representation_labels',
				'representation_id',
			],
			[
				'i_type_id',
				'ca_object_representation_labels',
				'type_id',
			],
			[
				'i_idno',
				'ca_occurrences',
				'idno',
			],
			[
				'i_idno_sort',
				'ca_occurrences',
				'idno_sort',
			],
			[
				'idno_sort_num',
				'ca_site_page_media',
				'idno_sort_num',
			],
			[
				'locale_id',
				'ca_site_pages',
				'locale_id',
			],
			[
				'i_idno',
				'ca_storage_locations',
				'idno',
			],
			[
				'i_idno_sort',
				'ca_storage_locations',
				'idno_sort',
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function getDropForeignKeys(): array
	{
		return [
			[
				'ca_object_representation_captions',
				'fk_ca_object_rep_captiopns_locale_id',
			],
			[
				'ca_users',
				'fk_ca_entities_entity_id',
			],
		];
	}

	/**
	 * @return string[]
	 */
	public function getMb4Tables(): array
	{
		return [
			'ca_acl',
			'ca_application_vars',
			'ca_attribute_value_multifiles',
			'ca_attribute_values',
			'ca_attributes',
			'ca_batch_log',
			'ca_batch_log_items',
			'ca_bookmark_folders',
			'ca_bookmarks',
			'ca_bundle_display_labels',
			'ca_bundle_display_placements',
			'ca_bundle_display_type_restrictions',
			'ca_bundle_displays',
			'ca_bundle_displays_x_user_groups',
			'ca_bundle_displays_x_users',
			'ca_change_log',
			'ca_change_log_snapshots',
			'ca_change_log_subjects',
			'ca_collection_labels',
			'ca_collections',
			'ca_collections_x_collections',
			'ca_collections_x_storage_locations',
			'ca_collections_x_vocabulary_terms',
			'ca_data_exporter_items',
			'ca_data_exporter_labels',
			'ca_data_exporters',
			'ca_data_import_event_log',
			'ca_data_import_events',
			'ca_data_import_items',
			'ca_data_importer_groups',
			'ca_data_importer_items',
			'ca_data_importer_labels',
			'ca_data_importer_log',
			'ca_data_importer_log_items',
			'ca_data_importers',
			'ca_download_log',
			'ca_editor_ui_bundle_placements',
			'ca_editor_ui_labels',
			'ca_editor_ui_screen_labels',
			'ca_editor_ui_screen_type_restrictions',
			'ca_editor_ui_screens',
			'ca_editor_ui_screens_x_roles',
			'ca_editor_ui_screens_x_user_groups',
			'ca_editor_ui_screens_x_users',
			'ca_editor_ui_type_restrictions',
			'ca_editor_uis',
			'ca_editor_uis_x_roles',
			'ca_editor_uis_x_user_groups',
			'ca_editor_uis_x_users',
			'ca_entities',
			'ca_entities_x_collections',
			'ca_entities_x_entities',
			'ca_entities_x_occurrences',
			'ca_entities_x_places',
			'ca_entities_x_storage_locations',
			'ca_entities_x_vocabulary_terms',
			'ca_entity_labels',
			'ca_eventlog',
			'ca_groups_x_roles',
			'ca_guids',
			'ca_history_tracking_current_values',
			'ca_ip_bans',
			'ca_ips',
			'ca_item_comments',
			'ca_item_tags',
			'ca_items_x_tags',
			'ca_list_item_labels',
			'ca_list_items',
			'ca_list_items_x_list_items',
			'ca_list_labels',
			'ca_lists',
			'ca_loan_labels',
			'ca_loans',
			'ca_loans_x_collections',
			'ca_loans_x_entities',
			'ca_loans_x_loans',
			'ca_loans_x_movements',
			'ca_loans_x_object_lots',
			'ca_loans_x_object_representations',
			'ca_loans_x_objects',
			'ca_loans_x_occurrences',
			'ca_loans_x_places',
			'ca_loans_x_storage_locations',
			'ca_loans_x_vocabulary_terms',
			'ca_locales',
			'ca_media_content_locations',
			'ca_media_replication_status_check',
			'ca_media_upload_session_files',
			'ca_media_upload_sessions',
			'ca_metadata_alert_rule_labels',
			'ca_metadata_alert_rule_type_restrictions',
			'ca_metadata_alert_rules',
			'ca_metadata_alert_rules_x_user_groups',
			'ca_metadata_alert_rules_x_users',
			'ca_metadata_alert_triggers',
			'ca_metadata_dictionary_entries',
			'ca_metadata_dictionary_entry_labels',
			'ca_metadata_dictionary_rule_violations',
			'ca_metadata_dictionary_rules',
			'ca_metadata_element_labels',
			'ca_metadata_elements',
			'ca_metadata_type_restrictions',
			'ca_movement_labels',
			'ca_movements',
			'ca_movements_x_collections',
			'ca_movements_x_entities',
			'ca_movements_x_movements',
			'ca_movements_x_object_lots',
			'ca_movements_x_object_representations',
			'ca_movements_x_objects',
			'ca_movements_x_occurrences',
			'ca_movements_x_places',
			'ca_movements_x_storage_locations',
			'ca_movements_x_vocabulary_terms',
			'ca_multipart_idno_sequences',
			'ca_notification_subjects',
			'ca_object_checkouts',
			'ca_object_labels',
			'ca_object_lot_labels',
			'ca_object_lots',
			'ca_object_lots_x_collections',
			'ca_object_lots_x_entities',
			'ca_object_lots_x_object_lots',
			'ca_object_lots_x_object_representations',
			'ca_object_lots_x_occurrences',
			'ca_object_lots_x_places',
			'ca_object_lots_x_storage_locations',
			'ca_object_lots_x_vocabulary_terms',
			'ca_object_representation_captions',
			'ca_object_representation_labels',
			'ca_object_representation_multifiles',
			'ca_object_representation_sidecars',
			'ca_object_representations',
			'ca_object_representations_x_collections',
			'ca_object_representations_x_entities',
			'ca_object_representations_x_object_representations',
			'ca_object_representations_x_occurrences',
			'ca_object_representations_x_places',
			'ca_object_representations_x_storage_locations',
			'ca_object_representations_x_vocabulary_terms',
			'ca_objects',
			'ca_objects_x_collections',
			'ca_objects_x_entities',
			'ca_objects_x_object_representations',
			'ca_objects_x_objects',
			'ca_objects_x_occurrences',
			'ca_objects_x_places',
			'ca_objects_x_storage_locations',
			'ca_objects_x_vocabulary_terms',
			'ca_occurrence_labels',
			'ca_occurrences',
			'ca_notifications',
			'ca_occurrences_x_collections',
			'ca_occurrences_x_occurrences',
			'ca_occurrences_x_storage_locations',
			'ca_occurrences_x_vocabulary_terms',
			'ca_persistent_cache',
			'ca_place_labels',
			'ca_places',
			'ca_places_x_collections',
			'ca_places_x_occurrences',
			'ca_places_x_places',
			'ca_places_x_storage_locations',
			'ca_places_x_vocabulary_terms',
			'ca_relationship_relationships',
			'ca_relationship_type_labels',
			'ca_relationship_types',
			'ca_replication_log',
			'ca_representation_annotation_labels',
			'ca_representation_annotations',
			'ca_representation_annotations_x_entities',
			'ca_representation_annotations_x_objects',
			'ca_representation_annotations_x_occurrences',
			'ca_representation_annotations_x_places',
			'ca_representation_annotations_x_vocabulary_terms',
			'ca_representation_transcriptions',
			'ca_schema_updates',
			'ca_search_form_labels',
			'ca_search_form_placements',
			'ca_search_form_type_restrictions',
			'ca_search_forms',
			'ca_search_forms_x_user_groups',
			'ca_search_forms_x_users',
			'ca_search_indexing_queue',
			'ca_search_log',
			'ca_set_item_labels',
			'ca_set_items',
			'ca_set_labels',
			'ca_sets',
			'ca_sets_x_user_groups',
			'ca_sets_x_users',
			'ca_site_page_media',
			'ca_site_pages',
			'ca_site_templates',
			'ca_sql_search_ngrams',
			'ca_sql_search_word_index',
			'ca_sql_search_words',
			'ca_storage_location_labels',
			'ca_storage_locations',
			'ca_storage_locations_x_storage_locations',
			'ca_storage_locations_x_vocabulary_terms',
			'ca_task_queue',
			'ca_tour_labels',
			'ca_tour_stops',
			'ca_tour_stop_labels',
			'ca_tour_stops_x_collections',
			'ca_tour_stops_x_entities',
			'ca_tour_stops_x_objects',
			'ca_tour_stops_x_occurrences',
			'ca_tour_stops_x_places',
			'ca_tour_stops_x_tour_stops',
			'ca_tour_stops_x_vocabulary_terms',
			'ca_tours',
			'ca_user_groups',
			'ca_user_notes',
			'ca_user_representation_annotation_labels',
			'ca_user_representation_annotations',
			'ca_user_representation_annotations_x_entities',
			'ca_user_representation_annotations_x_objects',
			'ca_user_representation_annotations_x_occurrences',
			'ca_user_representation_annotations_x_places',
			'ca_user_representation_annotations_x_vocabulary_terms',
			'ca_user_roles',
			'ca_user_sort_items',
			'ca_user_sorts',
			'ca_users',
			'ca_users_x_groups',
			'ca_users_x_roles',
			'ca_watch_list',
		];
	}

	/**
	 * @param Db $db
	 *
	 * @return DbResult|false
	 */
	public function removeDefaultValues(Db $db)
	{
		$db->query("ALTER TABLE ca_metadata_element_labels ALTER COLUMN is_preferred DROP DEFAULT");
		$this->messages[__METHOD__]['added'][ 'ca_metadata_element_labels' ] = 'is_preferred DROP DEFAULT';
	}
}
