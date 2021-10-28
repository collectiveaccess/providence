<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2021 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */

 	require_once(__CA_LIB_DIR__.'/Utils/CLIBaseUtils.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Maintenance.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Media.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Search.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/ImportExport.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/ContentManagement.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Cron.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Performance.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Test.php');
 	require_once(__CA_LIB_DIR__.'/Utils/CLIUtils/Statistics.php');
 
	class CLIUtils extends CLIBaseUtils {
		use CLIUtilsMaintenance;
		use CLIUtilsMedia;
		use CLIUtilsSearch;
		use CLIUtilsConfiguration;
		use CLIUtilsImportExport;
		use CLIUtilsContentManagement;
		use CLIUtilsCron;
		use CLIUtilsPerformance;
		use CLIUtilsStatistics;
		use CLIUtilsTest;
	}
