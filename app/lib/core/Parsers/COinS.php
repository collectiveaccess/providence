<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/COinS.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
  	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
  	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
  
	class COinS {
		# -------------------------------------------------------
		public function __construct() {
		
		}
		# -------------------------------------------------------
		/**
		  * Returns COinS tags for inclusion in page
		  *
		  * @param SearchResult_or_BundleableLabelableBaseModelWithAttributes_subclass $pm_instance_or_result
		  * @param string $ps_output_type
		  * @param array $pa_options
		  */
		public static function getTags($pm_instance_or_result, $ps_output_type=null, $pa_options=null) {
			$va_config = COinS::_getConfig($vs_table_name = $pm_instance_or_result->tableName(), $ps_output_type);
			$va_urls = array();
			
			if (is_subclass_of($pm_instance_or_result, 'BaseModel')) {
				$qr_res = $pm_instance_or_result->makeSearchResult($pm_instance_or_result->tableName(), array($pm_instance_or_result->getPrimaryKey()));
			} else {
				$qr_res = $pm_instance_or_result;
			}
			$o_dm = Datamodel::load();
			$t_instance = $o_dm->getInstanceByTableName($vs_table_name, true);
			$vs_type_fld_name = $t_instance->getTypeFieldName();
			
			$t_item = new ca_list_items();
			
			while($qr_res->nextHit()) {
				$vs_type_name = null;
				
				if ($vn_type_id = $qr_res->get($vs_type_fld_name)) {
					if ($t_item->load($vn_type_id)) {
						$vs_type_name = $t_item->get('idno');
					}
				}
				
				$va_mappings = (isset($va_config[$vs_type_name])) ? $va_config[$vs_type_name] : $va_config['__default__'];
				$va_people_mappings = $va_mappings['People'];
				unset($va_mappings['People']);
				
				$va_item = $va_people =  array();
				foreach ($va_mappings as $vs_key => $va_mapping) {
					if (!is_array($va_mapping)) {
						$vs_static = (string)$va_mapping;
					} else {
						$vs_static = $va_mapping['value'];
						$vs_bundle = $va_mapping['bundle'];
						$va_options = $va_mapping['options'];
					}
					
					if ($vs_bundle) {
						$va_item[$vs_key] = $qr_res->get($vs_bundle, $va_options);
					} else {
						$va_item[$vs_key]  = $vs_static;
					}
				}
				
				if(is_array($va_people_mappings)) {
					foreach ($va_people_mappings as $vn_person_type => $va_mapping) {
						if (!is_array($va_mapping)) {
							$vs_static = (string)$va_mapping;
						} else {
							$vs_static = $va_mapping['value'];
							$vs_forename_bundle = $va_mapping['forename'];
							$vs_surname_bundle = $va_mapping['surname'];
							$va_options = $va_mapping['options'];
						}
						
						$vs_forename = $qr_res->get($vs_forename_bundle, $va_options);
						$vs_surname = $qr_res->get($vs_surname_bundle, $va_options);
						
						if ($vs_forename || $vs_surname) {
							$va_people[] = array(
								'DocRelationship' => $vn_person_type,
								'FirstName' => $vs_forename,
								'LastName' => $vs_surname
							);
						} 
					}
				}
				
				$va_urls[] = $vs_url = COinS::_CreateOpenURL($va_item, $va_people);
				$va_tags[] = "<span class='Z3988' title='{$vs_url}'></span>";
			}
				
			
			$vb_return_urls = (isset($pa_options['returnUrls']) && $pa_options['returnUrls']);
			if (isset($pa_options['returnAsArray']) && $pa_options['returnAsArray']) {
				return $vb_return_urls ? $va_urls : $va_tags;
			} else {
				$vs_delimiter = (isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : "\n";
				return join($vs_delimiter, $vb_return_urls ? $va_urls : $va_tags);
			}
		}
		# -------------------------------------------------------
		/**
		  *
		  */
		public function _getConfig($ps_table_name, $ps_output_type=null) {
			$o_config = Configuration::load();
			$o_mapping = Configuration::load($o_config->get('z39_88_config'));
			
			if (!$ps_output_type) { $ps_output_type = '__default__'; }
			
			
			if (is_array($va_config = $o_mapping->getAssoc($ps_output_type))) {
				return $va_config[$ps_table_name];
			}
			return null;
		} 
		# -------------------------------------------------------
		/**
		 * Taken from http://www.kb-creative.net/sei/coins_test/
		 *
		 * Modifed by Eric Kemp-Benedict 15 July 2010: made so it uses nested arrays, not mixed objects & arrays;
  also fixed some budgs (e.g., documented document types did not match actual list in code)

OpenURL() constructs an NISO Z39.88 compliant ContextObject for use in OpenURL links and COinS.  It returns 
the proper query string, which you must embed in a <span></span> thus:
 
<span class="Z3988" title="<?php print OpenURL($Document, $People) ?>">Content of your choice goes here</span>
 
This span will work with Zotero. You can also use the output of OpenURL() to link to your library's OpenURL resolver, thus:
 
<a href="http://www.lib.utexas.edu:9003/sfx_local?<?php print OpenURL($Document, $People); ?>" title="Search for a copy of this document in UT's libraries">Find it at UT!</a>
 
Replace "http://www.lib.utexas.edu:9003/sfx_local?" with the correct resolver for your library.
 
OpenURL() takes two arguments.
 
$Document - a document array with the following properties:
	$Document["DocType"]
		0 = Article
		1 = Book Item (e.g. a chapter, section, etc)
		2 = Book
		3 = Unpublished MA thesis
		4 = Unpublished PhD thesis
		5 = Report
		6 = Conference proceedings
		7 = Conference paper
		8 = General document
 
	$Document["DocTitle"] - Title of the document.
	$Document["JournalTitle"] - Title of the journal/magazine the article was published in, or false if this is not an article.
 
	$Document["BookTitle"] - Title of the book in which this item was published, or false if this is not a book item.
 
	$Document["Volume"] - The volume of the journal this article was published in as an integer, or false if this is not an article.  Optional.
	$Document["JournalIssue"] - The issue of the journal this article was published in as an integer, or false if this is not an article.  Optional.
	$Document["JournalSeason"] Optional.
		The season of the journal this article was published in, as a string, where:
			Spring
			Summer
			Fall
			Winter
			false = not applicable
	$Document["JournalQuarter"] - The quarter of the journal this article was published in as an integer between 1 and 4, or false. Optional.
	$Document["ISSN"] - The volume of the journal this article was published in, or false.  Optional.
 
 
	$Document["BookPublisher"] - The publisher of the book, or false. Optional.
	$Document["PubPlace"] - The publication place, or false.  Optional.
	$Document["ISBN"] - The ISBN of the book.  Optional but highly recommended.
 
	$Document["StartPage"] - Start page for the article or item, or false if this is a complete book.
	$Document["EndPage"] - End page for the article or item, or false if this is a complete book.
 
$Document["DocYear"] - The year in which this document was published.
 
$People - An array of person arrays, each with these properties:
	$People[i]["DocRelationship"]
		An integer indicating what kind of relationship the person has to this document.
		0 = author
		1 = editor
		2 = translator
	$People[i]["FirstName"] - The person's first name.
	$People[i]["LastName"] - The person's last name.
);
		 */
		public static function _CreateOpenURL($Document, $People){
			$DocType = $Document["DocType"];
			if($DocType > 8){ return false; }
		 
			// Base of the OpenURL specifying which version of the standard we're using.
			$URL = "ctx_ver=Z39.88-2004";
			
			// Metadata format - e.g. article or book.
			if($DocType == 0){ $URL .= "&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal"; }
			if($DocType > 0){ $URL .= "&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook"; }
		 
			// An ID for your application.  Replace yoursite.com and specify a name for your application.
			$URL .= "&amp;rfr_id=info%3Asid%2F".$_SERVER['HTTP_HOST']."%3ACollectiveAccess";
			
			// Document Genre
			if($DocType == 0){ $URL .= "&amp;rft.genre=article"; }
			if($DocType == 1){ $URL .= "&amp;rft.genre=bookitem"; }
			if($DocType == 2){ $URL .= "&amp;rft.genre=book"; }
			if($DocType == 3){ $URL .= "&amp;rft.genre=book"; }
			if($DocType == 4){ $URL .= "&amp;rft.genre=book"; }
			if($DocType == 5){ $URL .= "&amp;rft.genre=report"; }
			if($DocType == 6){ $URL .= "&amp;rft.genre=conference"; }
			if($DocType == 7){ $URL .= "&amp;rft.genre=proceeding"; }
			if($DocType == 8){ $URL .= "&amp;rft.genre=document"; }
		 
			// Document Title
			if($DocType < 2){ $URL .= "&amp;rft.atitle=".urlencode($Document["DocTitle"]); }
			if($DocType >= 2){ $URL .= "&amp;rft.btitle=".urlencode($Document["DocTitle"]); }
		 
			// Publication Title
			if($DocType == 0){ $URL .= "&amp;rft.jtitle=".urlencode($Document["JournalTitle"]); }
			if($DocType > 0){ $URL .= "&amp;rft.btitle=".urlencode($Document["BookTitle"]); }
		 
			// Volume, Issue, Season, Quarter, and ISSN (for journals)
			if($DocType == 0){
				if($Document["Volume"]){ $URL .= "&amp;rft.volume=".urlencode($Document["Volume"]); }
				if($Document["JournalIssue"]){ $URL .= "&amp;rft.issue=".urlencode($Document["JournalIssue"]); }
				if($Document["JournalSeason"]){ $URL .= "&amp;rft.ssn=".urlencode($Document["JournalSeason"]); }
				if($Document["JournalQuarter"]){ $URL .= "&amp;rft.quarter=".urlencode($Document["JournalQuarter"]); }
				if($Document["JournalQuarter"]){ $URL .= "&amp;rft.quarter=".urlencode($Document["ISSN"]); }
			}
		 
			// Publisher, Publication Place, and ISBN (for books)
			if($DocType > 0){
				$URL .= "&amp;rft.pub=".urlencode($Document["BookPublisher"]);
				$URL .= "&amp;rft.place=".urlencode($Document["PubPlace"]);
				if($Document["ISBN"]) {$URL .= "&amp;rft.isbn=".urlencode($Document["ISBN"]);}
			}
		 
			// Start page and end page (for journals and book articles)
			if($DocType < 2){
				$URL .= "&amp;rft.spage=".urlencode($Document["StartPage"]);
				$URL .= "&amp;rft.epage=".urlencode($Document["EndPage"]);
			}
		 
			// Publication year.
			$URL .= "&amp;rft.date=".$Document["DocYear"];
		 
			// Authors
			$i = 0;
			while($People[$i]){
				if($People[$i]["DocRelationship"] == 0){
					$URL .= "&amp;rft.au=".urlencode($People[$i]["LastName"]).",+".urlencode($People[$i]["FirstName"]);
				}
				$i++;
			}
		 
			return $URL;
		}
		# -------------------------------------------------------
	}
?>