<?php
/** ---------------------------------------------------------------------
 * support/sql/migrations/VersionUpdate167.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2020 Whirl-i-Gig
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
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/BaseVersionUpdater.php');
 require_once(__CA_LIB_DIR__."/Db.php");
 require_once(__CA_LIB_DIR__."/Datamodel.php");
 require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
 require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 
	class VersionUpdate167 extends BaseVersionUpdater {
		# -------------------------------------------------------
		protected $opn_schema_update_to_version_number = 167;

		public function updateNullLocales(){
		    $vs_locale = Configuration::load()->get('locale_default') ? : 'en_US';
		    $vn_locale_id = ca_locales::codeToID($vs_locale);
            $vs_sql = "UPDATE ca_sql_search_words set locale_id = ? where locale_id is NULL";

            $o_db = new Db();
            $o_sql = $o_db->prepare($vs_sql);
            $o_result = $o_sql->execute($vn_locale_id);
            return $o_result;
        }

        public function getPostupdateTasks() {
            return array('updateNullLocales');
        }
        # -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function getPostupdateMessage() {
			return _t("You must now rebuild search indices <strong>and</strong> sort values using the administrative maintenance options shown below. These options are available under <em>Administrate</em> in the <em>Manage</em> menu.<br/><div class='contentSuccess'>%1</div>", "<img src='".__CA_URL_ROOT__."/support/sql/migrations/164/rebuild.png' width='246' height='276' alt='Maintenance menu'/>");
		}
		# -------------------------------------------------------
	}
?>