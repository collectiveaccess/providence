<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Lucene/TokenFilter/StemmingFilter.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

include_once(__CA_LIB_DIR__.'/core/Search/Common/Stemmer/SnoballStemmer.php');
include_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Analysis/TokenFilter.php');
include_once(__CA_LIB_DIR__.'/core/Search/Common/Language/LanguageDetection.php');


class StemmingFilter extends Zend_Search_Lucene_Analysis_TokenFilter
{
    /**
     * Normalize Token or remove it (if null is returned)
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $po_srctoken) {
		
		$vo_lang_analyzer = new LanguageDetection();
		$vs_original_string = $po_srctoken->getTermText();
		$vs_lang_code = $vo_lang_analyzer->analyze($vs_original_string);
		/* stem text with respect to language that has been detected */
		$vo_stemmer = new SnoballStemmer();
		if($vs_lang_code) {
			$vs_stemmed_string = $vo_stemmer->stem($vs_original_string,$vs_lang_code);
		} else {
			/* if language could not be detected, don't do any stemming at all */
			$vs_stemmed_string = $vs_original_string;
		}
		/* build new token to return */
		$vo_new_token = new Zend_Search_Lucene_Analysis_Token(
                                     $vs_stemmed_string,
                                     $po_srctoken->getStartOffset(),
                                     $po_srctoken->getEndOffset());

        $vo_new_token->setPositionIncrement($po_srctoken->getPositionIncrement());

        return $vo_new_token;
    }
}

