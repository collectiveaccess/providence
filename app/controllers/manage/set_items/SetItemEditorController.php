<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SetItemEditorController.php : 
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
require_once(__CA_MODELS_DIR__."/ca_sets.php");
require_once(__CA_MODELS_DIR__."/ca_set_items.php");
require_once(__CA_LIB_DIR__."/BaseEditorController.php");

class SetItemEditorController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_set_items';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $view_paths=null) {
		parent::__construct($po_request, $po_response, $view_paths);
		
		// check access to set - if user doesn't have edit access we bail
		$t_item = new ca_set_items($po_request->getParameter('item_id', pInteger));
		$t_set = new ca_sets($t_item->get('set_id'));
		if (!$t_set->haveAccessToSet($po_request->getUserID(), __CA_SET_EDIT_ACCESS__, $t_item->get('set_id'))) {
			$this->postError(2320, _t("Access denied"), "SetItemEditorController->__construct()");
		}
	}
	# -------------------------------------------------------
	public function Edit($values=null, $options=null) {
		AssetLoadManager::register('panel');
		AssetLoadManager::register('imageScroller');
		AssetLoadManager::register('bundleListEditorUI');
		$ret = parent::Edit($values, $options);
		
		$t_subject = $this->view->getVar("t_subject");
		$t_set = new ca_sets($set_id = $t_subject->get('set_id'));
		$items = $t_set->getItems(array('user_id' => $this->request->getUserID()));
		
		$this->opo_result_context = new ResultContext($this->request, 'ca_set_items', 'set_item_edit');
		$this->opo_result_context->setResultList(is_array($items) ? array_keys($items) : array());
		$this->opo_result_context->setParameter('set_id', $set_id);
		$this->opo_result_context->setAsLastFind();
		$this->opo_result_context->saveContext();
		
		return $ret;
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function Info($parameters) {
		parent::info($parameters);
		$item_id = (isset($parameters['item_id'])) ? $parameters['item_id'] : null;
		
		$t_set_item = new ca_set_items($item_id);
		$this->view->setVar('t_set', $t_set = new ca_sets($t_set_item->get('set_id')));
			
		if ($t_set_item->getPrimaryKey()) {
			
			$t_row_instance = Datamodel::getInstanceByTableNum($t_set->get('table_num'), true);
			$t_row_instance->load($t_set_item->get('row_id'));
			
			$this->view->setVar('t_row_instance', $t_row_instance);
		}	
		
		return $this->render('widget_set_item_info_html.php', true);
	}
	# -------------------------------------------------------
}