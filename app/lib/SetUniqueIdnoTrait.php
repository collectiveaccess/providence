<?php
/** ---------------------------------------------------------------------
 * app/lib/SetUniqueIdnoTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
	trait SetUniqueIdnoTrait {
		# ------------------------------------------------------
		/** 
		 * Override addLabel() to set display_code if not specifically set by user
		 */
		public function addLabel($pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false, $pa_options=null) {
			if ($vn_rc = parent::addLabel($pa_label_values, $pn_locale_id, $pn_type_id, $pb_is_preferred, $pa_options)) {
				$this->_setUniqueIdno();
			}
			return $vn_rc;
		}
		# ------------------------------------------------------
		/**
		 * Override update() to set display_code if not specifically set by user
		 */
		public function update($pa_options=null) {
			$this->_setUniqueIdno(['noUpdate' => true]);
			return parent::update($pa_options);
		}
		# ------------------------------------------------------
		/** 
		 * 
		 * 
		 * @param array $pa_options Options include
		 *		noUpdate = Don't update row with newly generated identifier 
		 */
		private function _setUniqueIdno($pa_options=null) {
			if (!$this->getPrimaryKey()) { return null; }
		
			$vs_idno_fld = $this->getProperty('ID_NUMBERING_ID_FIELD');
			$vs_prefix = preg_replace("![^a-z0-9]+!", "_", strtolower($this->getProperty('NAME_SINGULAR')));
			
			if (!strlen(trim($this->get($vs_idno_fld)))) {
				if (!($vn_len = (is_array($va_len = $this->getFieldInfo($vs_idno_fld, 'BOUNDS_LENGTH')) && sizeof($va_len) > 1) ? (int)$va_len[1] : 0)) {
					$vn_len = 20;
				}
				
				$this->setMode(ACCESS_WRITE);
				if(!($vs_label = strtolower($this->getLabelForDisplay()))) { $vs_label = "{$vs_prefix}_".$this->getPrimaryKey(); }
				$vs_new_code = substr(preg_replace('![^a-z0-9]+!', '_', $vs_label), 0, $vn_len);
				if (call_user_func_array($this->tableName().'::find', [$x=array($vs_idno_fld => $vs_new_code), array('returnAs' => 'firstId')]) > 0) {
					$vs_new_code .= '_'.$this->getPrimaryKey();
				}
				$this->set($vs_idno_fld, $vs_new_code);
				
				if (isset($pa_options['noUpdate']) && (bool)$pa_options['noUpdate']) { return true; }
				return $this->update();
			}
			return false;
		}
		# ------------------------------------------------------
	}
