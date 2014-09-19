<?php
/**
 * Created by PhpStorm.
 * User: kehan
 * Date: 12/09/14
 * Time: 9:54 AM
 */
require_once(__DIR__. '/Installer.php');
class Updater extends Installer{

	/**
	 * @var bool We are updating the installation profile
	 */
	protected $opb_updating = true;

	public function loadLocales(){
		require_once(__CA_MODELS_DIR__."/ca_locales.php");

		$t_locale = new ca_locales();

		$va_locales = $t_locale->getLocaleList(array('index_by_code' => true));
		if(!$va_locales){
			return $this->processLocales();
		}
		foreach($va_locales as $vs_code => $va_locale){
			$this->opa_locales[$vs_code] = $va_locale['locale_id'];
		}
		return true;
	}
	/**
	 * @param $t_list ca_lists
	 * @param $po_items SimpleXMLElement
	 * @param $pn_parent_id int
	 * @return bool
	 */
	protected  function processListItems($t_list, $po_items, $pn_parent_id){
		foreach($po_items->children() as $vo_item){
			$vs_item_value = self::getAttribute($vo_item, "value");
			$vs_item_idno = self::getAttribute($vo_item, "idno");
			$vs_type = self::getAttribute($vo_item, "type");
			$vs_status = self::getAttribute($vo_item, "status");
			$vs_access = self::getAttribute($vo_item, "access");
			$vs_rank = self::getAttribute($vo_item, "rank");
			$vn_enabled = self::getAttribute($vo_item, "enabled");
			$vn_default = self::getAttribute($vo_item, "default");

			if (!isset($vs_item_value) || !strlen(trim($vs_item_value))) {
				$vs_item_value = $vs_item_idno;
			}

			$vn_type_id = null;
			if ($vs_type) {
				$vn_type_id = $t_list->getItemIDFromList('list_item_types', $vs_type);
			}


			if (!isset($vs_status)) { $vs_status = 0; }
			if (!isset($vs_access)) { $vs_access = 0; }
			if (!isset($vs_rank)) { $vs_rank = 0; }
			$va_find_values = array(
				'list_id' => (int)$t_list->getPrimaryKey(),
				'idno' => $vs_item_idno
			);
			if($pn_parent_id){
				$va_find_values['parent_id'] = (int)$pn_parent_id;
			}
			$t_item = ca_list_items::find($va_find_values, array('returnAs' => 'firstModelInstance'));
			if($t_item){
				$t_item->set('item_value', $vs_item_value);
				$t_item->set('is_enabled', $vn_enabled);
				$t_item->set('is_default', $vn_default);
				$t_item->set('type_id', $vn_type_id);
				$t_item->set('status',(int)$vs_status);
				$t_item->set('access',  (int)$vs_access);
				if (!is_null($vs_rank)) { $t_item->set('rank', (int)$vs_rank); }
			}else {
				$t_item = $t_list->addItem($vs_item_value, $vn_enabled, $vn_default, $pn_parent_id, $vn_type_id, $vs_item_idno, '', (int)$vs_status, (int)$vs_access, (int)$vs_rank);
			}
			if ($t_list->numErrors()) {
				$this->addError("There was an error while inserting list item {$vs_item_idno}: ".join(" ",$t_list->getErrors()));
				return false;
			} else {
				$t_item->setMode(ACCESS_WRITE);
				self::addLabelsFromXMLElement($t_item, $vo_item->labels, $this->opa_locales);
				if ($t_item->numErrors()) {
					$this->addError("There was an error while inserting list item label for {$vs_item_idno}: ".join(" ",$t_item->getErrors()));
				}
			}

			if (isset($vo_item->items)) {
				if(!$this->processListItems($t_list, $vo_item->items, $t_item->getPrimaryKey())){
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Override the parent class' createAdminAccount() as we don't want to overwrite admin accounts on existing installs
	 * @return bool|string
	 */
	public function createAdminAccount(){
		$this->addError("Did not create an admin account as you are updating an existing install.");
		return false;
	}
} 