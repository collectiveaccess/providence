<?php
/** ---------------------------------------------------------------------
 * tests/lib/DataMigrationUtilsTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');

class DataMigrationUtilsTest extends TestCase {

	public function testSplitEntityNamesBasic() {
		$r = DataMigrationUtils::splitEntityName("George Tilyou");
		$this->_checkValue($r, ['surname' => 'Tilyou', 'forename' => 'George', 'displayname' => 'George Tilyou']);
		
		$r = DataMigrationUtils::splitEntityName("George C. Tilyou");
		$this->_checkValue($r, ['surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'George C. Tilyou']);

		$r = DataMigrationUtils::splitEntityName("Mr. George C. Tilyou");
		$this->_checkValue($r, ['prefix' => 'Mr.', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'Mr. George C. Tilyou']);

		$r = DataMigrationUtils::splitEntityName("Mr George C. Tilyou");
		$this->_checkValue($r, ['prefix' => 'Mr', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'Mr George C. Tilyou']);

		$r = DataMigrationUtils::splitEntityName("George C. Tilyou esq");
		$this->_checkValue($r, ['prefix' => '', 'suffix' => 'esq', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'George C. Tilyou esq']);

		$r = DataMigrationUtils::splitEntityName("George C. Tilyou Esq.");
		$this->_checkValue($r, ['prefix' => '', 'suffix' => 'Esq.', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'George C. Tilyou Esq.']);

		$r = DataMigrationUtils::splitEntityName("George C. Tilyou, Esq.");
		$this->_checkValue($r, ['prefix' => '', 'suffix' => 'Esq.', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'George C. Tilyou, Esq.']);

		$r = DataMigrationUtils::splitEntityName("Mr. George C. Tilyou, Esq.");
		$this->_checkValue($r, ['prefix' => 'Mr.', 'suffix' => 'Esq.', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'Mr. George C. Tilyou, Esq.']);

		$r = DataMigrationUtils::splitEntityName("Mr. George C. Tilyou PhD");
		$this->_checkValue($r, ['prefix' => 'Mr.', 'suffix' => 'PhD', 'surname' => 'Tilyou', 'forename' => 'George', 'middlename' => 'C.', 'displayname' => 'Mr. George C. Tilyou PhD']);

	}
	
	public function testSplitEntityNamesAmpersands() {
		$r = DataMigrationUtils::splitEntityName("Jane and Bob Doe");
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane and Bob', 'displayname' => 'Jane and Bob Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Jane & Bob Doe");
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane & Bob', 'displayname' => 'Jane & Bob Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Doe, Jane and Bob");
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane and Bob', 'displayname' => 'Doe, Jane and Bob']);
		
		$r = DataMigrationUtils::splitEntityName("Doe, Jane & Bob");
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane & Bob', 'displayname' => 'Doe, Jane & Bob']);		
		
		$r = DataMigrationUtils::splitEntityName("Dr Jane and Bob Doe");
		$this->_checkValue($r, ['prefix' => 'Dr', 'surname' => 'Doe', 'forename' => 'Jane and Bob', 'displayname' => 'Dr Jane and Bob Doe']);	
	}
	
	public function testAmbiguousBoundaries() {
		$r = DataMigrationUtils::splitEntityName("Jane B Van Doe");
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'B', 'displayname' => 'Jane B Van Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Van Doe, Jane B");
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'B', 'displayname' => 'Van Doe, Jane B']);			
	}
	
	public function testSplitEntityNamesCorporateNames() {
		$r = DataMigrationUtils::splitEntityName("Steeplchase Amusements, Inc.");
		$this->_checkValue($r, ['suffix' => 'Inc.', 'surname' => 'Steeplchase Amusements', 'forename' => '', 'displayname' => 'Steeplchase Amusements, Inc.']);

		$r = DataMigrationUtils::splitEntityName("Steeplchase Amusements LLC");
		$this->_checkValue($r, ['suffix' => 'LLC', 'surname' => 'Steeplchase Amusements', 'forename' => '', 'displayname' => 'Steeplchase Amusements LLC']);

		// Test that corporate suffix always forces treatment as org name (surname contains full name)
		$r = DataMigrationUtils::splitEntityName("Mr. Steeplchase Amusements LLC");
		$this->_checkValue($r, ['prefix' => 'Mr.', 'suffix' => 'LLC', 'surname' => 'Steeplchase Amusements', 'forename' => '', 'displayname' => 'Mr. Steeplchase Amusements LLC']);
	}
	
	public function testSurnamePrefixHandling() {
		$r = DataMigrationUtils::splitEntityName("Jane Van Doe");
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Jane Van Doe']);

		$r = DataMigrationUtils::splitEntityName("Emil Van Der Kleij");
		$this->_checkValue($r, ['surname' => 'Van Der Kleij', 'forename' => 'Emil', 'middlename' => '', 'displayname' => 'Emil Van Der Kleij']);

		$r = DataMigrationUtils::splitEntityName("Emil Lejetøj Van Der Kleij");
		$this->_checkValue($r, ['surname' => 'Van Der Kleij', 'forename' => 'Emil', 'middlename' => 'Lejetøj', 'displayname' => 'Emil Lejetøj Van Der Kleij']);
		
		$r = DataMigrationUtils::splitEntityName("Van Der Kleij, Emil");
		$this->_checkValue($r, ['surname' => 'Van Der Kleij', 'forename' => 'Emil', 'middlename' => '', 'displayname' => 'Van Der Kleij, Emil']);

		$r = DataMigrationUtils::splitEntityName("Der Kleij, Emil Van");
		$this->_checkValue($r, ['surname' => 'Der Kleij', 'forename' => 'Emil', 'middlename' => 'Van', 'displayname' => 'Der Kleij, Emil Van']);	
	}
	
	public function testDisplaynameFormatting() {
		/*		locale = locale code to use when applying rules; if omitted current user locale is employed
		 *		displaynameFormat = surnameCommaForename, forenameCommaSurname, forenameSurname, forenamemiddlenamesurname, original [Default = original]
		 *		doNotParse = Use name as-is in the surname and display name fields. All other fields are blank. [Default = false]
		 */
		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Van Doe, Jane']);
		
		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => 'forenameCommaSurname']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Jane, Van Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => 'forenameSurname']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Jane Van Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => 'forenamemiddlenamesurname']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Jane Erin Van Doe']);
		
		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => 'original']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Jane Erin Van Doe']);
		

		$r = DataMigrationUtils::splitEntityName("Jane Erin Van Doe", ['displaynameFormat' => '^middlename / ^surname / ^forename']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Jane', 'middlename' => 'Erin', 'displayname' => 'Erin / Van Doe / Jane']);
	}
	
	public function testAmpersandWithSurnamePrefix() {
		$r = DataMigrationUtils::splitEntityName("Bob and Jane Van Doe", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Bob and Jane', 'middlename' => '', 'displayname' => 'Van Doe, Bob and Jane']);
		
		$r = DataMigrationUtils::splitEntityName("Bob & Jane Van Doe", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Doe', 'forename' => 'Bob & Jane', 'middlename' => '', 'displayname' => 'Van Doe, Bob & Jane']);	
	}
	
	public function testPrefixInCommaDelimited() {
		$r = DataMigrationUtils::splitEntityName("Doe, Ms Jane", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => 'Ms']);
		
		$r = DataMigrationUtils::splitEntityName("Doe, Ms. Jane", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => 'Ms.']);
		
		$r = DataMigrationUtils::splitEntityName("Van Der Doe, Ms. Jane Alice", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice', 'displayname' => 'Van Der Doe, Jane', 'prefix' => 'Ms.']);
		
	}
	
	public function testSuffixInCommaDelimited() {
		$r = DataMigrationUtils::splitEntityName("Doe Phd, Jane", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => 'Phd']);

		$r = DataMigrationUtils::splitEntityName("Doe Phd, Jane Alice Erin", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => 'Phd']);
	
	}
	
	public function testPrefixSuffixInCommaDelimited() {
		$r = DataMigrationUtils::splitEntityName("Doe Phd, Ms Jane", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => 'Ms', 'suffix' => 'Phd']);

		$r = DataMigrationUtils::splitEntityName("Doe Phd, Ms. Jane Alice Erin", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin', 'displayname' => 'Doe, Jane', 'prefix' => 'Ms.', 'suffix' => 'Phd']);
	}
	
	public function testOrderOfSuffixinCommaDelimited() {
		$r = DataMigrationUtils::splitEntityName("Doe Phd, Jane", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => 'Phd']);

		$r = DataMigrationUtils::splitEntityName("Doe, Jane Phd", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => '', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => 'Phd']);
	}
	
	public function testLongNames() {
		$r = DataMigrationUtils::splitEntityName("Doe, Jane Alice Erin Dalhia", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => '']);

		$r = DataMigrationUtils::splitEntityName("Jane Alice Erin Dalhia Doe", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Doe, Jane', 'prefix' => '', 'suffix' => '']);

		$r = DataMigrationUtils::splitEntityName("Van Der Doe, Jane Alice Erin Dalhia", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => '', 'suffix' => '']);

		$r = DataMigrationUtils::splitEntityName("Jane Alice Erin Dalhia Van Der Doe", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => '', 'suffix' => '']);
	
		$r = DataMigrationUtils::splitEntityName("Van Der Doe Esq, Ms. Jane Alice Erin Dalhia", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => 'Ms.', 'suffix' => 'Esq']);
		
		$r = DataMigrationUtils::splitEntityName("Van Der Doe, Ms. Jane Alice Erin Dalhia, Esq", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => 'Ms.', 'suffix' => 'Esq']);

		$r = DataMigrationUtils::splitEntityName("Van Der Doe, Ms. Jane Alice Erin Dalhia Esq", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => 'Ms.', 'suffix' => 'Esq']);

		$r = DataMigrationUtils::splitEntityName("Ms. Jane Alice Erin Dalhia Van Der Doe, Esq", ['displaynameFormat' => 'surnameCommaForename']);
		$this->_checkValue($r, ['surname' => 'Van Der Doe', 'forename' => 'Jane', 'middlename' => 'Alice Erin Dalhia', 'displayname' => 'Van Der Doe, Jane', 'prefix' => 'Ms.', 'suffix' => 'Esq']);	
	}
	
	public function testChineseNames() {
		$r = DataMigrationUtils::splitEntityName("蔡国强");
		$this->_checkValue($r, ['surname' => '蔡', 'forename' => '国强', 'middlename' => '', 'displayname' => '蔡国强', 'prefix' => '', 'suffix' => '']);
	}
	
	public function testJapaneseNames() {
		$r = DataMigrationUtils::splitEntityName("野口 勇");
		$this->_checkValue($r, ['surname' => '野口', 'forename' => '勇', 'middlename' => '', 'displayname' => '野口 勇', 'prefix' => '', 'suffix' => '']);
		
		$r = DataMigrationUtils::splitEntityName("小野 洋子");
		$this->_checkValue($r, ['surname' => '小野', 'forename' => '洋子', 'middlename' => '', 'displayname' => '小野 洋子', 'prefix' => '', 'suffix' => '']);
	}
	
	public function testKoreanNames() {
		$r = DataMigrationUtils::splitEntityName("김민수");
		$this->_checkValue($r, ['surname' => '김', 'forename' => '민수', 'middlename' => '', 'displayname' => '김민수', 'prefix' => '', 'suffix' => '']);
		
	}
	
	
	/**
	 * Verify presence of expected keys and test returned values against expected values
	 */
	private function _checkValue(array $value, array $map): bool {
		$fields = ['surname', 'forename', 'middlename', 'displayname', 'prefix', 'suffix'];
		foreach($fields as $f) {
			$this->assertArrayHasKey($f, $value);
			$this->assertEquals((string)$map[$f], $value[$f], "Testing field {$f}");
		}
		return true;
	}
}
