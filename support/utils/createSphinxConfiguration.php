<?php

if(!file_exists('./setup.php')) {
	die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
}

/* necessary files */
require_once("./setup.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once("./sphinxplugin_templates/sphinx_charset_table.php");

/* formatting helper functions */
function nl(){
	return "\n";
}

function tabs($pn_num_tabs){
	$vs_return = "";
	for($i=0;$i<$pn_num_tabs;$i++){
		$vs_return.="\t";
	}
	return $vs_return;
}

/* get search and search indexing configuration */
$po_app_config = Configuration::load();
$po_search_config = Configuration::load($po_app_config->get("search_config"));
$po_search_indexing_config = Configuration::load($po_search_config->get("search_indexing_config"));

$ps_sphinx_dir_prefix = $po_search_config->get('search_sphinx_dir_prefix');

/* parse search indexing configuration to see which tables are indexed */
$va_tables = $po_search_indexing_config->getAssocKeys();

/* this will be the resulting configuration file */
$vr_sphinxconfig_file = fopen($ps_sphinx_dir_prefix."/etc/sphinx.conf", 'w+');

/* this is the template */
$va_sphinx_conf_template = file("./sphinxplugin_templates/sphinx.conf");
foreach($va_sphinx_conf_template as $vs_template_line){
	if(stripos($vs_template_line,"#### SOURCES ####")!==false){ /* sources placeholder in template*/
		foreach($va_tables as $vs_table){
			/*
			 * We define 2 sources per table to be indexed.
			 * One is meant to create an empty index, the other one
			 * is for the "delta" index, so it returns only the newest
			 * record (max(primary_key)), which can be merged to the
			 * 'all' index afterwards.
			 */
			// 'all' source
			$vs_source = "";
			$vs_source.="source ".$vs_table."_all {".nl();
			$vs_source.=tabs(1)."type".tabs(3)."= xmlpipe2".nl();
			$vs_source.=tabs(1)."xmlpipe_command".tabs(2).
				"= cat ".__CA_APP_DIR__."/tmp/sphinx/".$vs_table."_delta_provider".nl(); // we will need a (dynamic) dummy xml file to create an empty 'all' index which is then populated with data by merges from the delta index. for now this is the delta_provider
			$vs_source.="}".nl();

			// 'delta' source
			$vs_source.="source ".$vs_table."_delta {".nl();
			$vs_source.=tabs(1)."type".tabs(3)."= xmlpipe2".nl();
			$vs_source.=tabs(1)."xmlpipe_command".tabs(2).
				"= cat ".__CA_APP_DIR__."/tmp/sphinx/".$vs_table."_delta_provider".nl();
			$vs_source.="}".nl().nl();
			fprintf($vr_sphinxconfig_file,"%s",$vs_source);
		}
	} else if(stripos($vs_template_line,"#### INDEXES ####")!==false){ /* indexes placeholder */
		foreach($va_tables as $vs_table){
			$vs_index = "";
			// 'all' index
			$vs_index.="index ".$vs_table."_all {".nl();
			/* indexes are stored in <ca_app_dir>/sphinx/<table_name>_<index_type> */
			$vs_index.=tabs(1)."path".tabs(3)."= ".__CA_APP_DIR__."/sphinx/".$vs_table."_all".nl();
			/* stemming and stuff of that sort is done in our library, so the search engine doesn't have to do that */
			/* common stuff between all indexes, so we inherit it from #1 (vs_dad) */
			$vs_index.=tabs(1)."morphology".tabs(2)."= none".nl();
			$vs_index.=tabs(1)."source".tabs(3)."= ".$vs_table."_all".nl();
			$vs_index.=tabs(1)."charset_type".tabs(2)."= utf-8".nl();
			$vs_index.=$vs_charset_table.nl(); /* charset table is imported from external file */
			$vs_index.="}".nl();

			// 'delta' index
			$vs_index.="index ".$vs_table."_delta {".nl();
			$vs_index.=tabs(1)."path".tabs(3)."= ".__CA_APP_DIR__."/sphinx/".$vs_table."_delta".nl();
			$vs_index.=tabs(1)."source".tabs(3)."= ".$vs_table."_delta".nl();
			$vs_index.=tabs(1)."morphology".tabs(2)."= none".nl();
			$vs_index.=tabs(1)."charset_type".tabs(2)."= utf-8".nl();
			$vs_index.=$vs_charset_table.nl(); /* charset table is imported from external file */
			$vs_index.="}".nl().nl();
			fprintf($vr_sphinxconfig_file,"%s",$vs_index);
		}
	} else { /* everything else is just copied (indexer and searchd configuration) */
		fprintf($vr_sphinxconfig_file,"%s",$vs_template_line);
	}
}
fclose($vr_sphinxconfig_file);
?>