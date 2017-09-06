<?php
/* ----------------------------------------------------------------------
 * plugins/statisticsViewer/controllers/StatisticsController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

 	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 	require_once(__CA_APP_DIR__.'/plugins/statisticsViewer/lib/statisticsSQLHandler.php');
 	

 	class StatisticsController extends ActionController {
 		# -------------------------------------------------------
  		protected $opo_config;		// plugin configuration file
 		protected $opa_dir_list;	// list of available import directories
 		protected $opa_regexes;		// list of available regular expression packages for extracting object idno's from filenames
 		protected $opa_regex_patterns;
 		protected $opa_locales;
 		protected $opa_statistics_xml_files;
 		protected $opa_statistics;
 		protected $opa_stat;
 		protected $opa_id;
 		protected $pa_parameters;
 		protected $allowed_universes;


 		# -------------------------------------------------------
 		# Constructor
 		# -------------------------------------------------------

 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			global $allowed_universes;
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->user->canDoAction('can_use_statistics_viewer_plugin')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/statisticsViewer/conf/statisticsViewer.conf');
			
			// Get directory list
			$va_file_list = caGetDirectoryContentsAsList(__CA_APP_DIR__."/plugins/statisticsViewer/".$this->opo_config->get('XmlStatisticsRootDirectory'), false, false);
			$va_dir_list_with_file_counts = caGetSubDirectoryList($this->opo_config->get('importRootDirectory'), true, false);
			$this->opa_statistics_xml_files = array();
			$this->opa_statistics = array();

			if (is_array($allowed_universes = $this->opo_config->getAssoc('AvailableUniversesForStats'))) {
				// if the conf variable AvailableUniversesFor stats is defined
				//echo "here.";
			}
			
			$this->get_statistics_listing($va_file_list,$allowed_universes);			
 		}

 		# -------------------------------------------------------
 		# Local functions
 		# -------------------------------------------------------

		private function get_statistics_listing($va_file_list="",$va_universes) {
			if ($va_file_list=="") exit(); 
			// Parsing XML content files
			foreach($va_file_list as $vs_file) {
				if (in_array(basename($vs_file,".xml"),$va_universes)) {
				// if the file correspond to the universes declared in the conf file		
					if (file_exists($vs_file)) {
						$xml = simplexml_load_file($vs_file);
						$this->opa_statistics[basename($vs_file,".xml")]=$xml;
					} else {
						// TODO : treat the opening error with a CA error report
						exit("Failed opening xml file : ".$vs_file."<br/>\n");
						return(false);
					}
				}					
			}
			
			// Creating an object of XML content without the request
			// For safety reason, sql requests are dropped out of the variable 
			// we're handling, until the last moment
			// TODO : remove the need of this cleaning, doing it at the time we're reading
			// the file
			
			foreach($this->opa_statistics as $stat_file) {
				foreach($stat_file->statistics_group as $s_group) {
					foreach($s_group->statistic as $s_statistic) {
						$s_statistic->sql="";
					}
				}
			}
			return(true);
		}
		
		private function get_statistics_sql_request($path,$universe, $id) {
			global $allowed_universes;
			
			// TODO : create a specialized class for manipulating the object
			$statistics_sql_request = (object) array("title" => "", "sql" => "", "columns" =>"");
			// if the file correspond to the universes declared in the conf file		
			if (in_array($universe,$allowed_universes)) {
				// if the file exists load it
				if (file_exists($path."/".$universe.".xml")) {
					$xml = simplexml_load_file($path."/".$universe.".xml");
				// else exit
				} else {
					// TODO : treat the opening error with a CA error report
					exit("Failed opening xml file : ".$path."/".$universe.".xml"."<br/>\n");
				}

				foreach($xml->statistics_group as $s_group) {
					foreach($s_group->statistic as $s_statistic) {						
						if ($s_statistic->id == $id) {
							$statistics_sql_request->stat = $universe;
							$statistics_sql_request->id = $id;
							$statistics_sql_request->title = $s_statistic->title;
							$statistics_sql_request->comment = $s_statistic->comment;								
							$statistics_sql_request->sql = $s_statistic->sql;
							$statistics_sql_request->charting = $s_statistic->charting;
							$statistics_sql_request->columns = explode(",",$s_statistic->columns);
							$statistics_sql_request->total_columns = explode(",",$s_statistic->total_columns);
							$statistics_sql_request->charting_columns = explode(",",$s_statistic->charting_columns);
						}
					}
				}
				return $statistics_sql_request;
			}
		}

 		# -------------------------------------------------------
		private function getStatisticsInformations($stat,$id) {
 			$statistics=$this->get_statistics_sql_request(
					__CA_APP_DIR__."/plugins/statisticsViewer/".$this->opo_config->get('XmlStatisticsRootDirectory'),
					$this->request->getParameter('stat', pString), 
					$this->request->getParameter('id', pInteger)
				);
			return($statistics);
		}
 		
 		# -------------------------------------------------------
		private function getSqlRequest($stat,$id) {
 			$statistics=$this->getStatisticsInformations($stat,$id);
			$sql=$statistics->sql;				
			return($sql);
		}
 		 		
 		# -------------------------------------------------------
 		# Functions to render views
 		# -------------------------------------------------------
 		public function Index($type="") {
 			$universe=$this->request->getParameter('universe', pString);
 			if(!isset($universe)) {
 				_p("No corresponding table (or stat universe) declared.");
 			} else {
				$this->view->setVar('statistics_listing', $this->opa_statistics[$universe]);
				$this->render('stats_home_html.php');			 				
 			}
 		}
 				
 		public function ShowStat() {
			// Getting values
 			$opa_stat=$this->request->getParameter('stat', pString);
 			$opa_id=$this->request->getParameter('id', pInteger);
 			 			
			// Getting selected position or default one
 			if (!$opa_selectedPosition=$this->request->getParameter('selectedPosition', pString))
 				$opa_selectedPosition=$this->opo_config->get('defaultPosition');  
			// Getting passed charting type, if not available getting xml default charting type, 
			// if not available getting conf default charting type 
 			if (!$opa_selectedChartingType=$this->request->getParameter('selectedChartingType', pString)) {
 				if (!($opa_selectedChartingType=$this->getStatisticsInformations($opa_stat,$opa_id)->charting)) {
 					$opa_selectedChartingType=$this->opo_config->get('DefaultChartType');	
 				} 				
 			}
 			
 			// Creating a statisticsSQLHandler object to treat the request and its parameters if needed
 			$statSQLHandler = new statisticsSQLHandler();
 			// Loading the SQL request inside the object
 			$statSQLHandler->loadSQL($this->getSqlRequest($opa_stat,$opa_id));

 			// Getting an array with the list of the required parameters
 			$opa_queryparameters = $statSQLHandler->getInputParameters();
 			// Check posted values
 			if ($opa_queryparameters) 
	 			foreach ($opa_queryparameters as $queryparameter) {
	 				$value=$this->request->getParameter($queryparameter["name"], pString);
	 				if ($value) {
	 					//print $queryparameter["name"];die();
	 					$statSQLHandler->treatQueryParameter($queryparameter["name"],$value); 
	 				}
	 			}
 			
 			$this->view->setVar(
					'informations', 
					$this->getStatisticsInformations($opa_stat,$opa_id));

			// If no parameter input required in the query, render the view... 
 			if (!$statSQLHandler->checkQueryParametersPresence()) {
	 			// Defining view's parameters
	 			$this->view->setVar(
	 				'sql',
	 				array(
	 					'request' => $statSQLHandler->getRequest(),
	 					'result' => $statSQLHandler->get()
	 				));
	 			$this->view->setVar(
	 				'parameters',
	 				array(
	 					'ChartingLib' => $this->opo_config->get('ChartingLib'),
	 					'positions' => $this->opo_config->getAssoc('positions'),
	 					'selectedPosition' => $opa_selectedPosition,
	 					'ChartTypes' => $this->opo_config->getAssoc('ChartTypes'),
	 					'selectedChartingType' => $opa_selectedChartingType));
				$this->render('stats_viewstat_html.php');
 			
 			// ... else see if query parameters values if needed
 			} else {
				$this->view->setVar(
	 				'queryparameters',
				 	$opa_queryparameters
	 				);
	 			$this->render('stats_queryparameters_html.php');				
 			}
 		}

 		public function ShowChartImage() {
			// Getting values
 			$opa_stat=$this->request->getParameter('stat', pString);
 			$opa_id=$this->request->getParameter('id', pInteger);
 			$opa_type=$this->request->getParameter('type', pString);
 			$opa_width=$this->request->getParameter('width', pInteger);

 			// Creating a statisticsSQLHandler object to treat the request and its parameters if needed
 			$statSQLHandler = new statisticsSQLHandler();
 			// Loading the SQL request inside the object
 			$statSQLHandler->loadSQL($this->getSqlRequest($opa_stat,$opa_id));
 			
 			
 			// Getting selected charting type or default one
			// Defining view's parameters
			$this->view->setVar(
				'informations', 
				$this->getStatisticsInformations($opa_stat,$opa_id));
 			$this->view->setVar(
 				'sql',
	 			array(
	 				'request' => $statSQLHandler->getRequest(),
	 				'result' => $statSQLHandler->get()
	 			));
 			$this->view->setVar(
 				'parameters',
 				array(
 					'ChartingLib' => $this->opo_config->get('ChartingLib'),
 					'width' => $opa_width,
 					'ChartTypes' => $this->opo_config->getAssoc('ChartTypes'),
 					'selectedChartingType' => $opa_type));
			$this->render('stats_viewstat_image.php');
 		}
 		
 		public function ShowCSV() {   	
			// Getting values
 			$opa_stat=$this->request->getParameter('stat', pString);
 			$opa_id=$this->request->getParameter('id', pInteger);

 			// Creating a statisticsSQLHandler object to treat the request and its parameters if needed
 			$statSQLHandler = new statisticsSQLHandler();
 			// Loading the SQL request inside the object
 			$statSQLHandler->loadSQL($this->getSqlRequest($opa_stat,$opa_id));
 			
 			// Defining view's parameters
			$this->view->setVar(
				'informations', 
				$this->getStatisticsInformations($opa_stat,$opa_id));
 			$this->view->setVar(
 				'sql',
 				array(
	 				'request' => $statSQLHandler->getRequest(),
	 				'result' => $statSQLHandler->get()
 	 			));
 			$this->view->setVar(
 				'parameters',
 				array());
			$this->render('stats_csv_html.php');
 		}

 		# ------------------------------------------------------- 				
 	}
 ?>