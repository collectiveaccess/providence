<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Service/CataloguingService.php
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
 * @package CollectiveAccess
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/ca/Service/BaseService.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");

class CataloguingService extends BaseService {
	# -------------------------------------------------------
	protected $opo_dm;
	# -------------------------------------------------------
	public function  __construct($po_request) {
		parent::__construct($po_request);

		$this->opo_dm = Datamodel::load();
	}
	# -------------------------------------------------------
	/**
	 * Adds item of type “type” to the database using the data in the
	 * associative array $fieldInfo to populate fields.
	 * 
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param array $fieldInfo associative array that contains the data (field_name => data)
	 * @return int primary key of the new record
	 * @throws SoapFault
	 */
	public function add($type, $fieldInfo){
		$t_subject_instance = $this->getTableInstance($type);
		if(is_array($fieldInfo)){
			foreach($fieldInfo as $vs_key => $vs_val){
				if(!$t_subject_instance->hasField($vs_key)){
					throw new SoapFault("Server", "Field {$vs_key} is invalid for table {$type}");
				} else {
					$t_subject_instance->set($vs_key,$vs_val);
				}
			}
			$t_subject_instance->insert();
			if($t_subject_instance->numErrors()==0){
				return $t_subject_instance->getPrimaryKey();
			} else {
				throw new SoapFault("Server", "There were errors while inserting the new record: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			return 0;
		}
	}
	# -------------------------------------------------------
	/**
	 * Updates the specified item with data in the $fieldInfo
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param array $fieldInfo associative array that contains the data (field_name => data)
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function update($type, $item_id, $fieldInfo){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if(is_array($fieldInfo)){
			foreach($fieldInfo as $vs_key => $vs_val){
				if(!$t_subject_instance->hasField($vs_key)){
					throw new SoapFault("Server", "Field {$vs_key} is invalid for table {$type}");
				} else {
					$t_subject_instance->set($vs_key,$vs_val);
				}
			}
			$vb_success = $t_subject_instance->update();
			if($vb_success && $t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while updating the record: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "fieldInfo is not an array!");
		}
	}
	# -------------------------------------------------------
	/**
	 * Removes specified item from the database
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function remove($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		$vb_success = $t_subject_instance->delete(true);
		if($vb_success && $t_subject_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while deleting the record: ".join(";",$t_subject_instance->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Adds an attribute to the specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param string $attribute_code_or_id
	 * @param array $attribute_data_array
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function addAttribute($type, $item_id, $attribute_code_or_id, $attribute_data_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->addAttribute($attribute_data_array, $attribute_code_or_id);
			$t_subject_instance->update();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while adding the attribute: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Adds several attributes to the specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param array $attribute_list_array
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function addAttributes($type, $item_id, $attribute_list_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			foreach($attribute_list_array as $vs_attribute_code_or_id => $va_attr_data){
				$t_subject_instance->addAttribute($va_attr_data, $vs_attribute_code_or_id);
			}
			$t_subject_instance->update();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while adding an attribute: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Edits existing attribute specified by given attribute_id
	 * 
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param int $attribute_id attribute to edit
	 * @param string $attribute_code_or_id
	 * @param array $attribute_data_array
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function editAttribute($type, $item_id, $attribute_id, $attribute_code_or_id, $attribute_data_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->editAttribute($attribute_id, $attribute_code_or_id, $attribute_data_array);
			$t_subject_instance->update();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while editing the attribute: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Replaces existing attributes attached to specified item
	 * (i.e. deletes all existing attributes with specified attribute_code_or_id and adds new attribute)
	 * 
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param string $attribute_code_or_id
	 * @param array $attribute_data_array
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function replaceAttribute($type, $item_id, $attribute_code_or_id, $attribute_data_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->removeAttributes($attribute_code_or_id);
			$t_subject_instance->update();
			if($t_subject_instance->numErrors()==0){
				$this->addAttribute($type, $item_id, $attribute_code_or_id, $attribute_data_array);
				$t_subject_instance->update();
				if($t_subject_instance->numErrors()==0){
					return true;
				} else {
					throw new SoapFault("Server", "Adding new attribute failed: ".join(";",$t_subject_instance->getErrors()));
				}
			} else {
				throw new SoapFault("Server", "There were errors while removing an attribute: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Updates several attributes attached to the specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param array $attribute_list_array
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function updateAttributes($type, $item_id, $attribute_list_array){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			if(is_array($attribute_list_array)){
				foreach($attribute_list_array as $vs_attribute_code_or_id => $va_data){
					$t_subject_instance->removeAttributes($vs_attribute_code_or_id);
					$t_subject_instance->addAttribute($va_data, $vs_attribute_code_or_id);
				}
				$t_subject_instance->update();
				if($t_subject_instance->numErrors()==0){
					return true;
				} else {
					throw new SoapFault("Server", "There were errors while modifying attribute: ".join(";",$t_subject_instance->getErrors()));
				}
			} else {
				throw new SoapFault("Server", "attribute_list_array must be an array");
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove attribute from specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param int $attribute_id attribute to delete
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeAttribute($type, $item_id, $attribute_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->removeAttribute($attribute_id);
			$t_subject_instance->update();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while deleting attribute: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove all attributes with given element code from specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param int $attribute_code_or_id element code of the attribute(s) to remove
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function removeAttributes($type, $item_id, $attribute_code_or_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->removeAttributes($attribute_code_or_id);
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while deleting attributes: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove all attributes from specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @return boolean success state
	 * @throws SoapFault
	 */
	public function removeAllAttributes($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof BaseModelWithAttributes){
			$t_subject_instance->removeAttributes();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while deleting attributes: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take attributes");
		}
	}
	# -------------------------------------------------------
	/**
	 * Add label to specified item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param array $label_data_array
	 * @param boolean $is_preferred
	 * @return int the identifier of the new label
	 * @throws SoapFault
	 */
	public function addLabel($type, $item_id, $label_data_array, $localeID, $is_preferred){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			$vn_id = $t_subject_instance->addLabel($label_data_array, $localeID, null, $is_preferred);
			if($t_subject_instance->numErrors()==0 && intval($vn_id) > 0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while adding the label: ".join(";",$t_subject_instance->getErrors()));
			}
			return $vn_id;
		} else {
			throw new SoapFault("Server", "This type can't take labels");
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove specified label from item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param int $label_id primary key identifier of the label to delete
	 * @return boolean
	 */
	public function removeLabel($type, $item_id, $label_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			$t_subject_instance->removeLabel($label_id);
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while removing the label: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take labels");
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove all labels from item
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeAllLabels($type, $item_id){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			$t_subject_instance->removeAllLabels();
			if($t_subject_instance->numErrors()==0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while removing a label: ".join(";",$t_subject_instance->getErrors()));
			}
		} else {
			throw new SoapFault("Server", "This type can't take labels");
		}
	}
	# -------------------------------------------------------
	/**
	 * Edit existing label
	 * 
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param int $label_id primary key of the label to edit
	 * @param array $label_data_array
	 * @param int $localeID
	 * @param boolean $is_preferred
	 * @return int primary key of the label 
	 */
	public function editLabel($type, $item_id, $label_id, $label_data_array, $localeID, $is_preferred){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid type or item_id");
		}
		if($t_subject_instance instanceof LabelableBaseModelWithAttributes){
			$vn_id = $t_subject_instance->editLabel($label_id, $label_data_array, $localeID, null, $is_preferred);
			if($t_subject_instance->numErrors()==0 && intval($vn_id) > 0){
				return true;
			} else {
				throw new SoapFault("Server", "There were errors while adding the label: ".join(";",$t_subject_instance->getErrors()));
			}
			return $vn_id;
		} else {
			throw new SoapFault("Server", "This type can't take labels");
		}
	}
	# -------------------------------------------------------
	/**
	 * Add object representation to specified object
	 *
	 * @param int $object_id
	 * @param string $media_url
	 * @param int $type_id
	 * @param int $locale_id
	 * @param int $status
	 * @param int $access
	 * @param int $is_primary
	 * @return bool success state
	 */
	public function addObjectRepresentation($object_id, $media_url, $type_id, $locale_id, $status, $access, $is_primary){
		if(!($t_subject_instance = $this->getTableInstance("ca_objects",$object_id))){
			throw new SoapFault("Server", "Invalid object_id");
		}

		// This is kinda C-ish ... maybe there is a better way to copy URL content to a local file?
		$vr_src_file = fopen($media_url, "r");
		$vs_tmpfile = __CA_APP_DIR__."/tmp/soap_cataloguing_service_".md5($media_url);
		$vr_dst_file = fopen($vs_tmpfile,"w+");
		while (!feof($vr_src_file)) {
			$vs_buffer = fgets($vr_src_file, 4096);
			fwrite($vr_dst_file,$vs_buffer);
		}
		fclose($vr_src_file);
		fclose($vr_dst_file);

		$vn_id = $t_subject_instance->addRepresentation($vs_tmpfile, $type_id, $locale_id, $status, $access, $is_primary);
		@unlink($vs_tmpfile);
		if($t_subject_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while adding the representation: ".join(";",$t_subject_instance->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove representation from database
	 *
	 * @param int $representation_id
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeObjectRepresentation($representation_id){
		require_once(__CA_MODELS_DIR__."/ca_object_representations.php");
		$t_rep = new ca_object_representations();
		if(!$t_rep->load($representation_id)){
			throw new SoapFault("Server", "Invalid representation ID");
		}
		$t_rep->delete();
		if($t_rep->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while removing the representation: ".join(";",$t_rep->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Add relationship between items
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param int $item_id primary key identifier of the record to update
	 * @param string $related_type name of the table storing the record you want to relate to
	 * @param int $related_item_id
	 * @param int $relationship_type_id
	 * @param string $source_info
	 * @return boolean
	 * @throws SoapFault
	 */
	public function addRelationship($type, $item_id, $related_type, $related_item_id, $relationship_type_id, $source_info){
		if(!($t_subject_instance = $this->getTableInstance($type,$item_id))){
			throw new SoapFault("Server", "Invalid object_id");
		}
		$vn_return = $t_subject_instance->addRelationship($related_type, $related_item_id, $relationship_type_id, null, $source_info);
		if($vn_return && $t_subject_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while adding the relationship: ".join(";",$t_subject_instance->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Update existing relationship between items
	 *
	 * @param string $type can be one of: [ca_objects, ca_entities, ca_places, ca_occurrences, ca_collections, ca_list_items]
	 * @param string $related_type
	 * @param int $relation_id
	 * @param int $relationship_type_id
	 * @param string $source_info
	 * @return boolean
	 * @throws SoapFault
	 */
	public function updateRelationship($type, $related_type, $relation_id, $relationship_type_id, $source_info){
		$t_rel_instance = $this->getRelTableInstance($type, $related_type, $relation_id);
		$t_rel_instance->set("type_id",$relationship_type_id);
		$t_rel_instance->set("source_info",$source_info);
		$t_rel_instance->update();
		if($t_rel_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while modifying the relationship: ".join(";",$t_rel_instance->getErrors()));
		}
	}
	# -------------------------------------------------------
	/**
	 * Remove relationship between items from database
	 *
	 * @param string $type
	 * @param string $related_type
	 * @param int $relation_id
	 * @return boolean
	 * @throws SoapFault
	 */
	public function removeRelationship($type, $related_type, $relation_id){
		$t_instance = $this->getRelTableInstance($type, $related_type, $relation_id);
		$t_rel_instance->delete();
		if($t_rel_instance->numErrors()==0){
			return true;
		} else {
			throw new SoapFault("Server", "There were errors while deleting the relationship: ".join(";",$t_rel_instance->getErrors()));
		}
	}	
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getTableInstance($ps_type,$pn_type_id_to_load=null){
		if(!in_array($ps_type, array("ca_objects", "ca_entities", "ca_places", "ca_occurrences", "ca_collections", "ca_list_items", "ca_object_representations", "ca_storage_locations", "ca_movements", "ca_loans", "ca_tours", "ca_tour_stops", "ca_sets", "ca_set_items"))){
			throw new SoapFault("Server", "Invalid type or item_id");
		} else {
			require_once(__CA_MODELS_DIR__."/{$ps_type}.php");
			$t_instance = new $ps_type();
			if($pn_type_id_to_load){
				if(!$t_instance->load($pn_type_id_to_load)){
					return false;
				} else {
					$t_instance->setMode(ACCESS_WRITE);
					return $t_instance;
				}
			} else {
				$t_instance->setMode(ACCESS_WRITE);
				return $t_instance;
			}
		}
	}
	# -------------------------------------------------------
	private function getRelTableInstance($ps_left_table,$ps_right_table,$pn_relation_id){
		$va_relationships = $this->opo_dm->getPath($ps_left_table, $ps_right_table);
		unset($va_relationships[$ps_left_table]);
		unset($va_relationships[$ps_right_table]);
		if(sizeof($va_relationships)==1){
			foreach($va_relationships as $vs_table_name => $vs_table_num){
				$vs_table = $vs_table_name;
			}
			$t_rel_instance = $this->opo_dm->getTableInstance($vs_table);
			if(!$t_rel_instance->load($pn_relation_id)){
				throw new SoapFault("Server", "Invalid relation ID");
			} else {
				$t_rel_instance->setMode(ACCESS_WRITE);
				return $t_rel_instance;
			}
		} else {
			throw new SoapFault("Server", "There is no applicable path from {$ps_left_table} to {$ps_right_table}");
		}
	}
	# -------------------------------------------------------
}