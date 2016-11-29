<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/AttributeValue.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage Sync
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\Sync\LogEntry;

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Base.php');
require_once(__CA_MODELS_DIR__.'/ca_metadata_elements.php');
require_once(__CA_MODELS_DIR__.'/ca_attributes.php');

class AttributeValue extends Base {

	public function isRelevant() {
		if((!$this->getModelInstance()->loadByGUID($this->getGUID())) && $this->isUpdate()) {
			return false;
		}

		return parent::isRelevant();
	}

	public function sanityCheck() {
		parent::sanityCheck();
		$va_snapshot = $this->getSnapshot();

		// check if element code is valid
		if (isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])) {
			if (!($vn_element_id = \ca_metadata_elements::getElementID($vs_element_code))) {
				throw new InvalidLogEntryException(_t("Could not find element with code %1", $vs_element_code));
			}
		} else {
			throw new InvalidLogEntryException(_t("No element code found in attribute value log entry"));
		}

		// check if attribute guid is present and valid
		if (isset($va_snapshot['attribute_guid']) && ($vs_attribute_guid = $va_snapshot['attribute_guid'])) {
			$t_attr = new \ca_attributes();
			if($this->isUpdate() && !$t_attr->loadByGUID($vs_attribute_guid)) {
				throw new InvalidLogEntryException(_t("Could not find attribute with guid %1", $vs_attribute_guid));
			}
		} else {
			throw new InvalidLogEntryException(_t("No attribute_guid found for attribute value log entry"));
		}

		// if item_id is present, check if it's valid
		if (isset($va_snapshot['item_id']) && ($vs_item_id = $va_snapshot['item_id'])) {
			if (
				isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])
			) {
				$t_element = \ca_metadata_elements::getInstance($vs_element_code);

				if($vn_list_id = $t_element->get('list_id')) {
					if(isset($va_snapshot['item_code']) && ($vs_item_code = $va_snapshot['item_code'])) {
						if(!($vn_item_id = caGetListItemID($vn_list_id, $vs_item_code))) {
							throw new InvalidLogEntryException(_t("Invalid list item code %1 for attribute value log entry.", $vs_item_code));
						}
					} elseif(isset($va_snapshot['item_label']) && ($vs_item_label = $va_snapshot['item_label'])) {
						if(!($vn_item_id = caGetListItemIDForLabel($vn_list_id, $vs_item_label))) {
							throw new InvalidLogEntryException(_t("Invalid list item label %1 for attribute value log entry.", $vs_item_label));
						}
					} else {
						throw new InvalidLogEntryException(("No item code or label for list attribute value"));
					}
				} else {
					throw new InvalidLogEntryException(_t("Referenced element is not of type List, but item_id is set in the snapshot"));
				}
			}
		}


	}

	public function apply(array $pa_options = array()) {
		$this->setIntrinsicsFromSnapshotInModelInstance();

		if($this->isInsert()) {
			$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
		} elseif($this->isUpdate()) {
			$this->getModelInstance()->update();
		} elseif($this->isDelete()) {
			$this->getModelInstance()->delete(false);
		}

		$this->checkModelInstanceForErrors();
	}

	public function setIntrinsicsFromSnapshotInModelInstance() {
		parent::setIntrinsicsFromSnapshotInModelInstance();
		$va_snapshot = $this->getSnapshot();

		foreach($va_snapshot as $vs_field => $vm_val) {

			if($vs_field == 'element_id') {
				if (isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])) {
					if ($vn_element_id = \ca_metadata_elements::getElementID($vs_element_code)) {
						$this->getModelInstance()->set('element_id', $vn_element_id);
					}
				}
			} elseif($vs_field == 'value_blob') {
				$o_app_vars = new \ApplicationVars();
				$va_files = $o_app_vars->getVar('pushMediaFiles');
				if(isset($va_files[$va_snapshot[$vs_field]])) {
					$this->getModelInstance()->useBlobAsMediaField(true);
					$this->getModelInstance()->set('value_blob', $va_files[$va_snapshot[$vs_field]]);
				}
			} elseif($vs_field == 'attribute_id') {
				if (isset($va_snapshot['attribute_guid']) && ($vs_attribute_guid = $va_snapshot['attribute_guid'])) {
					$t_attr = new \ca_attributes();
					if($t_attr->loadByGUID($vs_attribute_guid)) {
						$this->getModelInstance()->set('attribute_id', $t_attr->getPrimaryKey());
					}
				}
			} elseif($vs_field == 'item_id') {
				if (
					isset($va_snapshot['element_code']) && ($vs_element_code = $va_snapshot['element_code'])
				) {
					$t_element = \ca_metadata_elements::getInstance($vs_element_code);

					if($vn_list_id = $t_element->get('list_id')) {
						if(isset($va_snapshot['item_code']) && ($vs_item_code = $va_snapshot['item_code'])) {
							if($vn_item_id = caGetListItemID($vn_list_id, $vs_item_code)) {
								$this->getModelInstance()->set('item_id', $vn_item_id);
							}
						} elseif(isset($va_snapshot['item_label']) && ($vs_item_label = $va_snapshot['item_label'])) {
							if($vn_item_id = caGetListItemIDForLabel($vn_list_id, $vs_item_label)) {
								$this->getModelInstance()->set('item_id', $vn_item_id);
							}
						}
					}
				}
			}
		}
	}

}
