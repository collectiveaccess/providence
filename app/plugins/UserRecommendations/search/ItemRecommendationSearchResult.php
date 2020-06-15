<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/ItemRecommendationSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearchResult.php");

class ItemRecommendationSearchResult extends BaseSearchResult {
	# -------------------------------------
	/**
	 * Name of table for this type of search subject
	 */
	protected $ops_table_name = 'ca_item_recommendations';
	# -------------------------------------
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
	# -------------------------------------
}
?>
