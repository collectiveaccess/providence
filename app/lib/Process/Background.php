<?php
/** ---------------------------------------------------------------------
 * app/lib/Process/Background.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * ADAPTED FROM CODE INCLUDED IN Omeka-S (https://omeka.org/s/)
 *
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Process;

require_once(__CA_LIB_DIR__."/Process/CLI.php");
require_once(__CA_LIB_DIR__."/Process/Socket.php");

class Background {
	# -------------------------------------------------------
    /**
     *
     */
    public function __construct() {
        $this->log = caGetLogger();
    }
	# -------------------------------------------------------
    /**
     *
     */
    static public function run(string $queue) {
    	$queue = strtolower($queue);
    	
    	$ret = null;
    	$cli = new \CA\Process\CLI();
        switch($queue) {
        	case 'searchindexingqueue':
        		if (!\ca_search_indexing_queue::lockExists()) {
                	$ret = $cli->run('php', __CA_BASE_DIR__.'/support/bin/caUtils process-indexing-queue', true);	
        		}
        		break;
        	case 'taskqueue':
        		$ret = $cli->run('php', __CA_BASE_DIR__.'/support/bin/caUtils process-task-queue', true);
        		break;
        	case 'socket':
        		$ret = $cli->run('php', __CA_BASE_DIR__.'/support/bin/caUtils process-task-queue', true);
        		break;
        }
        
        return $ret;
    }
    # -------------------------------------------------------
}
