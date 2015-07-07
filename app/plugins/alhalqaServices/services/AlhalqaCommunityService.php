<?php
/** ---------------------------------------------------------------------
 * app/plugins/alhalqaServices/services/AlhalqaCommunityService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
  
require_once(__CA_LIB_DIR__.'/ca/Service/BaseJSONService.php');
require_once(__CA_MODELS_DIR__.'/ca_entities.php');

class AlhalqaCommunityService extends BaseJSONService {

	/**
	 * @var string
	 */
	private $ops_endpoint = null;

	public function __construct($po_request,$ps_endpoint) {
		$this->ops_endpoint = $ps_endpoint;

		parent::__construct($po_request, 'ca_entities');

	}
	# -------------------------------------------------------
	/**
	 * @return string
	 */
	public function getEndpoint() {
		return $this->ops_endpoint;
	}
	# -------------------------------------------------------
	public function dispatch() {
		$va_post = $this->getRequestBodyArray();

		$vm_return = array();
		switch($this->getRequestMethod()) {
			case "GET":
			case "POST": // create new users and create new "likes"
				switch($this->getEndpoint()) {
					case 'newUser': // new user
						$vm_return = $this->newUser($va_post);
						break;
					case 'like':
						$vm_return = $this->like($va_post);
						break;
					case 'unlike':
						$vm_return = $this->unlike($va_post);
						break;
					default:
						$this->addError('Unknown endpoint');
						break;
				}
				break;
			default:
				$this->addError(_t("Invalid HTTP request method for this service"));
				$vm_return = false;
		}

		return $vm_return;
	}
	# -------------------------------------------------------
	/**
	 * Create new entity with given user name and return id
	 * @param array $pa_data
	 * @return int|bool
	 */
	protected function newUser($pa_data) {
		if(!is_array($pa_data) || !isset($pa_data['username'])) {
			$this->addError("Malformed request body");
			return false;
		}

		$ps_username = $pa_data['username'];

		if(ca_entities::find(array(
			'preferred_labels' => array('surname' => $ps_username),
		), array('returnAs' => 'firstId'))) {
			$this->addError('Username is taken');
			return false;
		}

		$vn_new_entity_id = DataMigrationUtils::getEntityID(array('surname' => $ps_username), 'real_person', 1, array('idno' => 'public_'.time()));

		if(!$vn_new_entity_id) {
			return false;
		}

		return array('entity_id' => $vn_new_entity_id);
	}
	# -------------------------------------------------------
	/**
	 * Add like to object
	 * @param array $pa_data
	 * @return bool
	 */
	protected function like($pa_data) {
		if(!is_array($pa_data) || !isset($pa_data['object_id']) || !isset($pa_data['entity_id'])) {
			$this->addError("Malformed request body");
			return false;
		}

		$vn_object_id = (int)$pa_data['object_id'];
		$t_object = new ca_objects($vn_object_id);

		if(!$t_object->getPrimaryKey()) {
			$this->addError("Invalid object id");
			return false;
		}

		$vn_entity_id = (int)$pa_data['entity_id'];
		$t_entity = new ca_entities($vn_entity_id);

		if(!$t_entity->getPrimaryKey()) {
			$this->addError("Invalid entity id");
			return false;
		}

		$o_db = new Db();

		$qr_likes = $o_db->query("
			SELECT * FROM ca_item_comments WHERE name = ? AND table_num = ? AND row_id = ?
		", $vn_entity_id, $t_object->tableNum(), $t_object->getPrimaryKey());

		if(!$qr_likes || ($qr_likes->numRows() > 0)) {
			$this->addError('like ignored, possible duplicate?');
			return false;
		}

		$t_object->addComment('like', 1, null, null, $vn_entity_id);

		return array('msg' => 'like successfully added');
	}
	# -------------------------------------------------------
	/**
	 * Remove all likes from object (for one user)
	 * @param array $pa_data
	 * @return bool
	 */
	protected function unlike($pa_data) {
		if(!is_array($pa_data) || !isset($pa_data['object_id']) || !isset($pa_data['entity_id'])) {
			$this->addError("Malformed request body");
			return false;
		}

		$vn_object_id = (int)$pa_data['object_id'];
		$t_object = new ca_objects($vn_object_id);

		if(!$t_object->getPrimaryKey()) {
			$this->addError("Invalid object id");
			return false;
		}

		$vn_entity_id = (int)$pa_data['entity_id'];
		$t_entity = new ca_entities($vn_entity_id);

		if(!$t_entity->getPrimaryKey()) {
			$this->addError("Invalid entity id");
			return false;
		}

		$o_db = new Db();

		$qr_likes = $o_db->query("
			DELETE FROM ca_item_comments WHERE name = ? AND table_num = ? AND row_id = ?
		", $vn_entity_id, $t_object->tableNum(), $t_object->getPrimaryKey());

		if(!$qr_likes) {
			$this->addError('something may have went wrong');
			return false;
		}

		return array('msg' => 'like(s) successfully removed');
	}
	# -------------------------------------------------------

}
