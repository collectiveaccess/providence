<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/representation_annotations/RepresentationAnnotationQuickAddController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2026 Whirl-i-Gig
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
require_once(__CA_MODELS_DIR__."/ca_representation_annotations.php");
require_once(__CA_LIB_DIR__."/BaseQuickAddController.php");

class RepresentationAnnotationQuickAddController extends BaseQuickAddController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_representation_annotations';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Form($values=null, $options=null) {
		$representation_id = $this->request->getParameter('representation_id', pInteger);
		$anno_id = $this->request->getParameter('annotation_id', pInteger);
		
		$t_annotation = new ca_representation_annotations();
		$type = $t_annotation->getAnnotationType($representation_id);
		
		parent::Form(null, array(
			'loadSubject' => true,
			'dontCheckQuickAddAction' => true,
			'forceSubjectValues' => ['representation_id' => $representation_id, 'type_code' => $type]
		));
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Save($options=null) {
		if (!($rc = parent::Save(array('loadSubject' => true, 'dontCheckQuickAddAction' => true)))) {
			$this->notification->addNotification(_t('Saved annotation.'), __NOTIFICATION_TYPE_INFO__);
		}
		return $rc;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected function _initView($options=null) {
		list($t_subject, $t_ui) = parent::_initView($options);
		$t_subject->loadProperties($options['forceSubjectValues']['type_code']);
		
		return array($t_subject, $t_ui);
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function deleteAnnotation() {
		$annotation_id = $this->request->getParameter('annotation_id', pInteger);
		
		$va_response = array('code' => 0, 'id' => $annotation_id, 'errors' => []);
		$t_annotation = new ca_representation_annotations();
		if ($t_annotation->load($annotation_id)) {
			$t_annotation->delete(true);
			if ($t_annotation->numErrors()) {
				$va_response = array(
					'code' => 10,
					'id' => $annotation_id, 
					'errors' => $t_annotation->getErrors()
				);
			}
		} else {
			$va_response = array(
				'code' => 10,
				'errors' => array(_t('Invalid annotation_id'))
			);
		}
		
		$this->view->setVar('response', $va_response);
		
		$this->response->setContentType('application/json');
		return $this->render('ajax_representation_annotation_delete_json.php');
	}
	# -------------------------------------------------------
}
