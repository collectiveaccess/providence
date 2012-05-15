<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/BundleMappingsController.php :
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
 
	require_once(__CA_MODELS_DIR__.'/ca_bundle_mappings.php');
	require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_labels.php');
	require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_groups.php');
	require_once(__CA_MODELS_DIR__.'/ca_bundle_mapping_rules.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/BaseEditorController.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');

class BundleMappingsController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_bundle_mappings';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	/**
	 *
	 */
	public function ListMappings(){
		JavascriptLoadManager::register('tableList');
		
		$vo_dm = Datamodel::load();
		$va_mappings = ca_bundle_mappings::getMappingList(null);
		$t_mapping = new ca_bundle_mappings();
		foreach($va_mappings as $vs_key => $va_mapping){
			$t_instance = $vo_dm->getInstanceByTableNum($va_mapping['table_num'], true);
			$va_mappings[$vs_key]['type'] = $t_instance->getProperty('NAME_PLURAL');
			$va_mappings[$vs_key]['directionForDisplay'] = $t_mapping->getChoiceListValue('direction', $va_mapping['direction']);
		}
		$this->view->setVar('mapping_list',$va_mappings);
		
		$o_result_context = new ResultContext($this->request, $this->ops_table_name, 'basic_search');
		$o_result_context->setResultList(array_keys($va_mappings));
		$o_result_context->setAsLastFind();
		$o_result_context->saveContext();
		
 		$this->view->setVar('table_list', caFilterTableList($t_mapping->getFieldInfo('table_num', 'BOUNDS_CHOICE_LIST')));
 		$this->view->setVar('format_list', $t_mapping->getFieldInfo('target', 'BOUNDS_CHOICE_LIST'));
 		$this->view->setVar('direction_list', $t_mapping->getFieldInfo('direction', 'BOUNDS_CHOICE_LIST'));
		
		return $this->render('mapping_list_html.php');
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		
		return $this->render('widget_mapping_info_html.php', true);
	}
	# -------------------------------------------------------
}