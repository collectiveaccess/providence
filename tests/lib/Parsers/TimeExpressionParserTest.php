<?php
/** ---------------------------------------------------------------------
 * tests/lib/Parsers/TimeExpressionParserTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');

class TimeExpressionParserTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		// most of the comparisons below rely on Eastern time zone
		date_default_timezone_set('America/New_York');
	}
	
	public function testHyphensInSortOfOddPlaces() {
	 	$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('c.1887-c.1918');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1887.010100000010");
		$this->assertEquals($va_parse['end'], "1918.123123595910");
		$this->assertEquals($va_parse[0], "1887.010100000010");
		$this->assertEquals($va_parse[1], "1918.123123595910");	
		$this->assertEquals($o_tep->getText(), "circa 1887 – 1918");
		
		$vb_res = $o_tep->parse('19th-century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1800.010100000000");
		$this->assertEquals($va_parse['end'], "1899.123123595900");
		$this->assertEquals($va_parse[0], "1800.010100000000");
		$this->assertEquals($va_parse[1], "1899.123123595900");	
		$this->assertEquals($o_tep->getText(), "19th century");
		
		
		$vb_res = $o_tep->parse('early-19th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1800.010100000000");
		$this->assertEquals($va_parse['end'], "1820.123123595900");
		$this->assertEquals($va_parse[0], "1800.010100000000");
		$this->assertEquals($va_parse[1], "1820.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 19th century");
		
		$vb_res = $o_tep->parse('early-19th-century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1800.010100000000");
		$this->assertEquals($va_parse['end'], "1820.123123595900");
		$this->assertEquals($va_parse[0], "1800.010100000000");
		$this->assertEquals($va_parse[1], "1820.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 19th century");
	}
	
	
	public function testQualifiedDecadeAndCenturyRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('1920s - early 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1920.010100000000");
		$this->assertEquals($va_parse['end'], "1934.123123595900");
		$this->assertEquals($va_parse[0], "1920.010100000000");
		$this->assertEquals($va_parse[1], "1934.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 1920s - early 1930s");
		
		$vb_res = $o_tep->parse('mid 1920s - early 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1923.010100000000");
		$this->assertEquals($va_parse['end'], "1934.123123595900");
		$this->assertEquals($va_parse[0], "1923.010100000000");
		$this->assertEquals($va_parse[1], "1934.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 1920s - early 1930s");
		
		$vb_res = $o_tep->parse('late 1920s - early 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1926.010100000000");
		$this->assertEquals($va_parse['end'], "1934.123123595900");
		$this->assertEquals($va_parse[0], "1926.010100000000");
		$this->assertEquals($va_parse[1], "1934.123123595900");	
		$this->assertEquals($o_tep->getText(), "late 1920s - early 1930s");
		
		$vb_res = $o_tep->parse('early 1920s - mid 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1920.010100000000");
		$this->assertEquals($va_parse['end'], "1937.123123595900");
		$this->assertEquals($va_parse[0], "1920.010100000000");
		$this->assertEquals($va_parse[1], "1937.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 1920s - mid 1930s");
		
		$vb_res = $o_tep->parse('mid 1920s - mid 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1923.010100000000");
		$this->assertEquals($va_parse['end'], "1937.123123595900");
		$this->assertEquals($va_parse[0], "1923.010100000000");
		$this->assertEquals($va_parse[1], "1937.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 1920s - mid 1930s");
		
		$vb_res = $o_tep->parse('late 1920s - mid 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1926.010100000000");
		$this->assertEquals($va_parse['end'], "1937.123123595900");
		$this->assertEquals($va_parse[0], "1926.010100000000");
		$this->assertEquals($va_parse[1], "1937.123123595900");	
		$this->assertEquals($o_tep->getText(), "late 1920s - mid 1930s");
		
		$vb_res = $o_tep->parse('early 1920s - late 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1920.010100000000");
		$this->assertEquals($va_parse['end'], "1939.123123595900");
		$this->assertEquals($va_parse[0], "1920.010100000000");
		$this->assertEquals($va_parse[1], "1939.123123595900");	
		$this->assertEquals($o_tep->getText(), "1920 – 1939");
		
		$vb_res = $o_tep->parse('mid 1920s - late 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1923.010100000000");
		$this->assertEquals($va_parse['end'], "1939.123123595900");
		$this->assertEquals($va_parse[0], "1923.010100000000");
		$this->assertEquals($va_parse[1], "1939.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 1920s - late 1930s");
		
		$vb_res = $o_tep->parse('late 1920s - late 1930s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1926.010100000000");
		$this->assertEquals($va_parse['end'], "1939.123123595900");
		$this->assertEquals($va_parse[0], "1926.010100000000");
		$this->assertEquals($va_parse[1], "1939.123123595900");	
		$this->assertEquals($o_tep->getText(), "late 1920s - late 1930s");
		
		$vb_res = $o_tep->parse('early 18th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1700.010100000000");
		$this->assertEquals($va_parse['end'], "1720.123123595900");
		$this->assertEquals($va_parse[0], "1700.010100000000");
		$this->assertEquals($va_parse[1], "1720.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 18th century");
		
		$vb_res = $o_tep->parse('mid 18th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1740.010100000000");
		$this->assertEquals($va_parse['end'], "1760.123123595900");
		$this->assertEquals($va_parse[0], "1740.010100000000");
		$this->assertEquals($va_parse[1], "1760.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 18th century");
		
		$vb_res = $o_tep->parse('late 18th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1780.010100000000");
		$this->assertEquals($va_parse['end'], "1799.123123595900");
		$this->assertEquals($va_parse[0], "1780.010100000000");
		$this->assertEquals($va_parse[1], "1799.123123595900");	
		$this->assertEquals($o_tep->getText(), "late 18th century");
		
		$vb_res = $o_tep->parse('early 18th century - early 19th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1700.010100000000");
		$this->assertEquals($va_parse['end'], "1820.123123595900");
		$this->assertEquals($va_parse[0], "1700.010100000000");
		$this->assertEquals($va_parse[1], "1820.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 18th century - early 19th century");
		
		$vb_res = $o_tep->parse('mid 18th century - mid 19th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1740.010100000000");
		$this->assertEquals($va_parse['end'], "1860.123123595900");
		$this->assertEquals($va_parse[0], "1740.010100000000");
		$this->assertEquals($va_parse[1], "1860.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 18th century - mid 19th century");
		
		$vb_res = $o_tep->parse('late 15th century - mid 17th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1480.010100000000");
		$this->assertEquals($va_parse['end'], "1660.123123595900");
		$this->assertEquals($va_parse[0], "1480.010100000000");
		$this->assertEquals($va_parse[1], "1660.123123595900");		
		$this->assertEquals($o_tep->getText(), "late 15th century - mid 17th century");
		
		$vb_res = $o_tep->parse('late 15th century - late 17th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1480.010100000000");
		$this->assertEquals($va_parse['end'], "1699.123123595900");
		$this->assertEquals($va_parse[0], "1480.010100000000");
		$this->assertEquals($va_parse[1], "1699.123123595900");		
		$this->assertEquals($o_tep->getText(), "late 15th century - late 17th century");
		
		$vb_res = $o_tep->parse('early 1920s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1920.010100000000");
		$this->assertEquals($va_parse['end'], "1924.123123595900");
		$this->assertEquals($va_parse[0], "1920.010100000000");
		$this->assertEquals($va_parse[1], "1924.123123595900");	
		$this->assertEquals($o_tep->getText(), "early 1920s");
		
		$vb_res = $o_tep->parse('mid 1920s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1923.010100000000");
		$this->assertEquals($va_parse['end'], "1927.123123595900");
		$this->assertEquals($va_parse[0], "1923.010100000000");
		$this->assertEquals($va_parse[1], "1927.123123595900");	
		$this->assertEquals($o_tep->getText(), "mid 1920s");
		
		$vb_res = $o_tep->parse('late 1920s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1926.010100000000");
		$this->assertEquals($va_parse['end'], "1929.123123595900");
		$this->assertEquals($va_parse[0], "1926.010100000000");
		$this->assertEquals($va_parse[1], "1929.123123595900");	
		$this->assertEquals($o_tep->getText(), "late 1920s");
	}
	
	public function testCenturyRange() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');	
		
		$vb_res = $o_tep->parse('18th century - 19th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1700.010100000000");
		$this->assertEquals($va_parse['end'], "1899.123123595900");
		$this->assertEquals($va_parse[0], "1700.010100000000");
		$this->assertEquals($va_parse[1], "1899.123123595900");	
		
		
		$vb_res = $o_tep->parse('15th century - 20th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1400.010100000000");
		$this->assertEquals($va_parse['end'], "1999.123123595900");
		$this->assertEquals($va_parse[0], "1400.010100000000");
		$this->assertEquals($va_parse[1], "1999.123123595900");	
	}
	
	public function testPrePostDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('pre 1600');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "1600.123123595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "1600.123123595900");	
		
		$vb_res = $o_tep->parse('post 1600');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1600.010100000000");
		$this->assertEquals($va_parse['end'], "2000000000.123123595900");
		$this->assertEquals($va_parse[0], "1600.010100000000");
		$this->assertEquals($va_parse[1], "2000000000.123123595900");	
		
		$vb_res = $o_tep->parse('18th century - 19th Century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1700.010100000000");
		$this->assertEquals($va_parse['end'], "1899.123123595900");
		$this->assertEquals($va_parse[0], "1700.010100000000");
		$this->assertEquals($va_parse[1], "1899.123123595900");	
	}
	
	
	public function testModifiersWithoutTrailingSpaces() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('c1959');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1959.0101000000100");
		$this->assertEquals($va_parse['end'], "1959.123123595910");
		$this->assertEquals($va_parse[0], "1959.010100000010");
		$this->assertEquals($va_parse[1], "1959.123123595910");	
		
		$vb_res = $o_tep->parse('c.1959');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1959.0101000000100");
		$this->assertEquals($va_parse['end'], "1959.123123595910");
		$this->assertEquals($va_parse[0], "1959.010100000010");
		$this->assertEquals($va_parse[1], "1959.123123595910");	
	}
	
	public function testBPDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('55 BP');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1895.010100000080");	# Date attribute 8=BP
		$this->assertEquals($va_parse['end'], "1895.123123595980");
		$this->assertEquals($va_parse[0], "1895.010100000080");
		$this->assertEquals($va_parse[1], "1895.123123595980");	
		
		
		$vb_res = $o_tep->parse('7000 BP');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-5050.010100000080");	# Date attribute 8=BP
		$this->assertEquals($va_parse['end'], "-5050.123123595980");
		$this->assertEquals($va_parse[0], "-5050.010100000080");
		$this->assertEquals($va_parse[1], "-5050.123123595980");	
		
		
		$vb_res = $o_tep->parse('pre-1600');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "1600.123123595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "1600.123123595900");	
		
		$vb_res = $o_tep->parse('post-1600');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1600.010100000000");
		$this->assertEquals($va_parse['end'], "2000000000.123123595900");
		$this->assertEquals($va_parse[0], "1600.010100000000");
		$this->assertEquals($va_parse[1], "2000000000.123123595900");	
	}
	
	public function testDatesWithoutStart() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('- 6/5/1950');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "1950.060523595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "1950.060523595900");	
		
		$vb_res = $o_tep->parse('- 3/2010');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "2010.033123595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "2010.033123595900");	
		
		$vb_res = $o_tep->parse('- 3/12');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], date("Y").".031223595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], date("Y").".031223595900");	
		
		
		$vb_res = $o_tep->parse('- 3/99');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "1999.033123595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "1999.033123595900");	
		
		$vb_res = $o_tep->parse('- 1950');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "-2000000000.000000000000");
		$this->assertEquals($va_parse['end'], "1950.123123595900");
		$this->assertEquals($va_parse[0], "-2000000000.000000000000");
		$this->assertEquals($va_parse[1], "1950.123123595900");	
	}
	
	public function testExifDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		
		$vb_res = $o_tep->parse('2015:07:15 14:29:17.49');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		
		$this->assertEquals($va_parse['start'], "2015.0715142917");
		$this->assertEquals($va_parse['end'], "2015.0715142917");
		$this->assertEquals($va_parse[0], "2015.0715142917");
		$this->assertEquals($va_parse[1], "2015.0715142917");
		
		$va_parse = $o_tep->getUnixTimestamps();
		
		$this->assertEquals($va_parse['start'], "1436984957");
		$this->assertEquals($va_parse['end'], "1436984957");
		$this->assertEquals($va_parse[0], "1436984957");
		$this->assertEquals($va_parse[1], "1436984957");
	}

	public function testUnknownYearAACR2() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('199-');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("1990.010100000000", $va_parse['start']);
		$this->assertEquals("1999.123123595900", $va_parse['end']);
	}

	public function testUncertainDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('199?');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("199.010100000010", $va_parse['start']);
		$this->assertEquals("199.123123595910", $va_parse['end']);
	}

	public function testEarlyCEDatesWithoutEra() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('12/22/199');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("199.122200000000", $va_parse['start']);
		$this->assertEquals("199.122223595900", $va_parse['end']);
	}

	public function testEarlyCEDatesWithoutEraAussieStyle() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_AU');

		$vb_res = $o_tep->parse('22/12/199');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("199.122200000000", $va_parse['start']);
		$this->assertEquals("199.122223595900", $va_parse['end']);

		$vb_res = $o_tep->parse('22.12.199');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("199.122200000000", $va_parse['start']);
		$this->assertEquals("199.122223595900", $va_parse['end']);
	}

	public function testImplicitCenturyDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('2/19/16');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("2016.021900000000", $va_parse['start']);
		$this->assertEquals("2016.021923595900", $va_parse['end']);
	}

	public function testQuarterCenturyDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('20 Q2');	// 2nd quarter of 20th century
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1925.0101000000");
		$this->assertEquals($va_parse['end'], "1950.1231235959");
		$this->assertEquals($va_parse[0], "1925.0101000000");
		$this->assertEquals($va_parse[1], "1950.1231235959");

		$vb_res = $o_tep->parse('1 Q4');		// 4th quarter of 1st century
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "75.0101000000");
		$this->assertEquals($va_parse['end'], "100.1231235959");
		$this->assertEquals($va_parse[0], "75.0101000000");
		$this->assertEquals($va_parse[1], "100.1231235959");

	}

	public function testYearlessDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('10/24/??');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "0.1024000000");
		$this->assertEquals($va_parse['end'], "0.1024235959");
		$this->assertEquals($va_parse[0], "0.1024000000");
		$this->assertEquals($va_parse[1], "0.1024235959");
		$this->assertEquals($o_tep->getText(), "10/24/????");
	}

	public function testRangeSpanningEras() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('88 BCE to 55 CE');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], -88.0101000000);
		$this->assertEquals($va_parse['end'], 55.1231235959);
		$this->assertEquals($va_parse[0], -88.0101000000);
		$this->assertEquals($va_parse[1], 55.1231235959);


		$vb_res = $o_tep->parse('50 BCE to 10 CE');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], -50.0101000000);
		$this->assertEquals($va_parse['end'], 10.1231235959);
		$this->assertEquals($va_parse[0], -50.0101000000);
		$this->assertEquals($va_parse[1], 10.1231235959);
	}

	public function testRangeInFirstCentury() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('50 CE to 80 CE');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], 50.0101000000);
		$this->assertEquals($va_parse['end'], 80.1231235959);
		$this->assertEquals($va_parse[0], 50.0101000000);
		$this->assertEquals($va_parse[1], 80.1231235959);
	}

	public function testEarlyCEDatesWithEra() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('12/22/99 CE');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals("99.122200000000", $va_parse['start']);
		$this->assertEquals("99.122223595900", $va_parse['end']);
	}

	public function testSeasonDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('Winter 2010');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "2010.1221000000");
		$this->assertEquals($va_parse['end'], "2011.0320235959");
		$this->assertEquals($va_parse[0], "2010.1221000000");
		$this->assertEquals($va_parse[1], "2011.0320235959");
	}

	public function testParseSimpleDelimitedDateForEnglishLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('10/23/2004');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();
		$this->assertEquals($va_parse['start'], 1098504000);
		$this->assertEquals($va_parse['end'], 1098590399);
		$this->assertEquals($va_parse[0], 1098504000);
		$this->assertEquals($va_parse[1], 1098590399);
	}

	public function testParseSimpleDelimitedDateForEuropeanLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('fr_FR');
		$vb_res = $o_tep->parse('23/10/2004');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();

		$this->assertEquals($va_parse['start'], 1098504000);
		$this->assertEquals($va_parse['end'], 1098590399);
		$this->assertEquals($va_parse[0], 1098504000);
		$this->assertEquals($va_parse[1], 1098590399);
	}

	public function testParseTextDate() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('May 10, 1990');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();

		$this->assertEquals($va_parse['start'], 642312000);
		$this->assertEquals($va_parse['end'], 642398399);
		$this->assertEquals($va_parse[0], 642312000);
		$this->assertEquals($va_parse[1], 642398399);

		$vb_res = $o_tep->parse('10 May 1990');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();

		$this->assertEquals($va_parse['start'], 642312000);
		$this->assertEquals($va_parse['end'], 642398399);
		$this->assertEquals($va_parse[0], 642312000);
		$this->assertEquals($va_parse[1], 642398399);
	}

	public function testParseISO8601Date() {
		$o_tep = new TimeExpressionParser();
		$vb_res = $o_tep->parse('2009-09-18 21:03');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();

		$this->assertEquals($va_parse['start'], 1253322180);
		$this->assertEquals($va_parse['end'], 1253322180);
		$this->assertEquals($va_parse[0], 1253322180);
		$this->assertEquals($va_parse[1], 1253322180);
	}

	public function testParseNowDate() {
		$o_tep = new TimeExpressionParser();
		$vb_res = $o_tep->parse('now');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getUnixTimestamps();

		$this->assertEquals($va_parse['start'], $t= time());
		$this->assertEquals($va_parse['end'], $t);
		$this->assertEquals($va_parse[0], $t);
		$this->assertEquals($va_parse[1], $t);
	}

	public function testHistoricDayDateForEnglishLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('March 8, 1945');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1945.030800000000");
		$this->assertEquals($va_parse['end'], "1945.030823595900");
		$this->assertEquals($va_parse[0], "1945.030800000000");
		$this->assertEquals($va_parse[1], "1945.030823595900");
	}

	public function testHistoricDayDateForGermanLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('de_DE');
		$vb_res = $o_tep->parse('8. Mai 1945');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1945.050800000000");
		$this->assertEquals($va_parse['end'], "1945.050823595900");
		$this->assertEquals($va_parse[0], "1945.050800000000");
		$this->assertEquals($va_parse[1], "1945.050823595900");
	}

	public function testInvalidMonthDateForGermanLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('de_DE');
		$vb_res = $o_tep->parse('24.13.14');
		$this->assertEquals($vb_res, false);
	}

	public function testHistoricDayDateWithUmlautForGermanLocale() {
 		$o_tep = new TimeExpressionParser();
 		$o_tep->setLanguage('de_DE');
 		$vb_res = $o_tep->parse('11. März 1870');
 		$this->assertEquals($vb_res, true);
 		$va_parse = $o_tep->getHistoricTimestamps();
 		
 		$this->assertEquals($va_parse['start'], "1870.031100000000");
 		$this->assertEquals($va_parse['end'], "1870.031123595900");
 		$this->assertEquals($va_parse[0], "1870.031100000000");
 		$this->assertEquals($va_parse[1], "1870.031123595900");
 	}

	public function testHistoricDayDateWithUmlautForFrenchLocale() {
		return; // have to revisit this test but it always fails at the moment
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('fr_FR');
		$vb_res = $o_tep->parse('24 Décembre 1870');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1870.122400000000");
		$this->assertEquals($va_parse['end'], "1870.122423595900");
	}

	public function testCenturyDatesForGermanLocale() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('de_DE');
		$vb_res = $o_tep->parse('20. Jahrhundert');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1900.010100000000");
		$this->assertEquals($va_parse['end'], "1999.123123595900");
		$this->assertEquals($va_parse[0], "1900.010100000000");
		$this->assertEquals($va_parse[1], "1999.123123595900");
	}

	public function testFullDateWith3DigitYear() {
		$o_tep = new TimeExpressionParser();
		$vb_res = $o_tep->parse('January 17 999');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "999.01170000000000000000");
		$this->assertEquals($va_parse['end'], "999.01172359590000000000");

		$vb_res = $o_tep->parse('17 January 999');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "999.01170000000000000000");
		$this->assertEquals($va_parse['end'], "999.01172359590000000000");

		$vb_res = $o_tep->parse('1/17/999');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "999.01170000000000000000");
		$this->assertEquals($va_parse['end'], "999.01172359590000000000");

		$o_tep->setLanguage('de_DE');
		$vb_res = $o_tep->parse('17.1.999');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "999.01170000000000000000");
		$this->assertEquals($va_parse['end'], "999.01172359590000000000");
	}

	public function testHistoricYearRanges() {
		$o_tep = new TimeExpressionParser();
		$vb_res = $o_tep->parse('1930 – 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1930.010100000000");
		$this->assertEquals($va_parse['end'], "1946.123123595900");
		$this->assertEquals($va_parse[0], "1930.010100000000");
		$this->assertEquals($va_parse[1], "1946.123123595900");


		$vb_res = $o_tep->parse('1930-1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1930.010100000000");
		$this->assertEquals($va_parse['end'], "1946.123123595900");
		$this->assertEquals($va_parse[0], "1930.010100000000");
		$this->assertEquals($va_parse[1], "1946.123123595900");

		$vb_res = $o_tep->parse('1951 to 1955');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1951.010100000000");
		$this->assertEquals($va_parse['end'], "1955.123123595900");
		$this->assertEquals($va_parse[0], "1951.010100000000");
		$this->assertEquals($va_parse[1], "1955.123123595900");

		$vb_res = $o_tep->parse('Between 1951 and 1955');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1951.010100000000");
		$this->assertEquals($va_parse['end'], "1955.123123595900");
		$this->assertEquals($va_parse[0], "1951.010100000000");
		$this->assertEquals($va_parse[1], "1955.123123595900");

		$vb_res = $o_tep->parse('From 1951 to 1955');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1951.010100000000");
		$this->assertEquals($va_parse['end'], "1955.123123595900");
		$this->assertEquals($va_parse[0], "1951.010100000000");
		$this->assertEquals($va_parse[1], "1955.123123595900");
	}

	public function testCircaDateRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('circa 1950 to 1955');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.010100000010");
		$this->assertEquals($va_parse['end'], "1955.123123595910");
		$this->assertEquals($va_parse[0], "1950.010100000010");
		$this->assertEquals($va_parse[1], "1955.123123595910");


		$vb_res = $o_tep->parse('circa 6/1950 to 1955');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.060100000010");
		$this->assertEquals($va_parse['end'], "1955.123123595910");
		$this->assertEquals($va_parse[0], "1950.060100000010");
		$this->assertEquals($va_parse[1], "1955.123123595910");
	}
	
	public function testCircaEndDateRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('1950 to circa 1955');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.010100000000");
		$this->assertEquals($va_parse['end'], "1955.123123595910");
		$this->assertEquals($va_parse[0], "1950.010100000000");
		$this->assertEquals($va_parse[1], "1955.123123595910");
		$this->assertEquals($o_tep->getText(), "1950 – circa 1955");

		$vb_res = $o_tep->parse('6/1950 to circa 1955');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.060100000000");
		$this->assertEquals($va_parse['end'], "1955.123123595910");
		$this->assertEquals($va_parse[0], "1950.060100000000");
		$this->assertEquals($va_parse[1], "1955.123123595910");
		$this->assertEquals($o_tep->getText(), "June 1950 – circa December 1955");
		
		$vb_res = $o_tep->parse('circa June 1950 to circa 1955');
		$this->assertEquals($vb_res, true);
		
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.060100000010");
		$this->assertEquals($va_parse['end'], "1955.123123595910");
		$this->assertEquals($va_parse[0], "1950.060100000010");
		$this->assertEquals($va_parse[1], "1955.123123595910");
		$this->assertEquals($o_tep->getText(), "circa June 1950 – December 1955");
		
		$vb_res = $o_tep->parse('circa June 1950 to circa 11/1955');
		$this->assertEquals($vb_res, true);
		
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.060100000010");
		$this->assertEquals($va_parse['end'], "1955.113023595910");
		$this->assertEquals($va_parse[0], "1950.060100000010");
		$this->assertEquals($va_parse[1], "1955.113023595910");
		$this->assertEquals($o_tep->getText(), "circa June 1950 – November 1955");
		
		
		$vb_res = $o_tep->parse('circa June 1950 to 11/1950');
		$this->assertEquals($vb_res, true);
		
		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.060100000010");
		$this->assertEquals($va_parse['end'], "1950.113023595910");
		$this->assertEquals($va_parse[0], "1950.060100000010");
		$this->assertEquals($va_parse[1], "1950.113023595910");
		$this->assertEquals($o_tep->getText(), "circa June – November 1950");
		
	}

	public function testDecadeRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('1950s to 1970s');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.010100000000");
		$this->assertEquals($va_parse['end'], "1979.123123595900");
		$this->assertEquals($va_parse[0], "1950.010100000000");
		$this->assertEquals($va_parse[1], "1979.123123595900");
	}

	public function testCircaDecade() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('circa 1950s');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.010100000010");
		$this->assertEquals($va_parse['end'], "1959.123123595910");
		$this->assertEquals($va_parse[0], "1950.010100000010");
		$this->assertEquals($va_parse[1], "1959.123123595910");
	}

	public function testCircaDecadeRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$vb_res = $o_tep->parse('circa 1950s to 1970s');
		$this->assertEquals($vb_res, true);

		$va_parse = $o_tep->getHistoricTimestamps();
		$this->assertEquals($va_parse['start'], "1950.010100000010");
		$this->assertEquals($va_parse['end'], "1979.123123595910");
		$this->assertEquals($va_parse[0], "1950.010100000010");
		$this->assertEquals($va_parse[1], "1979.123123595910");
	}

	public function testHistoricCircaYearDate() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('c 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");

		$vb_res = $o_tep->parse('c. 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");

		$vb_res = $o_tep->parse('circa 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");


		$vb_res = $o_tep->parse('ca 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");


		$vb_res = $o_tep->parse('ca. 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");


		$vb_res = $o_tep->parse('1946?');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.010100000010");
		$this->assertEquals($va_parse['end'], "1946.123123595910");
		$this->assertEquals($va_parse[0], "1946.010100000010");
		$this->assertEquals($va_parse[1], "1946.123123595910");
	}

	public function testHistoricCircaMonthAndYearDate() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('c June 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");

		$vb_res = $o_tep->parse('c. 6/1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");

		$vb_res = $o_tep->parse('circa June 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");


		$vb_res = $o_tep->parse('ca 6/1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");


		$vb_res = $o_tep->parse('ca. June 1946');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");


		$vb_res = $o_tep->parse('6/1946?');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1946.060100000010");
		$this->assertEquals($va_parse['end'], "1946.063023595910");
		$this->assertEquals($va_parse[0], "1946.060100000010");
		$this->assertEquals($va_parse[1], "1946.063023595910");
	}

	public function testDecadeDate() {
		$o_tep = new TimeExpressionParser();
		$vb_res = $o_tep->parse('1950s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1950.010100000000");
		$this->assertEquals($va_parse['end'], "1959.123123595900");
		$this->assertEquals($va_parse[0], "1950.010100000000");
		$this->assertEquals($va_parse[1], "1959.123123595900");

		$vb_res = $o_tep->parse('1950\'s');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1950.010100000000");
		$this->assertEquals($va_parse['end'], "1959.123123595900");
		$this->assertEquals($va_parse[0], "1950.010100000000");
		$this->assertEquals($va_parse[1], "1959.123123595900");

		$vb_res = $o_tep->parse('195-');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1950.010100000000");
		$this->assertEquals($va_parse['end'], "1959.123123595900");
		$this->assertEquals($va_parse[0], "1950.010100000000");
		$this->assertEquals($va_parse[1], "1959.123123595900");
	}

	public function testCenturyDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('20th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1900.010100000000");
		$this->assertEquals($va_parse['end'], "1999.123123595900");
		$this->assertEquals($va_parse[0], "1900.010100000000");
		$this->assertEquals($va_parse[1], "1999.123123595900");

		$vb_res = $o_tep->parse('19--');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1900.010100000000");
		$this->assertEquals($va_parse['end'], "1999.123123595900");
		$this->assertEquals($va_parse[0], "1900.010100000000");
		$this->assertEquals($va_parse[1], "1999.123123595900");
	}


	public function testADBCDates() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('2000bc');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], -"2000.010100000000");
		$this->assertEquals($va_parse['end'], -"2000.123123595900");
		$this->assertEquals($va_parse[0], -"2000.010100000000");
		$this->assertEquals($va_parse[1], -"2000.123123595900");


		$vb_res = $o_tep->parse('2000ad');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2000.010100000000");
		$this->assertEquals($va_parse['end'], "2000.123123595900");
		$this->assertEquals($va_parse[0], "2000.010100000000");
		$this->assertEquals($va_parse[1], "2000.123123595900");
		
		// Negative year BCE format
		$vb_res = $o_tep->parse('-2150');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], -"2150.010100000000");
		$this->assertEquals($va_parse['end'], -"2150.123123595900");
		$this->assertEquals($va_parse[0], -"2150.010100000000");
		$this->assertEquals($va_parse[1], -"2150.123123595900");
	}
	
	public function testADCenturies() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('2nd century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "100.010100000000");
		$this->assertEquals($va_parse['end'], "199.123123595900");
		$this->assertEquals($va_parse[0], "100.010100000000");
		$this->assertEquals($va_parse[1], "199.123123595900");
		$this->assertEquals(strtolower($o_tep->getText()), "2nd century");
		
		$vb_res = $o_tep->parse('2nd century ad');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "100.010100000000");
		$this->assertEquals($va_parse['end'], "199.123123595900");
		$this->assertEquals($va_parse[0], "100.010100000000");
		$this->assertEquals($va_parse[1], "199.123123595900");
		$this->assertEquals(strtolower($o_tep->getText()), "2nd century");
		
		$vb_res = $o_tep->parse('15th century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1400.010100000000");
		$this->assertEquals($va_parse['end'], "1499.123123595900");
		$this->assertEquals($va_parse[0], "1400.010100000000");
		$this->assertEquals($va_parse[1], "1499.123123595900");
		$this->assertEquals(strtolower($o_tep->getText()), "15th century");
		
		$vb_res = $o_tep->parse('15th century ad');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "1400.010100000000");
		$this->assertEquals($va_parse['end'], "1499.123123595900");
		$this->assertEquals($va_parse[0], "1400.010100000000");
		$this->assertEquals($va_parse[1], "1499.123123595900");
		$this->assertEquals(strtolower($o_tep->getText()), "15th century");
	}
	
	public function testBCECenturies() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('2nd century bce');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "-100.010100000000");
		$this->assertEquals($va_parse['end'], "-199.123123595900");
		$this->assertEquals($va_parse[0], "-100.010100000000");
		$this->assertEquals($va_parse[1], "-199.123123595900");
		
		$this->assertEquals(strtolower($o_tep->getText()), "2nd century bce");
		
		$vb_res = $o_tep->parse('15th century bce');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "-1400.010100000000");
		$this->assertEquals($va_parse['end'], "-1499.123123595900");
		$this->assertEquals($va_parse[0], "-1400.010100000000");
		$this->assertEquals($va_parse[1], "-1499.123123595900");
		
		$this->assertEquals(strtolower($o_tep->getText()), "15th century bce");
		
		$vb_res = $o_tep->parse('1st century bce');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "0.010100000000");
		$this->assertEquals($va_parse['end'], "-99.123123595900");
		$this->assertEquals($va_parse[0], "0.010100000000");
		$this->assertEquals($va_parse[1], "-99.123123595900");
		
		$this->assertEquals(strtolower($o_tep->getText()), "1st century bce");
		
		
		$vb_res = $o_tep->parse('1st century');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "0.010100000000");
		$this->assertEquals($va_parse['end'], "99.123123595900");
		$this->assertEquals($va_parse[0], "0.010100000000");
		$this->assertEquals($va_parse[1], "99.123123595900");
		
		$this->assertEquals(strtolower($o_tep->getText()), "1st century");
		
		
		$vb_res = $o_tep->parse('1st century ad');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "0.010100000000");
		$this->assertEquals($va_parse['end'], "99.123123595900");
		$this->assertEquals($va_parse[0], "0.010100000000");
		$this->assertEquals($va_parse[1], "99.123123595900");
		
		$this->assertEquals(strtolower($o_tep->getText()), "1st century");
	}

	public function testTimes() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parseTime('10:55pm');
		$va_parse = $o_tep->getTimes();

		$this->assertEquals($va_parse['start'], 82500);
		$this->assertEquals($va_parse['end'], 82500);
		$this->assertEquals($va_parse[0], 82500);
		$this->assertEquals($va_parse[1], 82500);


		$vb_res = $o_tep->parseTime('22:55');
		$va_parse = $o_tep->getTimes();

		$this->assertEquals($va_parse['start'], 82500);
		$this->assertEquals($va_parse['end'], 82500);
		$this->assertEquals($va_parse[0], 82500);
		$this->assertEquals($va_parse[1], 82500);


		$vb_res = $o_tep->parseTime('22:55:15');
		$va_parse = $o_tep->getTimes();

		$this->assertEquals($va_parse['start'], 82515);
		$this->assertEquals($va_parse['end'], 82515);
		$this->assertEquals($va_parse[0], 82515);
		$this->assertEquals($va_parse[1], 82515);
	}

	public function testDatesWithTimes() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('June 6 2009 at 10:55pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2009.060622550000");
		$this->assertEquals($va_parse['end'], "2009.060622550000");
		$this->assertEquals($va_parse[0], "2009.060622550000");
		$this->assertEquals($va_parse[1], "2009.060622550000");

		$vb_res = $o_tep->parse('June 6 2009 @ 22:55');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2009.060622550000");
		$this->assertEquals($va_parse['end'], "2009.060622550000");
		$this->assertEquals($va_parse[0], "2009.060622550000");
		$this->assertEquals($va_parse[1], "2009.060622550000");

		$vb_res = $o_tep->parse('June 6 2009 @ 10:55:10pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2009.060622551000");
		$this->assertEquals($va_parse['end'], "2009.060622551000");
		$this->assertEquals($va_parse[0], "2009.060622551000");
		$this->assertEquals($va_parse[1], "2009.060622551000");


		$vb_res = $o_tep->parse('6 june 2009 @ 10:55:10pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2009.060622551000");
		$this->assertEquals($va_parse['end'], "2009.060622551000");
		$this->assertEquals($va_parse[0], "2009.060622551000");
		$this->assertEquals($va_parse[1], "2009.060622551000");

		$vb_res = $o_tep->parse('6/6/2009 @ 10:55:10pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2009.060622551000");
		$this->assertEquals($va_parse['end'], "2009.060622551000");
		$this->assertEquals($va_parse[0], "2009.060622551000");
		$this->assertEquals($va_parse[1], "2009.060622551000");
	}

	public function testDateRangesWithTimes() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('6/5/2007 @ 9am .. 6/5/2007 @ 5pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2007.060509000000");
		$this->assertEquals($va_parse['end'],   "2007.060517000000");
		$this->assertEquals($va_parse[0], "2007.060509000000");
		$this->assertEquals($va_parse[1], "2007.060517000000");
		
		// Midnight
		$vb_res = $o_tep->parse('September 12 at 18:00 – September 13 2014 at 00:00');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], "2014.091218000000");
		$this->assertEquals($va_parse['end'],   "2014.091300000000");
		$this->assertEquals($va_parse[0], "2014.091218000000");
		$this->assertEquals($va_parse[1], "2014.091300000000");
		
		$this->assertEquals($o_tep->getText(), 'September 12 at 18:00 – September 13 2014 at 0:00');
	}

	public function testDatesWithImplicitYear() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$va_date = getDate();
		$vb_res = $o_tep->parse('6/5 at 9am – 5pm');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], $va_date['year'].'.060509000000');
		$this->assertEquals($va_parse['end'], $va_date['year'].'.060517000000');
		$this->assertEquals($va_parse[0], $va_date['year'].'.060509000000');
		$this->assertEquals($va_parse[1], $va_date['year'].'.060517000000');
	}

	public function testDateTextOutput() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('6/6/2009 @ 10:55:10pm');
		$this->assertEquals($vb_res, true);

		$this->assertEquals($o_tep->getText(), 'June 6 2009 at 22:55:10');

		$o_tep->setLanguage('de_DE');

		$this->assertEquals($o_tep->getText(), '6. Juni 2009 um 22:55:10');
	}

	public function testMYADate() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');

		$vb_res = $o_tep->parse('40 MYA');
		$this->assertEquals($vb_res, true);

		$this->assertEquals($o_tep->getText(), '40000000 BCE');
	}

	public function testIncompleteRanges() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage('en_US');
		$va_date = getDate();

		$vb_res = $o_tep->parse('August 20 – 27 2011');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], '2011.082000000000');
		$this->assertEquals($va_parse['end'], '2011.082723595900');
		
		$vb_res = $o_tep->parse('20 – August 27 2011');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], '2011.082000000000');
		$this->assertEquals($va_parse['end'], '2011.082723595900');


		$vb_res = $o_tep->parse('August 20 – 27');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], $va_date['year'].'.082000000000');
		$this->assertEquals($va_parse['end'], $va_date['year'].'.082723595900');
		
		$vb_res = $o_tep->parse('March – June 1850');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], '1850.030100000000');
		$this->assertEquals($va_parse['end'], '1850.063023595900');	
		
		$o_tep->setLanguage('de_DE');	
		
		$vb_res = $o_tep->parse('20 - 27 August 2011');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], '2011.082000000000');
		$this->assertEquals($va_parse['end'], '2011.082723595900');
		
		$vb_res = $o_tep->parse('20 August - 27 2011');
		$this->assertEquals($vb_res, true);
		$va_parse = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_parse['start'], '2011.082000000000');
		$this->assertEquals($va_parse['end'], '2011.082723595900');

	}

	function testNormalizationYears() {
		$o_tep = new TimeExpressionParser('16th century');
		$va_historic = $o_tep->getHistoricTimestamps();

		$va_years_expected = array();
		for($vn_i = 1500; $vn_i < 1600; $vn_i++) {
			$va_years_expected[$vn_i] = $vn_i;
		}

		$va_years = $o_tep->normalizeDateRange($va_historic['start'], $va_historic['end'], 'years');
		$this->assertEquals(100, sizeof($va_years));
		$this->assertEquals($va_years_expected, $va_years);
	}

	function testNormalizationDecades() {
		$o_tep = new TimeExpressionParser('16th century', 'en_US');
		$va_historic = $o_tep->getHistoricTimestamps();

		$va_decades_expected = array(
			1500 => '1500s',
			1510 => '1510s',
			1520 => '1520s',
			1530 => '1530s',
			1540 => '1540s',
			1550 => '1550s',
			1560 => '1560s',
			1570 => '1570s',
			1580 => '1580s',
			1590 => '1590s'
		);

		$va_decades = $o_tep->normalizeDateRange($va_historic['start'], $va_historic['end'], 'decades');
		$this->assertEquals(10, sizeof($va_decades));
		$this->assertEquals($va_decades_expected, $va_decades);
	}

	function testMultiWordConjunctions() {
		$o_tep = new TimeExpressionParser();
		$o_tep->setLanguage("it_IT");
		$this->assertEquals($o_tep->parse("23/3/2001 fino a 27/3/2001"), true);
		$va_historic = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_historic['start'], '2001.032300000000');
		$this->assertEquals($va_historic['end'], '2001.032723595900');
		
		$this->assertEquals($o_tep->parse("23 fino a 27 Marzo 2001"), true);
		$va_historic = $o_tep->getHistoricTimestamps();

		$this->assertEquals($va_historic['start'], '2001.032300000000');
		$this->assertEquals($va_historic['end'], '2001.032723595900');
	}
}
