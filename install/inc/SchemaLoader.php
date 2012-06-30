<?php
/* ----------------------------------------------------------------------
 * install/inc/SchemaLoader.php : class that loads SQL schema and performs driver translations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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

class SchemaLoader{
	private $debug = true;
	private $opa_mysqlstatements;
	public function __construct($ps_mysqlschemafilename){
		if(!($this->opa_mysqlstatements = $this->getMysqlStatements($ps_mysqlschemafilename))){
			return false;
		}
	}
	private function printDebug($ps_method, $pm_debuginfo, $pb_exit = false, $pb_printstacktrace = false){
		print "\n\n" . $ps_method . ": "; print_r($pm_debuginfo);
		if($pb_printstacktrace){
			print "\n" . caPrintStacktrace() . "\n";	
		}

		if($pb_exit){
			"\nExiting ...";
			exit;
		}
	}
	private function getMysqlStatements($ps_schemafilename){
		if(!($vs_contents = file_get_contents($ps_schemafilename))){
			if($this->debug){
				$this->printDebug(__METHOD__, "unnable to open file ".$ps_schemafilename.".");
			}
			return false;
		}
		return explode(";", $this->removeComments($vs_contents));
	}
	private function removeComments($ps_content){
		return preg_replace("!/\*.*\*/!sU", "", $ps_content);
	}
	public function getSchema($ps_driver){
		switch($ps_driver){
			case "pgsql":
			case "pgsqlpdo":
				return $this->getPostGreSQLTranslations();
				break;
			case "mysql":
			case "mysqlpdo":
			default:
				return $this->opa_mysqlstatements;
		}
		
	}
	/* NB! Makes numerous assumptions about the mysql schema! */
	private	function getPostGreSQLTranslations(){
		$va_pgstatements = array();
		$va_index_statements = array();
		foreach($this->opa_mysqlstatements as $vs_s){
			
        	if(preg_match("/create[\s\n]+table[\s\n]+([0-9a-z_]+)[\s\n]*\(.*\)/si" , $vs_s, $va_matches)){
        		$vs_s = $va_matches[0];
        		$vs_tablename = $va_matches[1];
        		$vs_pbt =  "/([(,][\s\n]*[0-9a-z_]+[\s\n]+)"; //Pattern before type field
        		$vs_s = preg_replace($vs_pbt."([0-9a-z_,()]+)[(),\s\n]+unsigned/i", "$1$2", $vs_s);
        		$vs_s = preg_replace($vs_pbt."[a-z]*int[(),0-9]*(.*)auto_increment(.*)/i", "$1serial$2$3", $vs_s);
        		$vs_s = preg_replace($vs_pbt."tinyint/i", "$1smallint", $vs_s);
        		$vs_s = preg_replace($vs_pbt."[a-z]*int[()0-9]*/i", "$1int", $vs_s);
        		$vs_s = preg_replace($vs_pbt."[a-z]*blob/i", "$1bytea", $vs_s);
        		$vs_s = preg_replace($vs_pbt."[a-z]*text/i", "$1text", $vs_s);
        		while(preg_match("/,[\s\n]*(unique|fulltext|)[\s\n]*(key|index)[\s\n]*([0-9a-z_]+)[\s\n]*(\(.*\))/i", $vs_s, $va_matches)){
            		$va_index_statements[] = $this->getPostGreSQLIndexStatement($va_matches[1], $va_matches[3], $vs_tablename, $va_matches[4]);
            		$vs_s = preg_replace("/,[\s\n]*".$va_matches[1]."[\s\n]+".$va_matches[2].".*\({$va_matches[4]}\)/iU", "", $vs_s);
        		}
        		$va_pgstatements[] = $vs_s;
    		}
			else if(preg_match("/create[\s\n]+(unique|fulltext|)[\s\n]*(index|key)[\s\n]*([0-9a-z_]+)[\s\n]+on[\s\n]+([0-9a-z_]+)(\(.*\))/i", $vs_s, $va_matches)){
					$va_index_statements[] = $this->getPostGreSQLIndexStatement($va_matches[1], $va_matches[3], $va_matches[4], $va_matches[5]);
    		}	
    		else if(preg_match("/insert.*(into.*)/is", $vs_s, $va_matches)){
					$va_pgstatements[] = "insert " . preg_replace("/unix_timestamp\(\)/", (string)time(), $va_matches[1]);

    		}
		}
		return array_merge($va_pgstatements, $va_index_statements);
	}
	private function getPostGreSQLIndexStatement($ps_type, $ps_indexname, $ps_table, $ps_args){
		$vs_name = $ps_table . "_" . $ps_indexname;
		if(strlen($vs_name) > 62){
			$vs_name = preg_replace("/([a-z])[a-z]*(_|)/i", "$1", $ps_table) . "_" .$ps_indexname;
		}

		return "create ".(preg_match("/fulltext/i", $ps_type) ? "" : $ps_type)." index {$vs_name} on {$ps_table}{$ps_args}";
	}
}
?>
