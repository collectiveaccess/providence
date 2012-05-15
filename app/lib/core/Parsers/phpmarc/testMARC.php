<?php

require_once 'PHPUnit.php';
require_once 'MARC.php';

$record = "01201nam  2200253 a 4500";
$record .= "001001300000003000700013005001800020008004100038040001900079050002200098100005500120245011400175246005000289260005600339300005100395440003200446500017000478500011600648504007200764500002000836650002700856600003500883700002900918\x1e";
$record .= "tes96000001 \x1e";
$record .= "ViArRB\x1e";
$record .= "199602210153555.7\x1e";
$record .= "960221s1955    dcuabcdjdbkoqu001 0deng d\x1e";
$record .= "  \x1faViArRB\x1fcViArRB\x1e";
$record .= " 4\x1faPQ1234\x1fb.T39 1955\x1e";
$record .= "2 \x1faDeer-Doe, J.\x1fq(Jane),\x1fcsaint,\x1fd1355-1401,\x1fcspirit.\x1e";
$record .= "10\x1faNew test record number 1 with ordinary data\x1fh[large print] /\x1fcby Jane Deer-Doe ; edited by Patty O'Furniture.\x1e";
$record .= "1 \x1faNew test record number one with ordinary data\x1e";
$record .= "  \x1faWashington, DC :\x1fbLibrary of Congress,\x1fc1955-<1957>\x1e";
$record .= "  \x1fav. 1-<5> :\x1fbill., maps, ports., charts ;\x1fc cm.\x1e";
$record .= " 0\x1faTest record series ;\x1fvno. 1\x1e";
$record .= "  \x1faThis is a test of ordinary features like replacement of the mnemonics for currency and dollar signs and backslashes (backsolidus \\) used for blanks in certain areas.\x1e";
$record .= "  \x1faThis is a test for the conversion of curly braces; the opening curly brace ({) and the closing curly brace (}).\x1e";
$record .= "  \x1faIncludes Bibliographies, discographies, filmographies, and reviews.\x1e";
$record .= "  \x1faIncludes index.\x1e";
$record .= " 4\x1faTest record\x1fxJuvenile.\x1e";
$record .= "14\x1faDoe, John,\x1fd1955- \x1fxBiography.\x1e";
$record .= "1 \x1faO'Furniture, Patty,\x1feed.\x1e";
$record .= "\x1d";

$mnem = "=LDR  01201nam\\\\2200253 a 4500\n";
$mnem .= "=001  tes96000001 \n";
$mnem .= "=003  ViArRB\n";
$mnem .= "=005  199602210153555.7\n";
$mnem .= "=008  960221s1955\\\\\\\\dcuabcdjdbkoqu001 0deng d\n";
$mnem .= "=040  \\\\\$aViArRB\$cViArRB\n";
$mnem .= "=050  \\4\$aPQ1234\$b.T39 1955\n";
$mnem .= "=100  2\\\$aDeer-Doe, J.\$q(Jane),\$csaint,\$d1355-1401,\$cspirit.\n";
$mnem .= "=245  10\$aNew test record number 1 with ordinary data\$h[large print] /\$cby Jane Deer-Doe ; edited by Patty O'Furniture.\n";
$mnem .= "=246  1\\\$aNew test record number one with ordinary data\n";
$mnem .= "=260  \\\\\$aWashington, DC :\$bLibrary of Congress,\$c1955-<1957>\n";
$mnem .= "=300  \\\\\$av. 1-<5> :\$bill., maps, ports., charts ;\$c cm.\n";
$mnem .= "=440  \\0\$aTest record series ;\$vno. 1\n";
$mnem .= "=500  \\\\\$aThis is a test of ordinary features like replacement of the mnemonics for currency and dollar signs and backslashes (backsolidus {bsol}) used for blanks in certain areas.\n";
$mnem .= "=500  \\\\\$aThis is a test for the conversion of curly braces; the opening curly brace ({lcub}) and the closing curly brace ({rcub}).\n";
$mnem .= "=504  \\\\\$aIncludes Bibliographies, discographies, filmographies, and reviews.\n";
$mnem .= "=500  \\\\\$aIncludes index.\n";
$mnem .= "=650  \\4\$aTest record\$xJuvenile.\n";
$mnem .= "=600  14\$aDoe, John,\$d1955- \$xBiography.\n";
$mnem .= "=700  1\\\$aO'Furniture, Patty,\$eed.\n\n";

class MarcParserTest extends PHPUnit_TestCase {
	var $p;
	function setUp() {
		$this->p = new MarcParser();
	}
	function testParser() {
		global $record, $mnem;

		$this->assertTrue(is_a($this->p, 'MarcParser'));
		if (!is_a($this->p, 'MarcParser')) {
			return;
		}
		$this->assertEquals(1, $this->p->parse($record));
		$this->assertEquals(0, $this->p->eof());
		$this->assertEquals(1, count($this->p->records));
		$r = $this->p->records[0];
		$this->assertTrue(is_a($r, 'MarcRecord'));
		if (is_a($r, 'MarcRecord')) {
			list($rec, $err) = $r->get();
			$this->assertFalse($err);
			$this->assertEquals($record, $rec);
			$this->assertEquals($mnem, $r->getMnem());
		}
	}
}
class MarcMnemParserTest extends PHPUnit_TestCase {
	var $p;
	function setUp() {
		$this->p = new MarcMnemParser();
	}
	function testParser() {
		global $record, $mnem;

		$this->assertTrue(is_a($this->p, 'MarcMnemParser'));
		if (!is_a($this->p, 'MarcMnemParser')) {
			return;
		}
		$this->assertEquals(1, $this->p->parse($mnem));
		$this->assertEquals(0, $this->p->eof());
		$this->assertEquals(1, count($this->p->records));
		$r = $this->p->records[0];
		$this->assertTrue(is_a($r, 'MarcRecord'));
		if (is_a($r, 'MarcRecord')) {
			list($rec, $err) = $r->get();
			$this->assertFalse($err);
			$this->assertEquals($record, $rec);
			$this->assertEquals($mnem, $r->getMnem());
		}
	}
}

$suite = new PHPUnit_TestSuite('MarcParserTest');
$result = PHPUnit::run($suite);
echo $result->toString();
$suite = new PHPUnit_TestSuite('MarcMnemParserTest');
$result = PHPUnit::run($suite);
echo $result->toString();
?>
