<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseLabel.php : Base class for ca_*_labels models
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2017 Whirl-i-Gig
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
  
 	require_once(__CA_LIB_DIR__.'/BaseModel.php');
 	require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');
	require_once(__CA_LIB_DIR__."/SyncableBaseModel.php");
 
	class BaseLabel extends BaseModel {
		# -------------------------------------------------------
		use SyncableBaseModel;
		# -------------------------------------------------------
		public function __construct($pn_id=null, $pb_use_cache=true) {
			parent::__construct($pn_id, $pb_use_cache);
		}
		# -------------------------------------------------------
		public function insert($pa_options=null) {
			$this->_generateSortableValue();	// populate sort field
			// invalidate get() prefetch cache
			SearchResult::clearResultCacheForTable($this->tableName());
			if($vn_rc = parent::insert($pa_options)) {
				$this->setGUID($pa_options);
			}

			return $vn_rc;
		}
		# -------------------------------------------------------
		public function update($pa_options=null) {
			$this->_generateSortableValue();	// populate sort field
			// invalidate get() prefetch cache
			SearchResult::clearResultCacheForTable($this->tableName());
			
			// Invalid entire labels-by-id cache since we can't know what entries pertain to the label we just changed
			LabelableBaseModelWithAttributes::$s_labels_by_id_cache = array();		
			
			// Unset label cache entry for modified label only
			unset(LabelableBaseModelWithAttributes::$s_label_cache[$this->getSubjectTableName()][$this->get($this->getSubjectKey())]);

			return parent::update($pa_options);
		}
		# -------------------------------------------------------
		public function delete ($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
			$vn_primary_key = $this->getPrimaryKey();
			$vn_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);

			if($vn_primary_key && $vn_rc && caGetOption('hard', $pa_options, false)) {
				// Don't remove GUID, otherwise wrong GUID will be sent to target
				//$this->removeGUID($vn_primary_key);
			}

			return $vn_rc;
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
		 * Returns list of secondary display fields. If not defined for the label (most don't have these) an empty array is returned.
		 */
		public function getSecondaryDisplayFields() {
			return property_exists($this, "LABEL_SECONDARY_DISPLAY_FIELDS") ? $this->LABEL_SECONDARY_DISPLAY_FIELDS : [];
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
				$t_subject =  Datamodel::getInstanceByTableName($vs_subject_table_name, true);
				
				if ($t_subject->inTransaction()) { 
					$t_subject->setTransaction($this->getTransaction()); 
				} else {
					$t_subject->setDb($this->getDb());
				}
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
				
				if (!($vs_locale = $this->getAppConfig()->get('use_locale_for_sortable_titles'))) {
					$t_locale = new ca_locales();
					$vs_locale = $t_locale->localeIDToCode($this->get('locale_id'));
				}
				$vs_display_value = caSortableValue($this->get($vs_display_field), array('locale' => $vs_locale));
				
				$this->set($vs_sort_field, $vs_display_value);
			}
		}
		# -------------------------------------------------------
		/**
		 * Set label type list; can vary depending upon whether label is preferred or nonpreferred
		 */
		public function setLabelTypeList($ps_list_idno) {
			if ($this->hasField('type_id')) { 
				$this->FIELDS['type_id']['LIST_CODE'] = $ps_list_idno; 
				return true;
			}
			return false;
		}
		# -------------------------------------------------------
		public function getAdditionalChecksumComponents() {
			return [$this->getSubjectTableInstance()->getGUID()];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function htmlFormElement($ps_field, $ps_format=null, $pa_options=null) {
			if (($ps_field == $this->getDisplayField()) && (is_array($va_use_list = caGetOption('use_list', $pa_options, false))) && ($po_request = caGetOption('request', $pa_options, null))) {
				$vn_list_id = array_shift($va_use_list);
				if ($vn_list_id > 0) {
					$ret_format = caGetOption('use_list_format', $pa_options, 'lookup');
                    
                    if ($ret_format == 'select') {
                    	return ca_lists::getListAsHTMLFormElement($vn_list_id, $pa_options['name'], ['id' => $pa_options['name']], array_merge($pa_options, ['useOptionsForValues' => true]));
                    } else {
						$va_urls = caJSONLookupServiceUrl($po_request, 'ca_list_items', ['list' => caGetListCode($vn_list_id)]);
				
						$pa_options['height'] = 1;
						$pa_options['usewysiwygeditor'] = false;
						$pa_options['lookup_url'] = $va_urls['search'];
					}
                }
			}
			return parent::htmlFormElement($ps_field, $ps_format, $pa_options);
		}
		# -------------------------------------------------------
	}
