<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/HierarchyToolsController.php : 
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
 * ----------------------------------------------------------------------
 */
 

class HierarchyToolsController extends ActionController {
	# -------------------------------------------------------
	/**
	 * Name of "subject" table (what we're editing)
	 */
	protected $table_name = null;
	
	/**
	 *
	 */
	protected $subject = null;
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
		
		$this->table_name = $request->getParameter('t', pString);
		if(!$this->table_name || !($this->subject = Datamodel::getInstance($this->table_name, true))) {
			throw new ApplicationException(_t('Table does not exist'));
		}
	}
	# -------------------------------------------------------
	/**
	 * Set media from selected sub-item as media for hierarchy root. Used in "album" 
	 * configurations where the root serves only to group together many "item" sub-records.
	 */
	public function setRootMedia() {
		$id = $this->request->getParameter('id', pString);	// id of item to set as root media
		if(!$id) {
			throw new ApplicationException(_t('ID is not defined'));
		}
		if(!$this->subject->load($id)) {
			throw new ApplicationException(_t('ID does not exist'));
		}
		if(!($parent_id = $this->subject->get('parent_id'))) {
			throw new ApplicationException(_t('Target is not a child record'));
		}
		if(!($t_parent = Datamodel::getInstance($this->table_name, false, $parent_id))) {
			throw new ApplicationException(_t('Parent does not exist'));
		}
		if(!$this->subject->isSaveable($this->request) || !$t_parent->isSaveable($this->request)) {
			throw new ApplicationException(_t('Access denied'));
		}
		$rep_ids = $this->subject->get('ca_object_representations.representation_id', ['returnAsArray' => true]);
		if(!is_array($rep_ids) || !sizeof($rep_ids)) {
			throw new ApplicationException(_t('ID has no associated media'));
		}
		$t_parent->removeRelationships('ca_object_representations');
		
		// TODO: how to derive relationship type when managing non-object "albums"?
		if($t_parent->addRelationship('ca_object_representations', $rep_ids[0], null)) {
			$resp = ['ok' => true, 'errors' => [], 'message' => _t('Updated media')];
		} else {
			$resp = ['ok' => false, 'errors' => $t_parent->getErrors(),'message' => _t('Could not update media: %1', join('; ', $t_parent->getErrors()))];
		}
		
		$this->view->setVar('response', $resp);
		$this->render('generic/hierarchy_tools_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Remove media from hierarchy and make standlone
	 */
	public function removeItems() {
		$ids = $this->request->getParameter('ids', pArray);	// list of ids to remove
		
		if(!is_array($ids) || !sizeof($ids)) {
			throw new ApplicationException(_t('ID list is empty'));
		}
		
		$c = 0;
		$errors = [];
		foreach($ids as $id) {
			$id = (int)$id;
			
			if(!$this->subject->load($id)) {
				continue;
			}
			if(!$this->subject->isSaveable($this->request)) {
				throw new ApplicationException(_t('Access denied'));
			}
			if(!$this->subject->get('parent_id')) {
				throw new ApplicationException(_t('Target is not a child record'));	
			}
			$this->subject->set('parent_id', null);
			$this->subject->update();
			
			if($this->subject->numErrors() > 0) {
				$errors[] = join('; ', $this->subject->getErrors());
				continue;
			}
			$c++;
		}
		$resp = ['ok' => ($c > 0), 'errors' => $errors, 'message' => _t('Removed %1 items', $c)];
		
		$this->view->setVar('response', $resp);
		$this->render('generic/hierarchy_tools_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Transfer items to a different hierarchy
	 */
	public function transferItems() {
		$parent_id = $this->request->getParameter('id', pString);	// id of item to move items under
		if(!$parent_id) {
			throw new ApplicationException(_t('ID is not defined'));
		}
		if(!$this->subject->load($parent_id)) {
			throw new ApplicationException(_t('ID does not exist'));
		}
		if($this->subject->get('parent_id')) {
			throw new ApplicationException(_t('Target is a child record; must be root'));
		}
		if(!$this->subject->isSaveable($this->request)) {
			throw new ApplicationException(_t('Access denied'));
		}
		$ids = $this->request->getParameter('ids', pArray);	// list of ids to remove
		
		if(!is_array($ids) || !sizeof($ids)) {
			throw new ApplicationException(_t('ID list is empty'));
		}
		
		$c = 0;
		$errors = [];
		foreach($ids as $id) {
			$id = (int)$id;
			
			if(!$this->subject->load($id)) {
				continue;
			}
			if(!$this->subject->isSaveable($this->request)) {
				throw new ApplicationException(_t('Access denied'));
			}
			
			// TODO: Do we need to check this?
			// if(!$this->subject->get('parent_id')) {
// 				throw new ApplicationException(_t('Target is not a child record'));	
// 			}
			$this->subject->set('parent_id', $parent_id);
			$this->subject->update();
			
			if($this->subject->numErrors() > 0) {
				$errors[] = join('; ', $this->subject->getErrors());
				continue;
			}
			$c++;
		}
		$resp = ['ok' => ($c > 0), 'errors' => $errors, 'count' => $c, 'message' => _t('Moved %1 items', $c)];
		
		$this->view->setVar('response', $resp);
		$this->render('generic/hierarchy_tools_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Create new hierarchy with items
	 */
	public function createWith() {
		$ids = $this->request->getParameter('ids', pArray);	// list of ids to move
		$name = $this->request->getParameter('name', pString);
		if(!is_array($ids) || !sizeof($ids)) {
			throw new ApplicationException(_t('ID list is empty'));
		}
		
		if(!$name) {		// TODO: does name already exist?
			throw new ApplicationException(_t('Name is empty'));
		}
		
		// check ids
		$filtered_ids = [];
		foreach($ids as $id) {
			$id = (int)$id;
			
			if(!$this->subject->load($id)) {
				continue;
			}
			if(!$this->subject->isSaveable($this->request)) {
				//throw new ApplicationException(_t('Access denied'));
				continue;
			}
			//if($this->subject->get('parent_id')) {
				//throw new ApplicationException(_t('Target is not a child record'));	
			//	continue;
			//}
			$filtered_ids[] = $id;
		}
		if(sizeof($filtered_ids) === 0) {
			throw new ApplicationException(_t('Nothing to add'));		
		}
		
		$this->subject = Datamodel::getInstance($this->table_name);
		$this->subject->set('type_id', 'album'); // TODO: make configurable
		$this->subject->setIdnoWithTemplate(date('Y').'.%'); // TODO: make configurable
		$this->subject->insert();
		
		if(!($parent_id = $this->subject->getPrimaryKey())) {
			throw new ApplicationException(_t('Cannot create new album'));
		}
		
		$this->subject->addLabel(['name' => $name], ca_locales::getDefaultCataloguingLocaleID(), null, true);
		
		$c = 0;
		$errors = [];
		foreach($filtered_ids as $id) {
			$id = (int)$id;
			
			if(!$this->subject->load($id)) {
				continue;
			}
			$this->subject->set('parent_id', $parent_id);
			$this->subject->update();
			
			if($this->subject->numErrors() > 0) {
				$errors[] = join('; ', $this->subject->getErrors());
				continue;
			}
			$c++;
		}
		$resp = ['ok' => ($c > 0), 'errors' => $errors, 'name' => $name, 'count' => $c, 'message' => _t('Added %1 items into new album', $c)];
		
		$this->view->setVar('response', $resp);
		$this->render('generic/hierarchy_tools_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Download selected media
	 */
	public function downloadMedia() {
		$ids = explode(';', $this->request->getParameter('ids', pString));	// list of ids to remove
		$selected_version = $this->request->getParameter('version', pString);
		
		if(!is_array($ids) || !sizeof($ids)) {
			throw new ApplicationException(_t('ID list is empty'));
		}
		
		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
		
		$c = 0;
		$errors = [];
		
		$o_zip = new ZipStream();
		foreach($ids as $id) {
			$id = (int)$id;
			
			if(!$this->subject->load($id)) {
				continue;
			}
			if(!$this->subject->isReadable($this->request)) {
				throw new ApplicationException(_t('Access denied'));
			}
			
			$version_list = ['full', 'original', 'h264_hi', 'mp3', 'page', 'large'];
			if($selected_version) { 
				array_unshift($version_list, $selected_version);
			}
			
			$reps = $this->subject->getRepresentations($version_list);
			foreach($reps as $rep) {
				$path = $version = null;
				foreach($version_list as $v) {
					if(isset($rep['paths'][$v])) {
						$path = $rep['paths'][$v];
						$version = $v;
						break;
					}
				}
				if($path) {
					$name = caGetRepresentationDownloadFileName($this->subject->tableName(), ['idno' => $this->subject->get('idno'), 'index' => $c, 'version' => $version, 'extension' => pathinfo($path, PATHINFO_EXTENSION), 'original_filename' => $rep['original_filename'], 'representation_id' => $rep['representation_id']]);
					$o_zip->addFile($path, $name);
					$c++;
				}
			}
		}
		
		if($c > 0) {
			$o_view->setVar('zip_stream', $o_zip);
			$o_view->setVar('archive_name', caGetMediaDownloadArchiveName($this->subject->tableName(), $id[0], ['extension' => 'zip']));
		
			$this->response->addContent($o_view->render('download_file_binary.php'));
		} else {
			$this->response->setHTTPResponseCode(204, _t('No files to download'));
		}
	}
	# -------------------------------------------------------
}
