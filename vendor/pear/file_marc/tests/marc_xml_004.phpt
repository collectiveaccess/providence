--TEST--
marc_xml_004: test conversion to XML of subfields that need to be escaped
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
$marc_file = new File_MARC($dir . '/' . 'xmlescape.mrc');

while ($marc_record = $marc_file->next()) {
  print $marc_record->toXML();
  print "\n";
}
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
 <record>
  <leader>00727nam  2200205 a 4500</leader>
  <controlfield tag="001">03-0016458</controlfield>
  <controlfield tag="005">19971103184734.0</controlfield>
  <controlfield tag="008">970701s1997    oru          u000 0 eng u</controlfield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="a">(Sirsi) a351664</subfield>
  </datafield>
  <datafield tag="050" ind1="0" ind2="0">
   <subfield code="a">ML270.2</subfield>
   <subfield code="b">.A6 1997</subfield>
  </datafield>
  <datafield tag="100" ind1="1" ind2=" ">
   <subfield code="a">Anthony, James R.</subfield>
  </datafield>
  <datafield tag="245" ind1="0" ind2="0">
   <subfield code="a">French baroque music from Beaujoyeulx to Rameau</subfield>
  </datafield>
  <datafield tag="250" ind1=" " ind2=" ">
   <subfield code="a">Rev. and expanded ed.</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">Portland, OR :</subfield>
   <subfield code="b">Amadeus Press,</subfield>
   <subfield code="c">1997.</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">586 p. :</subfield>
   <subfield code="b">music</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Music</subfield>
   <subfield code="&lt;">France</subfield>
   <subfield code="y">16th century</subfield>
   <subfield code="x">History and criticism.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Music</subfield>
   <subfield code="z">France</subfield>
   <subfield code="y">17th century</subfield>
   <subfield code="x">History and criticism.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Music</subfield>
   <subfield code="z">France</subfield>
   <subfield code="y">18th century</subfield>
   <subfield code="x">History and criticism.</subfield>
  </datafield>
  <datafield tag="949" ind1=" " ind2=" ">
   <subfield code="a">ML 270.2 A6 1997</subfield>
   <subfield code="w">LC</subfield>
   <subfield code="i">30007006841505</subfield>
   <subfield code="r">Y</subfield>
   <subfield code="t">BOOKS</subfield>
   <subfield code="l">HUNT-CIRC</subfield>
   <subfield code="m">HUNTINGTON</subfield>
  </datafield>
  <datafield tag="596" ind1=" " ind2=" ">
   <subfield code="a">1</subfield>
  </datafield>
 </record>
</collection>
