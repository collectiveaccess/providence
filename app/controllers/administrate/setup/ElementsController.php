<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/ElementsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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


	require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
	require_once(__CA_MODELS_DIR__.'/ca_metadata_element_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_metadata_type_restrictions.php');
	require_once(__CA_LIB_DIR__.'/ca/Attributes/Attribute.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/BaseEditorController.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

class ElementsController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_metadata_elements';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	public function Index() {
		JavascriptLoadManager::register('tableList');
	
		$vo_dm = Datamodel::load();
		$va_elements = ca_metadata_elements::getRootElementsAsList(null, null, true, true);
		$this->view->setVar('element_list',$va_elements);
		$this->view->setVar('attribute_types', Attribute::getAttributeTypes());
		
		$o_result_context = new ResultContext($this->request, $this->ops_table_name, 'basic_search');
		$o_result_context->setResultList(array_keys($va_elements));
		$o_result_context->setAsLastFind();
		$o_result_context->saveContext();
		
		return $this->render('elements_list_html.php');
	}
	# -------------------------------------------------------
	public function Edit($pa_values=null, $pa_options=null){
		JavascriptLoadManager::register('bundleableEditor');
		
		
		$t_element = $this->getElementObject();
		$t_restriction = new ca_metadata_type_restrictions(null, true);
		
		$this->view->setVar('available_settings',$t_element->getAvailableSettings());
		$this->view->setVar('type_list', $t_restriction->getTypeListsForTables());
			
		$va_initial_values = array();
		if($t_element->getPrimaryKey()){
			$va_sub_elements = array();
			/* BaseModel::getHierarchyChildren orders by PK, but we need to order by rank */
			$vo_db = new Db();
			$qr_result = $vo_db->query("
				SELECT cmel.*, cme.* 
				FROM ca_metadata_elements cme
				LEFT JOIN ca_metadata_element_labels AS cmel ON cme.element_id = cmel.element_id
				WHERE
					cme.parent_id = ?
				ORDER BY
					cme.rank
			",(int)$t_element->get('element_id'));
			
			while($qr_result->nextRow()){
				$va_row = $qr_result->getRow();
				if (!$va_row['name']) { $va_row['name'] = $va_row['element_code']; }
				$va_sub_elements[$qr_result->get('element_id')][$qr_result->get('locale_id')] = $va_row;
			}
			$va_sub_elements = caExtractValuesByUserLocale($va_sub_elements);
			$this->view->setVar('sub_elements',$va_sub_elements);
			
			// get restrictions
			$this->view->setVar('type_restrictions', $va_type_restrictions = $t_element->getTypeRestrictions());
			
			$va_restriction_settings = $t_restriction->getAvailableSettings();
			if(is_array($va_type_restrictions)){
				foreach($va_type_restrictions as $va_restriction) {
					if ($t_restriction->load($va_restriction['restriction_id'])) {

						foreach($va_restriction_settings as $vs_setting => $va_setting_info) {
							if (!is_array($va_settings = $t_restriction->getSettings())) { $va_settings = array(); }
							$va_initial_values[$t_restriction->getPrimaryKey()] = array_merge($t_restriction->getFieldValuesArray(), $va_settings);
						}
					}
				}
			}
		}

		$this->view->setVar('initial_restriction_values', $va_initial_values);
		if($vn_parent_id = $this->request->getParameter('parent_id', pInteger)){
			$this->view->setVar('parent_id',$vn_parent_id);
		}
	
		$this->view->setVar('t_restriction', $t_restriction);
		$this->render('elements_edit_html.php');
	}
	# -------------------------------------------------------
	public function Save($pa_values=null) {
		$t_element = $this->getElementObject();
		$t_element->setMode(ACCESS_WRITE);
		$va_request = $_REQUEST; /* we don't want to modify $_REQUEST since this may cause ugly side-effects */
		foreach($t_element->getFormFields() as $vs_f => $va_field_info) {
			$t_element->set($vs_f, $_REQUEST[$vs_f]);
			unset($va_request[$vs_f]);
			
			if ($t_element->numErrors()) {
				foreach ($t_element->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
 		}

		if($vn_parent_id = $this->request->getParameter('parent_id', pInteger)){
			$t_element->set('parent_id',$vn_parent_id);
		}
		

		if (!$t_element->getPrimaryKey()) {
			$vb_new = true;
			$vo_db = $t_element->getDb();
			if($vn_parent_id){
				$qr_tmp = $vo_db->query("
					SELECT MAX(rank) AS rank
					FROM ca_metadata_elements
					WHERE parent_id=?
				",$vn_parent_id);
				if(!$qr_tmp->nextRow()){
					$t_element->set('rank',1);
				} else {
					$t_element->set('rank',intval($qr_tmp->get('rank'))+1);
				}
			}
			$t_element->insert();
			$vs_message = _t("Added metadata element");
			$this->request->setParameter('element_id',$t_element->getPrimaryKey());
		} else {
			$t_element->update();
			$vb_new = false;
			$vs_message = _t("Saved changes to metadata element");
		}

		if ($t_element->numErrors()) {
			foreach ($t_element->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
			}
		} else {
			$this->notification->addNotification($vs_message, __NOTIFICATION_TYPE_INFO__);
		}

		if ($t_element->getPrimaryKey()) {
			$va_new_labels = array();
			$va_old_labels = array();
			$va_delete_labels = array();
			foreach($va_request as $vs_key => $vs_val){
				if(!(strpos($vs_key,'element_labels_Pref')===false)) { /* label field */
					$va_matches = array();
					if(!(strpos($vs_key,'_new')===false)){ /* new label field */
						preg_match('/element_labels_Pref(.*)_new_([0-9]+)/',$vs_key,$va_matches);
						$va_new_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
					} else if(!(strpos($vs_key,'_delete')===false)){ /* delete label */
						preg_match('/element_labels_PrefLabel_([0-9]+)_delete/',$vs_key,$va_matches);
						$va_delete_labels[] = $va_matches[1];
					} else {/* existing label field */
						preg_match('/element_labels_Pref(.*)_([0-9]+)/',$vs_key,$va_matches);
						$va_old_labels[$va_matches[2]][$va_matches[1]] = $vs_val;
					}
					unset($va_request[$vs_key]);
				}
			}
	
			/* insert new labels */
			$t_element_label = new ca_metadata_element_labels();
			foreach($va_new_labels as $va_label){
				$t_element_label->clear();
				foreach($va_label as $vs_f => $vs_val){
					$t_element_label->set($vs_f,$vs_val);
				}
				$t_element_label->set('element_id',$t_element->getPrimaryKey());
				$t_element_label->setMode(ACCESS_WRITE);
				$t_element_label->insert();
				if ($t_element_label->numErrors()) {
					foreach ($t_element_label->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				}
			}
	
			/* delete labels */
			foreach($va_delete_labels as $vn_label){
				$t_element_label->load($vn_label);
				$t_element_label->setMode(ACCESS_WRITE);
				$t_element_label->delete(false);
			}
	
			/* process old labels */
			foreach($va_old_labels as $vn_key => $va_label){
				$t_element_label->load($vn_key);
				foreach($va_label as $vs_f => $vs_val){
					$t_element_label->set($vs_f,$vs_val);
				}
				$t_element_label->set('element_id',$t_element->getPrimaryKey());
				$t_element_label->setMode(ACCESS_WRITE);
				if($vb_new){
					$t_element_label->insert();
				} else {
					$t_element_label->update();
				}
				if ($t_element_label->numErrors()) {
					foreach ($t_element_label->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				}
			}
	
			/* process settings */
			if (is_array($va_settings = $t_element->getAvailableSettings())) {
				$vb_need_to_update = false;
				foreach($va_settings as $vs_setting_key => $va_setting_info) {
					if (isset($va_setting_info['refreshOnChange']) && (bool)$va_setting_info['refreshOnChange']) {
						$t_element->setSetting($vs_setting_key, $va_request['setting_'.$vs_setting_key]);
						$vb_need_to_update = true;
					}
				}
				if ($vb_need_to_update) { 
					$t_element->update(); 
					$va_settings = $t_element->getAvailableSettings();
				}
				
				foreach($va_settings as $vs_setting_key => $va_setting_info) {
					if (isset($va_request['setting_'.$vs_setting_key.'[]'])) {
						$vs_val = $va_request['setting_'.$vs_setting_key.'[]'];
					} else {
						$vs_val = $va_request['setting_'.$vs_setting_key];
					}
					
					if (!($t_element->setSetting($vs_setting_key, $vs_val, $vs_error))) {
						$this->notification->addNotification(_t("Setting %2 is not valid: %1", $vs_error, $vs_setting_key), __NOTIFICATION_TYPE_ERROR__);
						continue;
					}
					$t_element->update();
				}
			}
			
			/* process type restrictions */
			$t_restriction = new ca_metadata_type_restrictions(null, true);
			$va_settings = array_keys($t_restriction->getAvailableSettings());

			foreach($_REQUEST as $vs_key => $vs_value) {
				if (preg_match('!^type_restrictions_table_num_([\d]+)$!', $vs_key, $va_matches)) {
					// got one to update
					if ($t_restriction->load($va_matches[1])) {
						$t_restriction->setMode(ACCESS_WRITE);
						$t_restriction->set('table_num', $this->request->getParameter('type_restrictions_table_num_'.$va_matches[1], pInteger));
						$t_restriction->set('type_id', ($vn_type_id = $this->request->getParameter('type_restrictions_type_id_'.$va_matches[1], pInteger)) ? $vn_type_id : null);
						$t_restriction->set('include_subtypes', ($vn_include_subtypes = $this->request->getParameter('type_restrictions_include_subtypes_'.$va_matches[1], pInteger)) ? $vn_include_subtypes : null);
						
						foreach($va_settings as $vs_setting) {
							$t_restriction->setSetting($vs_setting, $this->request->getParameter('type_restrictions_setting_'.$vs_setting.'_'.$va_matches[1], pString));
						}
						
						$t_restriction->update();
					}
					continue;
				}
				if (preg_match('!^type_restrictions_table_num_new_([\d]+)$!', $vs_key, $va_matches)) {
					// got one to create
					$t_restriction->setMode(ACCESS_WRITE);
					$t_restriction->set('element_id', $t_element->getPrimaryKey());
					$t_restriction->set('table_num', $this->request->getParameter('type_restrictions_table_num_new_'.$va_matches[1], pInteger));
					$t_restriction->set('type_id', ($vn_type_id = $this->request->getParameter('type_restrictions_type_id_new_'.$va_matches[1], pInteger)) ? $vn_type_id : null);
					$t_restriction->set('include_subtypes', ($vn_include_subtypes = $this->request->getParameter('type_restrictions_include_subtypes_new_'.$va_matches[1], pInteger)) ? $vn_include_subtypes : null);
					
					foreach($va_settings as $vs_setting) {
						$t_restriction->setSetting($vs_setting, $this->request->getParameter('type_restrictions_setting_'.$vs_setting.'_new_'.$va_matches[1], pString));
					}
					
					$t_restriction->insert();
					continue;
				}
				
				if (preg_match('!^type_restrictions_([\d]+)_delete$!', $vs_key, $va_matches)) {
					// got one to delete
					if ($t_restriction->load($va_matches[1])) {
						$t_restriction->setMode(ACCESS_WRITE);
						$t_restriction->delete();
					}
					continue;
				}
			}
		}
		
		$this->Edit();
		return;
 	}
	# -------------------------------------------------------
	public function Delete($pa_values=null) {
		$t_element = $this->getElementObject();
		if ($this->request->getParameter('confirm', pInteger)) {
			$t_element->setMode(ACCESS_WRITE);
			$t_element->delete(true);

			if ($t_element->numErrors()) {
				foreach ($t_element->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			} else {
 				$this->notification->addNotification(_t("Deleted metadata element"), __NOTIFICATION_TYPE_INFO__);
 			}

 			$this->Index();
 			return;
 		} else {
 			$this->render('elements_delete_html.php');
 		}
 	}
	# -------------------------------------------------------
	public function MoveElementUp() {
		$t_element = $this->getElementObject();
		if(is_array($va_ranks_to_stabilize = $this->elementRankStabilizationNeeded($t_element->get('parent_id')))){
			$this->stabilizeElementRanks($t_element->get('parent_id'),$va_ranks_to_stabilize);
		}
		$t_element = $this->getElementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT element_id, rank
			FROM ca_metadata_elements
			WHERE
				(rank < ?)
				AND
				(parent_id = ?)
			ORDER BY
				rank DESC
		",$t_element->get('rank'),$t_element->get('parent_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This element is at the top of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_element_rankswap = new ca_metadata_elements($qr_tmp->get('element_id'));
			$this->swapRanks($t_element, $t_element_rankswap);
			if($t_element->numErrors()){
				foreach ($t_element->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_element_rankswap->numErrors()){
				foreach ($t_element_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->request->setParameter('element_id',$this->request->getParameter('parent_id',pInteger));
		$this->Edit();
 	}
	# -------------------------------------------------------
	public function MoveElementDown() {
		$t_element = $this->getElementObject();
		if(is_array($va_ranks_to_stabilize = $this->elementRankStabilizationNeeded($t_element->get('parent_id')))){
			$this->stabilizeElementRanks($t_element->get('parent_id'),$va_ranks_to_stabilize);
		}
		$t_element = $this->getElementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT element_id,rank
			FROM ca_metadata_elements
			WHERE
				(rank > ?)
				AND
				(parent_id = ?)
			ORDER BY
				rank
		",$t_element->get('rank'),$t_element->get('parent_id'));
		if(!$qr_tmp->nextRow()){
			$this->notification->addNotification(_t("This element is at the bottom of the list"), __NOTIFICATION_TYPE_ERROR__);
		} else { /* swap ranks */
			$t_element_rankswap = new ca_metadata_elements($qr_tmp->get('element_id'));
			$this->swapRanks($t_element, $t_element_rankswap);
			if($t_element->numErrors()){
				foreach ($t_element->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
			if($t_element_rankswap->numErrors()){
				foreach ($t_element_rankswap->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
		}
		$this->request->setParameter('element_id',$this->request->getParameter('parent_id',pInteger));
		$this->Edit();
 	}
	# -------------------------------------------------------

	# -------------------------------------------------------
	# Utilities
 	# -------------------------------------------------------
 	private function getElementObject($pb_set_view_vars=true, $pn_element_id=null) {
		if (!($vn_element_id = $this->request->getParameter('element_id', pInteger))) {
			$vn_element_id = $pn_element_id;
		}
		$t_element = new ca_metadata_elements($vn_element_id);
 		if ($pb_set_view_vars){
 			$this->view->setVar('element_id', $vn_element_id);
 			$this->view->setVar('t_element', $t_element);
 		}
 		return $t_element;
 	}
	# -------------------------------------------------------
	private function swapRanks(&$t_first,&$t_second){
		$vn_first_rank = $t_first->get('rank');
		$vn_second_rank = $t_second->get('rank');
		$t_first->setMode(ACCESS_WRITE);
		$t_first->set('rank',$vn_second_rank);
		$t_first->update();
		$t_second->setMode(ACCESS_WRITE);
		$t_second->set('rank',$vn_first_rank);
		$t_second->update();
		return true;
	}
	# -------------------------------------------------------
	private function elementRankStabilizationNeeded($pn_parent_id){
		$vo_db = new Db();
		$qr_res = $vo_db->query("
			SELECT * FROM
				(SELECT rank,count(*) as count
					FROM ca_metadata_elements
					WHERE parent_id=?
					GROUP BY rank) as lambda
			WHERE
				count > 1;
		",$pn_parent_id);
		if($qr_res->numRows()){
			$va_return = array();
			while($qr_res->nextRow()){
				$va_return[$qr_res->get('rank')] = $qr_res->get('count');
			}
			return $va_return;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	private function stabilizeElementRanks($pn_parent_id,$pa_ranks){
		$vo_db = new Db();
		$t_element = new ca_metadata_elements();
		do {
			$va_ranks = array_keys($pa_ranks);
			$vn_rank = $va_ranks[0];
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_metadata_elements
					WHERE
						(parent_id=?)
						AND
						(rank>?)
					ORDER BY
						rank
			",$pn_parent_id,$vn_rank);
			while($qr_res->nextRow()){
				$t_element->load($qr_res->get('element_id'));
				$t_element->set('rank',intval($t_element->get('rank'))+$pa_ranks[0]);
				$t_element->setMode(ACCESS_WRITE);
				$t_element->update();
			}
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_metadata_elements
					WHERE
						(parent_id=?)
						AND
						(rank=?)
					ORDER BY
						rank
			",$pn_parent_id,$vn_rank);
			$i=0;
			while($qr_res->nextRow()){
				$i++;
				$t_element->load($qr_res->get('element_id'));
				$t_element->set('rank',intval($t_element->get('rank')) + $i);
				$t_element->setMode(ACCESS_WRITE);
				$t_element->update();
			}
			$pa_ranks = $this->elementRankStabilizationNeeded($pn_parent_id);
		} while(is_array($pa_ranks));
	}
	# -------------------------------------------------------
	# AJAX
	# -------------------------------------------------------
	public function getElementSettingsForm(){
		$t_element = $this->getElementObject();
		$pn_datatype = $this->request->getParameter('datatype', pInteger);
		
		$t_element->set('datatype', $pn_datatype); 
		
		foreach($_REQUEST as $vs_k => $vs_v) {
			if (substr($vs_k, 0, 8) == 'setting_') {
				$t_element->setSetting(substr($vs_k, 8), $y=$this->request->getParameter($vs_k, pString));
			}
		}
		
		$this->view->setVar('available_settings',$t_element->getAvailableSettings($ps_service));
		$this->render("ajax_elements_settings_form_html.php");
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		
		return $this->render('widget_element_info_html.php', true);
	}
	# -------------------------------------------------------
}
