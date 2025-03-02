<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseRepresentationRelationship.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BaseRelationshipModel.php');
require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
class BaseRepresentationRelationship extends BaseRelationshipModel {
	# ------------------------------------------------------
	private function _getTarget() {
		$vs_target_table = ($this->getLeftTableName() == 'ca_object_representations') ? $this->getRightTableName() : $this->getLeftTableName();
		$vs_target_key = ($this->getLeftTableName() == 'ca_object_representations') ? $this->getRightTableFieldName() : $this->getLeftTableFieldName();
		
		return array($vs_target_table, $vs_target_key);
	}
	# ------------------------------------------------------
	/**
	 * Overrides get() to support primary representation filtering
	 *
	 * Options:
	 *		All supported by BaseModelWithAttributes::get() plus:
	 *		filterPrimaryRepresentations = Set filtering of primary representations in those models that support representations [Default is true]
	 *		filterNonPrimaryRepresentations = Set filtering of non-primary representations in those models that support representations [Default is true]
	 */
	public function get($ps_field, $pa_options=null) {
		
		if($this->_rowAsSearchResult) {
			if (method_exists($this->_rowAsSearchResult, "filterPrimaryRepresentations")) {
				$this->_rowAsSearchResult->filterPrimaryRepresentations(caGetOption('filterPrimaryRepresentations', $pa_options, false));
			}
			if (method_exists($this->_rowAsSearchResult, "filterNonPrimaryRepresentations")) {
				$this->_rowAsSearchResult->filterNonPrimaryRepresentations(caGetOption('filterNonPrimaryRepresentations', $pa_options, false));
			}
			return $this->_rowAsSearchResult->get($ps_field, $pa_options);
		}
		return parent::get($ps_field, $pa_options);
	}
	# ------------------------------------------------------
}
