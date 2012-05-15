<?php
/**
 * 
 * SetSearch module.  Copyright 2009 Whirl-i-Gig (http://www.whirl-i-gig.com)
 * class for object search handling
 *
 * @author Stefan Keidel <stefan@whirl-i-gig.com>
 * @copyright Copyright 2008 Whirl-i-Gig (http://www.whirl-i-gig.com)
 * @license http://www.gnu.org/copyleft/lesser.html
 * @package CA
 * @subpackage Core
 *
 * Disclaimer:  There are no doubt inefficiencies and bugs in this code; the
 * documentation leaves much to be desired. If you'd like to improve these  
 * libraries please consider helping us develop this software. 
 *
 * phpweblib is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 *
 * This source code are free and modifiable under the terms of 
 * GNU Lesser General Public License. (http://www.gnu.org/copyleft/lesser.html)
 *
 *
 */

include_once(__CA_LIB_DIR__."/ca/Search/BaseSearch.php");
include_once(__CA_LIB_DIR__."/ca/Search/SetSearchResult.php");

class SetSearch extends BaseSearch {
	# ----------------------------------------------------------------------
	/**
	 * Which table does this class represent?
	 */
	protected $ops_tablename = "ca_sets";
	protected $ops_primary_key = "set_id";

	# ----------------------------------------------------------------------
	public function &search($ps_search, $pa_options=null) {
		return parent::search($ps_search, new SetSearchResult(), $pa_options);
	}
	# ----------------------------------------------------------------------
}