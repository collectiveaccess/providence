<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseLabel.php : Base class for ca_*_labels models
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 
	class BaseLabel extends BaseModel {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function insert($pa_options=null) {
			$this->_generateSortableValue();	// populate sort field
			return parent::insert($pa_options);
		}
		# -------------------------------------------------------
		public function update($pa_options=null) {
			$this->_generateSortableValue();	// populate sort field
			
			// Invalid entire labels-by-id cache since we can't know what entries pertain to the label we just changed
			LabelableBaseModelWithAttributes::$s_labels_by_id_cache = array();		
			
			// Unset label cache entry for modified label only
			unset(LabelableBaseModelWithAttributes::$s_label_cache[$this->getSubjectTableName()][$this->get($this->getSubjectKey())]);
			return parent::update($pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns a list of fields that should be displayed in user interfaces for labels
		 */
		public function getUIFields() {
			return $this->LABEL_UI_FIELDS;
		}
		# -------------------------------------------------------
		/**
		 * Returns name of single field to use for display of label
		 */
		public function getDisplayField() {
			return $this->LABEL_DISPLAY_FIELD;
		}
		# -------------------------------------------------------
		/**
		 * Returns name of table this table contains label for
		 */
		public function getSubjectTableName() {
			return $this->LABEL_SUBJECT_TABLE;
		}
		# -------------------------------------------------------
		/**
		 * Returns name of field that is foreign key of subject
		 */
		public function getSubjectKey() {
			if (!($t_subject = $this->getSubjectTableInstance())) { return null; }
			return $t_subject->primaryKey();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns instance of table this table contains label for
		 *
		 * @param array $pa_options Options are.
		 *		dontLoadInstance = If set returned instance is not preloaded with subject. Default is false - load subject data
		 *
		 * @return BaseModel Instance of subject table
		 */
		public function getSubjectTableInstance($pa_options=null) {
			if ($vs_subject_table_name = $this->getSubjectTableName()) {
				$t_subject =  $this->_DATAMODEL->getInstanceByTableName($vs_subject_table_name, true);
				
				if (!caGetOption("dontLoadInstance", $pa_options, false) && ($vn_id = $this->get($t_subject->primaryKey()))) {
					$t_subject->load($vn_id);
				}
				return $t_subject;
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns name of single field to use for sort of label content
		 **/
		public function getSortField() {
			return $this->LABEL_SORT_FIELD;
		}
		# -------------------------------------------------------
		/**
		 * Returns version of label 'display' field value suitable for sorting
		 * The sortable value is the same as the display value except when the display value
		 * starts with a definite article ('the' in English) or indefinite article ('a' or 'an' in English)
		 * in the locale of the label, in which case the article is moved to the end of the sortable value.
		 * 
		 * What constitutes an article is defined in the TimeExpressionParser localization files. So if the
		 * locale of the label doesn't correspond to an existing TimeExpressionParser localization, then
		 * the users' current locale setting is used.
		 */
		private function _generateSortableValue() {
			if ($vs_sort_field = $this->getProperty('LABEL_SORT_FIELD')) {
				$vs_display_field = $this->getProperty('LABEL_DISPLAY_FIELD');
				
				$o_tep = new TimeExpressionParser();
				
				$o_tep->setLanguage(ca_locales::localeIDToCode($this->get('locale_id')));
				$o_lang_settings = $o_tep->getLanguageSettings();
				$vs_display_value = trim(preg_replace('![^\p{L}0-9 ]+!u', ' ', $this->get($vs_display_field)));
				
				$va_definite_articles = $o_lang_settings->get('definiteArticles');
				$va_indefinite_articles = $o_lang_settings->get('indefiniteArticles');
				
				foreach(array($o_lang_settings->get('definiteArticles'), $o_lang_settings->get('indefiniteArticles')) as $va_articles) {
					if (is_array($va_articles)) {
						foreach($va_articles as $vs_article) {
							if (preg_match('!^('.$vs_article.')[ ]+!i', $vs_display_value, $va_matches)) {
								$vs_display_value = trim(str_replace($va_matches[1], '', $vs_display_value).', '.$va_matches[1]);
								break(2);
							}
						}
					}
				}
				
				$this->set($vs_sort_field, $vs_display_value);
			}
		}
		# -------------------------------------------------------
	}
?>