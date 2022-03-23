<?php
/** ---------------------------------------------------------------------
 * app/lib/MappingManagerController.php.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');

class MappingManagerController extends ActionController {
	# -------------------------------------------------------
	protected $opo_app_plugin_manager;
	protected $opo_result_context;
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	
 			AssetLoadManager::register('react');
 			
		if (!$po_request->user->canDoAction('can_batch_import_metadata')) {
			$po_response->setRedirect($po_request->config->get('error_display_url').'/n/3400?r='.urlencode($po_request->getFullUrlPath()));
			return;
		}
	
	}
	# -------------------------------------------------------
	/**
	 * List 
	 *
	 */
	public function Index($pa_values=null, $pa_options=null) {
		$this->view->setVar('key', GraphQLServices\GraphQLServiceController::encodeJWTRefresh(['id' => $this->request->user->getPrimaryKey()]));
			
		$this->render('mappingmanager/index_html.php');
	}
	# ------------------------------------------------------------------
	# Sidebar info handler
	# ------------------------------------------------------------------
	/**
	 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
	 *
	 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
	 */
	public function info($pa_parameters) {
		$t_importer = $this->getImporterInstance(false);
		$this->view->setVar('t_item', $t_importer);
		$this->view->setVar('result_context', $this->opo_result_context);
		$this->view->setVar('screen', $this->request->getActionExtra());	
	
		return $this->render('mappingmanager/widget_mapping_manager_iinfo_html.php', true);
	}
	# ------------------------------------------------------------------
}
