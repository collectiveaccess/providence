<?php

/**
 * statisticsSQLHandler : a class to handle SQL queries for CA statistics
 * @author gautier
 *
 */
class statisticsSQLHandler {
	private $sql;
	private $queryparameters;
	private $datamodel_config;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->datamodel_config = Configuration::load(__CA_APP_DIR__.'/conf/datamodel.conf');
	}

	/**
	 * Getting table num from datamodel.conf
	 * @param unknown_type $tablename
	 * @return boolean|unknown
	 */
	private function getConstantTABLENUM($tablename) {
		$tablenames=$this->datamodel_config->get("tables");
		if (! isset($tablenames[$tablename])) return false;
		return $tablenames[$tablename];
	}
	
	/**
	 * Getting relationship num from datamodel.conf
	 * @param unknown_type $relationshipname
	 * @return boolean|unknown
	 */
	private function getConstantRELATIONSHIPNUM($relationshipname) {
		$relationships=$this->datamodel_config->get("relationships");
		if (! isset($relationships[$relationshipname])) return false;
		return $relationships[$relationshipname];
	}

	/**
	 * Treating constants inside a SQL query using the getConstantXyz() functions
	 * @return true
	 */
	private function treatConstants() {
		$parameters = $this->queryparameters;
		for ($i = 0; $i < count(parameters); $i++) {
			if (method_exists($this, "getConstant".$parameters[$i]["name"])) {
				// Affecting the value returned by the corresponding method
				// For example, the ^TABLENUM(ca_objects) queryparameter in the SQL will have the value given by getConstantTABLENUM("ca_objects") 
				$constantValue = call_user_method("getConstant".$parameters[$i]["name"], $this, $parameters[$i]["arguments"]);
				// Replacement by the constant value in the SQL query
				$this->sql = str_ireplace($parameters[$i]["string"], $constantValue." ", $this->sql);
				// As the constant queryparamter is no more required, destroying it
				unset($this->queryparameters[$i]);
			} 
		}
		return true;	
	}
	
    /**
     * Load SQL into the object and do the first treatements 
     * (cleaning with additionnal space, extracting parameters if needed, calling treatConstants)
     * @param string $sqlquery
     * @return boolean
     */
    public function loadSQL($sqlquery) {
    	// load the SQL
    	$this->sql=" ".str_replace(";", " ; ", $sqlquery)." ";
    	$this->sql=str_replace("\n"," \n ",$this->sql);
    	// extract the parameters
    	$this->queryparameters=$this->extractQueryParameters();
    	// treat the queryparameters available as constants
    	$this->treatConstants();
    	return true;
    }
       
    /**
     * Recognizes parameters inside a SQL query, fetching them back in an array
     * @return unknown
     */
    private function extractQueryParameters() {
    	// Basic string treatment : add spaces at start and end, before ; to allow detection of parameters not space-followed at these places
		// Extraction of the parameters through a regexp
		preg_match_all("/\^(?P<name>\w*)(\((?P<arguments>\w*)\)|) /", $this->sql, $matches);		
    	for ($i = 0; $i < count($matches[0]); $i++) {
			$queryparameters[$i]["string"] = $matches[0][$i];
			$queryparameters[$i]["name"] = $matches["name"][$i];
			if ($matches["arguments"][$i]) $queryparameters[$i]["arguments"] = $matches["arguments"][$i];
		}
		return $queryparameters;
    }
    
    /**
     * Replaces a query parameter with a given value inside the query
     * @param unknown_type $va_parameter
     * @param unknown_type $va_value
     * @return boolean
     */
    public function treatQueryParameter ($va_parameter, $va_value=NULL) {
		if ($va_value)  {
			// Doing a scan on the object queryparameters to find corresponding one
			foreach ($this->queryparameters as $num => $queryparameter) {				
				if ($queryparameter["name"] == $va_parameter) {
					// If found, replace originate string by value in the SQL query 
					$this->sql = str_ireplace($queryparameter["string"], $va_value." ", $this->sql);
					unset($this->queryparameters[$num]);
					return TRUE;
				}
 			}
		}
		return FALSE;
    }
    
    /**
     * Check if user interaction is required for query parameters
     * @return boolean
     */
    public function checkQueryParametersPresence() {
    	// If parameters have already been detected then there are some
    	if (count($this->queryparameters)) return TRUE;
    	// If the SQL still contains ^STRING then there are some even if not treated
    	if (strpos($this->sql,"^")) return TRUE; 
    	return FALSE;
    }
    

    /**
     * Returns query parameters requiring a user interaction
     * @return unknown
     */
    public function getInputParameters() {
    	return $this->queryparameters;
    }
    
    /**
     * Returns the SQL query content
     * @return boolean|string
     */
    public function getRequest() {
    	if (!isset($this->sql) || ($this->sql == "")) return FALSE; else return $this->sql;
    }
    
	/**
	 * Runs each query inside the SQL string, returns the last SQL query result
	 * @return DbResult
	 */
	public function get() {
		$sql=$this->sql;
		$sql_requests=array();

		// MySQL handling
		$o_data = new Db();
		// Setting locale for time names if not available
		//$g_ui_locale_id = $this->request->user->getPreferredUILocale();
		//$o_data->query("SET lc_time_names = '".$g_ui_locale_id."'");

		// Cleanup the sql strings : removing line breaks and last character if ; 
		$sql=trim(str_ireplace("\n", "", $sql));
		if (substr($sql,strlen($sql)-1,1) == ";") $sql=substr($sql, 0, strlen($sql)-1);

		// Separation of the distinct sql queries, execution
		$sql_requests = explode(";",$sql);
		foreach ($sql_requests as $sql_request) {
			$qr_result = $o_data->query($sql_request);	
		}
		// Returning last query result for printing
		return $qr_result; 		
	}
}