<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/InterfacesController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 
	require_once(__CA_MODELS_DIR__.'/ca_editor_uis.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screens.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_screen_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_editor_ui_bundle_placements.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/BaseEditorController.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

class InterfacesController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_editor_uis';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	/**
	 *
	 */
	public function ListUIs(){
		JavascriptLoadManager::register('tableList');
		
		$this->checkConfiguration();
		
		$vo_dm = Datamodel::load();
		$va_uis = ca_editor_uis::getUIList(null);
		foreach($va_uis as $vs_key => $va_ui){
			if (!($t_instance = $vo_dm->getInstanceByTableNum($va_ui['editor_type'], true))) { continue; }
			$va_uis[$vs_key]['editor_type'] = $t_instance->getProperty('NAME_PLURAL');
		}
		$this->view->setVar('editor_ui_list',$va_uis);
		
		$o_result_context = new ResultContext($this->request, $this->ops_table_name, 'basic_search');
		$o_result_context->setResultList(array_keys($va_uis));
		$o_result_context->setAsLastFind();
		$o_result_context->saveContext();
		
		$t_ui = new ca_editor_uis();
 		$this->view->setVar('table_list', caFilterTableList($t_ui->getFieldInfo('editor_type', 'BOUNDS_CHOICE_LIST')));
		
		return $this->render('ui_list_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Checks for presence of configuration for ca_editor_uis and ca_editor_ui_screens editors, and if not
	 * present, loads a default configuration. Since these editors are used to configure themselves, lack of 
	 * configuration (the case with all pre-version 1.1 installations), will effectively make it impossible to 
	 * change the setup for any editor in the installation.
	 *
	 * @return bool Returns true on success (either UI already exists or was created successfully), false on error (UI does not exist and could not be created)
	 *
	 */
	public function checkConfiguration() {
		global $g_ui_locale_id;
		
		$t_ui = new ca_editor_uis();
		if (!$t_ui->load(array('editor_type' => 101))) {
			$t_ui->setMode(ACCESS_WRITE);
			$t_ui->set('user_id', null);
			$t_ui->set('is_system_ui', 1);
			$t_ui->set('editor_type', 101);
			$t_ui->set('editor_code', 'ui_editor');
			$t_ui->set('color', '000000');
			$t_ui->insert();
			
			if ($t_ui->numErrors()) {
				return false;
			}
			
			$t_ui->addLabel(
				array('name' => 'UI Editor'), $g_ui_locale_id, null, true
			);
			
			if ($t_ui->numErrors()) {
				return false;
			}
			$vn_ui_id = $t_ui->getPrimaryKey();
			
			$t_screen = new ca_editor_ui_screens();
			$t_screen->setMode(ACCESS_WRITE);
			$t_screen->set('ui_id', $vn_ui_id);
			$t_screen->set('idno', 'basic_'.$vn_ui_id);
			$t_screen->set('rank', 1);
			$t_screen->set('default', 1);
			$t_screen->insert();
			
			if ($t_screen->numErrors()) {
				return false;
			}
			
			$t_screen->addLabel(
				array('name' => 'Basic'), $g_ui_locale_id, null, true
			);
			
			if ($t_screen->numErrors()) {
				return false;
			}
			
			$vn_i = 1;
			foreach(array('preferred_labels', 'editor_code', 'color', 'editor_type', 'ca_editor_ui_type_restrictions',  'ca_editor_ui_screens', 'is_system_ui', 'ca_users', 'ca_user_groups') as $vs_bundle_name) {
				$t_screen->addPlacement($vs_bundle_name, $vs_bundle_name, array(), $vn_i);
				$vn_i++;
			}
		}
		
		if (!$t_ui->load(array('editor_type' => 100))) {
			$t_ui->setMode(ACCESS_WRITE);
			$t_ui->set('user_id', null);
			$t_ui->set('is_system_ui', 1);
			$t_ui->set('editor_type', 100);
			$t_ui->set('color', 'CC0000');
			$t_ui->set('editor_code', 'ui_screen_editor');
			$t_ui->insert();
			
			if ($t_ui->numErrors()) {
				return false;
			}
			
			$t_ui->addLabel(
				array('name' => 'UI Screen Editor'), $g_ui_locale_id, null, true
			);
			
			if ($t_ui->numErrors()) {
				return false;
			}
			$vn_ui_id = $t_ui->getPrimaryKey();
			
			$t_screen = new ca_editor_ui_screens();
			$t_screen->setMode(ACCESS_WRITE);
			$t_screen->set('ui_id', $vn_ui_id);
			$t_screen->set('idno', 'basic_'.$vn_ui_id);
			$t_screen->set('rank', 1);
			$t_screen->set('default', 1);
			$t_screen->insert();
			
			if ($t_screen->numErrors()) {
				return false;
			}
			
			$t_screen->addLabel(
				array('name' => 'Basic'), $g_ui_locale_id, null, true
			);
			
			if ($t_screen->numErrors()) {
				return false;
			}
			
			$vn_i = 1;
			foreach(array('preferred_labels', 'idno', 'color', 'is_default',  'ca_editor_ui_screen_type_restrictions', 'ca_editor_ui_bundle_placements') as $vs_bundle_name) {
				$t_screen->addPlacement($vs_bundle_name, $vs_bundle_name, array(), $vn_i);
				$vn_i++;
			}
		}
		
		
		return true;
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		
		return $this->render('widget_ui_info_html.php', true);
	}
	# -------------------------------------------------------
}