<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/ElementsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Attributes/Attribute.php');
require_once(__CA_LIB_DIR__.'/BaseEditorController.php');
require_once(__CA_LIB_DIR__.'/ResultContext.php');

class ElementsController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_metadata_elements';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	/**
 	 *
 	 */
	public function __construct(&$request, &$response, $pa_view_paths=null) {
		parent::__construct($request, $response, $pa_view_paths);
		
		if(!$request || !$request->isLoggedIn() || !$request->user->canDoAction('can_configure_metadata_elements')) {
			throw new AccessException(_t('Access denied'));
		}
	}
	# -------------------------------------------------------
	/**
 	 *
 	 */
	public function Index() {
		AssetLoadManager::register('tableList');
	
		$elements = caGetDataBundleList(); //ca_metadata_elements::getRootElementsAsList(null, null, true, true);
		
		$this->view->setVar('element_list',$elements);
		$this->view->setVar('attribute_types', CA\Attributes\Attribute::getAttributeTypes());
		
		$o_result_context = new ResultContext($this->request, $this->ops_table_name, 'basic_search');
		$o_result_context->setResultList(array_keys($elements));
		$o_result_context->setAsLastFind();
		$o_result_context->saveContext();
		
		return $this->render('elements_list_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Edit($pa_values=null, $pa_options=null){
		AssetLoadManager::register('bundleableEditor');
		
		$t_element = $this->getElementObject();
		$t_restriction = new ca_metadata_type_restrictions(null, null, false);
		
		$this->view->setVar('available_settings',$t_element->getAvailableSettings());
		$this->view->setVar('type_list', $t_restriction->getTypeListsForTables());
			
		$initial_values = array();
		if($t_element->getPrimaryKey()){
			$sub_elements = array();
			/* BaseModel::getHierarchyChildren orders by PK, but we need to order by rank */
			$vo_db = new Db();
			$qr_result = $vo_db->query("
				SELECT cmel.*, cme.* 
				FROM ca_metadata_elements cme
				LEFT JOIN ca_metadata_element_labels AS cmel ON cme.element_id = cmel.element_id
				WHERE
					cme.parent_id = ? AND cme.deleted = 0
				ORDER BY
					cme.`rank`
			",(int)$t_element->get('element_id'));
			
			while($qr_result->nextRow()){
				$row = $qr_result->getRow();
				if (!$row['name']) { $row['name'] = $row['element_code']; }
				$sub_elements[$qr_result->get('element_id')][$qr_result->get('locale_id')] = $row;
			}
			$sub_elements = caExtractValuesByUserLocale($sub_elements);
			$this->view->setVar('sub_elements',$sub_elements);
			
			// get restrictions
			$this->view->setVar('type_restrictions', $type_restrictions = $t_element->getTypeRestrictions());
			
			$restriction_settings = $t_restriction->getAvailableSettings();
			if(is_array($type_restrictions)){
				foreach($type_restrictions as $restriction) {
					if ($t_restriction->load($restriction['restriction_id'])) {

						foreach($restriction_settings as $setting => $setting_info) {
							if (!is_array($settings = $t_restriction->getSettings())) { $settings = array(); }
							$initial_values[$t_restriction->getPrimaryKey()] = array_merge($t_restriction->getFieldValuesArray(), $settings);
						}
					}
				}
			}
		}

		$this->view->setVar('initial_restriction_values', $initial_values);
		if($parent_id = $this->request->getParameter('parent_id', pInteger)){
			$this->view->setVar('parent_id',$parent_id);
		}
	
		$this->view->setVar('t_restriction', $t_restriction);
		$this->render('elements_edit_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Save($pa_values=null) {
		$t_element = $this->getElementObject(false);
		$request = $_REQUEST; /* we don't want to modify $_REQUEST since this may cause ugly side-effects */
		foreach($t_element->getFormFields() as $f => $field_info) {
			if ((bool)$t_element->getAppConfig()->get('ca_metadata_elements_dont_allow_editing_of_codes_when_in_use') && $t_element->getPrimaryKey()) { continue; }
			if ((bool)$t_element->getAppConfig()->get('ca_metadata_elements_dont_allow_editing_of_data_types_when_in_use') && $t_element->getPrimaryKey()) { continue; }
			
			$t_element->set($f, $_REQUEST[$f] ?? null);
			unset($request[$f]);
			
			if ($t_element->numErrors()) {
				foreach ($t_element->errors() as $o_e) {
					$this->request->addActionError($o_e, 'general');
					$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
				}
			}
 		}

		if($parent_id = $this->request->getParameter('parent_id', pInteger)){
			$t_element->set('parent_id',$parent_id);
		}
		
		if (!$t_element->getPrimaryKey()) {
			$vb_new = true;
			$vo_db = $t_element->getDb();
			if($parent_id){
				$qr_tmp = $vo_db->query("
					SELECT MAX(`rank`) AS `rank`
					FROM ca_metadata_elements
					WHERE parent_id = ? AND deleted = 0
				",$parent_id);
				if(!$qr_tmp->nextRow()){
					$t_element->set('rank',1);
				} else {
					$t_element->set('rank',intval($qr_tmp->get('rank'))+1);
				}
			}
			$t_element->insert();
			$message = _t("Added metadata element");
			$this->request->setParameter('element_id',$t_element->getPrimaryKey());
		} else {
			$t_element->update();
			$vb_new = false;
			$message = _t("Saved changes to metadata element");
		}

		if ($t_element->numErrors()) {
			foreach ($t_element->errors() as $o_e) {
				$this->request->addActionError($o_e, 'general');
				$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
			}
		} else {
			$this->notification->addNotification($message, __NOTIFICATION_TYPE_INFO__);
		}

		if ($t_element->getPrimaryKey()) {
			$new_labels = $old_labels = $delete_labels = [];
			$new_alt_labels = $old_alt_labels = $delete_alt_labels = [];
		
			// Preferred labels
			foreach($request as $key => $val){
				if(strpos($key,'element_labels_Pref') !== false) { /* label field */
					$matches = [];
					if(!(strpos($key,'_new')===false)){ /* new label field */
						preg_match('/element_labels_Pref(.*)_new_([0-9]+)/',$key,$matches);
						$new_labels[$matches[2]][$matches[1]] = $val;
					} else if(!(strpos($key,'_delete')===false)){ /* delete label */
						preg_match('/element_labels_PrefLabel_([0-9]+)_delete/',$key,$matches);
						$delete_labels[] = $matches[1];
					} else {/* existing label field */
						preg_match('/element_labels_Pref(.*)_([0-9]+)/',$key,$matches);
						$old_labels[$matches[2]][$matches[1]] = $val;
					}
					unset($request[$key]);
				}
				// Nonpreferred labels (disambiguation labels)
				if(strpos($key,'alt_element_labels_NPref') !== false) { /* label field */
					$matches = [];
					if(!(strpos($key,'_new')===false)){ /* new label field */
						preg_match('/alt_element_labels_NPref(.*)_new_([0-9]+)/',$key,$matches);
						$new_alt_labels[$matches[2]][$matches[1]] = $val;
					} else if(!(strpos($key,'_delete')===false)){ /* delete label */
						preg_match('/alt_element_labels_NPrefLabel_([0-9]+)_delete/',$key,$matches);
						$delete_alt_labels[] = $matches[1];
					} else {/* existing label field */
						preg_match('/alt_element_labels_NPref(.*)_([0-9]+)/',$key,$matches);
						$old_alt_labels[$matches[2]][$matches[1]] = $val;
					}
					unset($request[$key]);
				}
			}
			
		
			/* insert new labels */
			foreach([
				1 => ['new' => $new_labels, 'old' => $old_labels, 'delete' => $delete_labels],
				0 => ['new' => $new_alt_labels, 'old' => $old_alt_labels, 'delete' => $delete_alt_labels]
			] as $is_preferred => $data) {
				$t_element_label = new ca_metadata_element_labels();
				foreach($data['new'] as $label){
					if (!$is_preferred && !$label['name']) { continue; }
					$t_element_label->clear();
					foreach($label as $f => $val){
						$t_element_label->set($f,$val);
					}
					$t_element_label->set('is_preferred', $is_preferred);
					$t_element_label->set('element_id',$t_element->getPrimaryKey());
					$t_element_label->insert();
					if ($t_element_label->numErrors()) {
						foreach ($t_element_label->errors() as $o_e) {
							$this->request->addActionError($o_e, 'general');
							$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
						}
					}
				}
	
				/* delete labels */
				foreach($data['delete'] as $label){
					$t_element_label->load($label);
					$t_element_label->delete(false);
				}
	
				/* process old labels */
				foreach($data['old'] as $key => $label){
					if (!$is_preferred && !$label['name']) { continue; }
					$t_element_label->load($key);
					foreach($label as $f => $val){
						$t_element_label->set($f,$val);
					}
					$t_element_label->set('is_preferred', $is_preferred);
					$t_element_label->set('element_id',$t_element->getPrimaryKey());
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
			}

			/* process settings */
			if (is_array($settings = $t_element->getAvailableSettings())) {
				$vb_need_to_update = false;
				foreach($settings as $setting_key => $setting_info) {
					if (isset($setting_info['refreshOnChange']) && (bool)$setting_info['refreshOnChange']) {
						$t_element->setSetting($setting_key, $request['setting_'.$setting_key]);
						$vb_need_to_update = true;
					}
				}
				if ($vb_need_to_update) { 
					$t_element->update(); 
					$settings = $t_element->getAvailableSettings();
				}

				// we need to unset the form timestamp to disable the 'Changes have been made since you loaded this data' warning
				// when we update() below. the warning makes sense because an update() is called before we get here, but if there
				// was an actual concurrent save problem , that very update above would have triggered the warning already
				$timestamp = $_REQUEST['form_timestamp'];
				unset($_REQUEST['form_timestamp']);

				foreach($settings as $setting_key => $setting_info) {
					if (isset($request['setting_'.$setting_key.'[]'])) {
						$val = $request['setting_'.$setting_key.'[]'];
					} else {
						$val = $request['setting_'.$setting_key] ?? null;
					}
					$error = null;
					if (!($t_element->setSetting($setting_key, $val, $error))) {
						$this->notification->addNotification(_t("Setting %2 is not valid: %1", $error, $setting_key), __NOTIFICATION_TYPE_ERROR__);
						continue;
					}
				}

				$t_element->update();
				$_REQUEST['form_timestamp'] = $timestamp;
			}
		
			/* process type restrictions */
			$t_restriction = new ca_metadata_type_restrictions(null, null, false);
			$settings = array_keys($t_restriction->getAvailableSettings());

			foreach($_REQUEST as $key => $value) {
				if (preg_match('!^type_restrictions_table_num_([\d]+)$!', $key, $matches)) {
					// got one to update
					if ($t_restriction->load($matches[1])) {
						$t_restriction->set('table_num', $this->request->getParameter('type_restrictions_table_num_'.$matches[1], pInteger));
						$t_restriction->set('type_id', ($type_id = $this->request->getParameter('type_restrictions_type_id_'.$matches[1], pInteger)) ? $type_id : null);
						$t_restriction->set('include_subtypes', ($include_subtypes = $this->request->getParameter('type_restrictions_include_subtypes_'.$matches[1], pInteger)) ? $include_subtypes : null);
						
						foreach($settings as $setting) {
							$t_restriction->setSetting($setting, $this->request->getParameter('type_restrictions_setting_'.$setting.'_'.$matches[1], pString));
						}
						
						$t_restriction->update();
					}
					continue;
				}
				if (preg_match('!^type_restrictions_table_num_new_([\d]+)$!', $key, $matches)) {
					// got one to create
					$t_restriction->set('element_id', $t_element->getPrimaryKey());
					$t_restriction->set('table_num', $this->request->getParameter('type_restrictions_table_num_new_'.$matches[1], pInteger));
					$t_restriction->set('type_id', ($type_id = $this->request->getParameter('type_restrictions_type_id_new_'.$matches[1], pInteger)) ? $type_id : null);
					$t_restriction->set('include_subtypes', ($include_subtypes = $this->request->getParameter('type_restrictions_include_subtypes_new_'.$matches[1], pInteger)) ? $include_subtypes : null);
					
					foreach($settings as $setting) {
						$t_restriction->setSetting($setting, $this->request->getParameter('type_restrictions_setting_'.$setting.'_new_'.$matches[1], pString));
					}
					
					$t_restriction->insert();
					continue;
				}
			
				if (preg_match('!^type_restrictions_([\d]+)_delete$!', $key, $matches)) {
					// got one to delete
					if ($t_restriction->load($matches[1])) {
						$t_restriction->delete();
					}
					continue;
				}
			}
			
            $t_element->flushCacheForElement();
		}
		
		$this->Edit();
		return;
 	}
	# -------------------------------------------------------
	/**
 	 *
 	 */
	public function Delete($pa_values=null) {
		$t_element = $this->getElementObject();
		if ($this->request->getParameter('confirm', pInteger)) {
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
	/**
 	 *
 	 */
	public function MoveElementUp() {
		$t_element = $this->getElementObject();
		if(is_array($ranks_to_stabilize = $this->elementRankStabilizationNeeded($t_element->get('parent_id')))){
			$this->stabilizeElementRanks($t_element->get('parent_id'),$ranks_to_stabilize);
		}
		$t_element = $this->getElementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT element_id, `rank`
			FROM ca_metadata_elements
			WHERE
				(`rank` < ?)
				AND
				(parent_id = ?)
				AND
				(deleted = 0)
			ORDER BY
				`rank` DESC
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
	/**
 	 *
 	 */
	public function MoveElementDown() {
		$t_element = $this->getElementObject();
		if(is_array($ranks_to_stabilize = $this->elementRankStabilizationNeeded($t_element->get('parent_id')))){
			$this->stabilizeElementRanks($t_element->get('parent_id'),$ranks_to_stabilize);
		}
		$t_element = $this->getElementObject();
		$vo_db = new Db();
		$qr_tmp = $vo_db->query("
			SELECT element_id, `rank`
			FROM ca_metadata_elements
			WHERE
				(`rank` > ?)
				AND
				(parent_id = ?)
				AND
				(deleted = 0)
			ORDER BY
				`rank`
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
	# Utilities
 	# -------------------------------------------------------
 	/**
 	 *
 	 */
 	private function getElementObject(?bool $set_view_vars=true, ?int $element_id=null) : ?ca_metadata_elements {
 		if(!$element_id) {
 			$element_id = $this->request->getParameter('element_id', pInteger);
		}
		$t_element = ca_metadata_elements::getInstance($element_id);
 		if ($set_view_vars){
 			$this->view->setVar('element_id', $element_id);
 			$this->view->setVar('t_element', $t_element);
 		}
 		return $t_element;
 	}
	# -------------------------------------------------------
	 /**
 	 *
 	 */
	private function swapRanks(&$t_first,&$t_second){
		$first_rank = $t_first->get('rank');
		$second_rank = $t_second->get('rank');
		$t_first->set('rank',$second_rank);
		$t_first->update();
		
		$t_second->set('rank',$first_rank);
		$t_second->update();
		return true;
	}
	# -------------------------------------------------------
	 /**
 	 *
 	 */
	private function elementRankStabilizationNeeded($pn_parent_id){
		$vo_db = new Db();
		$qr_res = $vo_db->query("
			SELECT * FROM
				(SELECT `rank`,count(*) as count
					FROM ca_metadata_elements
					WHERE parent_id = ? AND deleted = 0
					GROUP BY `rank`) as `lambda`
			WHERE
				count > 1;
		",$pn_parent_id);
		if($qr_res->numRows()){
			$return = array();
			while($qr_res->nextRow()){
				$return[$qr_res->get('rank')] = $qr_res->get('count');
			}
			return $return;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	 /**
 	 *
 	 */
	private function stabilizeElementRanks($pn_parent_id,$pa_ranks){
		$vo_db = new Db();
		$t_element = new ca_metadata_elements();
		do {
			$ranks = array_keys($pa_ranks);
			$rank = $ranks[0];
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_metadata_elements
					WHERE
						(parent_id = ?)
						AND
						(`rank` > ?)
						AND
						(deleted = 0)
					ORDER BY
						`rank`
			",$pn_parent_id,$rank);
			while($qr_res->nextRow()){
				$t_element->load($qr_res->get('element_id'));
				$t_element->set('rank',intval($t_element->get('rank'))+$pa_ranks[0]);
				$t_element->update();
			}
			$qr_res = $vo_db->query("
				SELECT * FROM
					ca_metadata_elements
					WHERE
						(parent_id=?)
						AND
						(`rank` = ?)
						AND
						(deleted = 0)
					ORDER BY
						`rank`
			",$pn_parent_id,$rank);
			$i=0;
			while($qr_res->nextRow()){
				$i++;
				$t_element->load($qr_res->get('element_id'));
				$t_element->set('rank',intval($t_element->get('rank')) + $i);
				$t_element->update();
			}
			$pa_ranks = $this->elementRankStabilizationNeeded($pn_parent_id);
		} while(is_array($pa_ranks));
	}
	# -------------------------------------------------------
	# AJAX
	# -------------------------------------------------------
	 /**
 	 *
 	 */
	public function getElementSettingsForm(){
		$t_element = $this->getElementObject();
		$pn_datatype = $this->request->getParameter('datatype', pInteger);
		
		$t_element->set('datatype', $pn_datatype); 
		
		foreach($_REQUEST as $k => $v) {
			if (substr($k, 0, 8) == 'setting_') {
				$t_element->setSetting(substr($k, 8), $y=$this->request->getParameter($k, pString));
			}
		}
		
		$this->view->setVar('available_settings',$t_element->getAvailableSettings());
		$this->render("ajax_elements_settings_form_html.php");
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	 /**
 	 *
 	 */
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		
		return $this->render('widget_element_info_html.php', true);
	}
	# -------------------------------------------------------
}
