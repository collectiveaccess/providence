--TEST--
marc_019: generate a MARCXML record not in a collection element
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

$records = new File_MARC($dir . '/' . 'music.mrc');

// Iterate through the retrieved records
$record = $records->next();

// Change each 852 $c to "Audio-Visual"
$holdings = $record->getFields('852');
foreach ($holdings as $holding) {

    // Get the $c subfields from this field
    $formats = $holding->getSubfields('c');
    foreach ($formats as $format) {
        if ($format->getData('AV')) {
            $format->setData('Audio-Visual');
        }
    }
}

// Generate the XML output for this record
print($record->toXML('UTF-8', true, true));
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
 <record>
  <leader>01145ncm  2200277 i 4500</leader>
  <controlfield tag="001">000073594</controlfield>
  <controlfield tag="004">AAJ5802</controlfield>
  <controlfield tag="005">20030415102100.0</controlfield>
  <controlfield tag="008">801107s1977    nyujza                   </controlfield>
  <datafield tag="010" ind1=" " ind2=" ">
   <subfield code="a">   77771106 </subfield>
  </datafield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="a">(CaOTUIC)15460184</subfield>
  </datafield>
  <datafield tag="035" ind1="9" ind2=" ">
   <subfield code="a">AAJ5802</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">LC</subfield>
  </datafield>
  <datafield tag="050" ind1="0" ind2="0">
   <subfield code="a">M1366</subfield>
   <subfield code="b">.M62</subfield>
   <subfield code="d">M1527.2</subfield>
  </datafield>
  <datafield tag="245" ind1="0" ind2="4">
   <subfield code="a">The Modern Jazz Quartet :</subfield>
   <subfield code="b">The legendary profile. --</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">New York :</subfield>
   <subfield code="b">M.J.Q. Music,</subfield>
   <subfield code="c">c1977.</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">score (72 p.) ;</subfield>
   <subfield code="c">31 cm.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">For piano, vibraphone, drums, and double bass.</subfield>
  </datafield>
  <datafield tag="505" ind1="0" ind2=" ">
   <subfield code="a">Lewis, J. Django.--Lewis, J. Plastic dreams (music from the film Kemek).--Lewis, J. Dancing (music from the film Kemek).--Lewis, J. Blues in A minor.--Lewis, J. Blues in B♭.--Lewis, J. Precious joy.--Jackson, M. The martyr.--Jackson, M. The legendary profile.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Jazz.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Motion picture music</subfield>
   <subfield code="v">Excerpts</subfield>
   <subfield code="v">Scores.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2="2">
   <subfield code="a">Lewis, John,</subfield>
   <subfield code="d">1920-</subfield>
   <subfield code="t">Selections.</subfield>
   <subfield code="f">1977.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2="2">
   <subfield code="a">Jackson, Milt.</subfield>
   <subfield code="t">Martyrs.</subfield>
   <subfield code="f">1977.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2="2">
   <subfield code="a">Jackson, Milt.</subfield>
   <subfield code="t">Legendary profile.</subfield>
   <subfield code="f">1977.</subfield>
  </datafield>
  <datafield tag="740" ind1="4" ind2=" ">
   <subfield code="a">The legendary profile.</subfield>
  </datafield>
  <datafield tag="852" ind1="0" ind2="0">
   <subfield code="b">MUSIC</subfield>
   <subfield code="c">Audio-Visual</subfield>
   <subfield code="k">folio</subfield>
   <subfield code="h">M1366</subfield>
   <subfield code="i">M62</subfield>
   <subfield code="9">1</subfield>
   <subfield code="4">Marvin Duchow Music</subfield>
   <subfield code="5"></subfield>
  </datafield>
 </record>
</collection>
