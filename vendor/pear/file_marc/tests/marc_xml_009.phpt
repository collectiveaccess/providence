--TEST--
marc_xml_009: convert a MARCXML record with an overly long leader to MARC
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARCXML.php';
$marc_file = new File_MARCXML($dir . '/' . 'bad_leader.xml');

while ($marc_record = $marc_file->next()) {
  print $marc_record->toRaw();
}
?>
--EXPECT--
00749cam a2200241 454500001001400000003000600014005001700020008004100037020001800078020001500096035002100111040003600132050002400168100002500192245007800217260003700295300002100332504004100353650001700394650002200411852004800433901002600481LIBN539044247OCoLC20081030150430.0070630|||||    |||           000 0 eng d  a9781856075442  a1856075443  a(OCoLC)156822300  aBTCTAcBTCTAdYDXCPdBAKERdEMT 4aBL2747.2b.W45 20061 aWhite, Stephen Ross.10aSpace for unknowing :bthe place of agnosis in faith /cStephen R. White.  aDublin :bColumba Press,cc2006.  a160 p. ;c22 cm.  aIncludes bibliographical references. 0aAgnosticism. 0aBelief and doubt.  a1h230 WHIp11111027105040t65112549p26.95  aLIBN539044247bSystem
