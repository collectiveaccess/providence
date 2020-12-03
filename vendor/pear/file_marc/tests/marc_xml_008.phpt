--TEST--
marc_xml_008: generate a single collection of MARCXML records from a MARCXML record
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

$records = new File_MARCXML($dir . '/' . 'music.xml');

// Add the XML header and opening <collection> element
$records->toXMLHeader();

// Iterate through the retrieved records
while ($record = $records->next()) {

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
    print $record->toXML('UTF-8', true, false);
}
// Add the </collection> closing element and dump the XMLWriter contents
print $records->toXMLFooter();
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
 <record xmlns="http://www.loc.gov/MARC21/slim">
  <leader>01145ncm a2200277 i 4500</leader>
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
   <subfield code="a">Lewis, J. Django.--Lewis, J. Plastic dreams (music from the film Kemek).--Lewis, J. Dancing (music from the film Kemek).--Lewis, J. Blues in A minor.--Lewis, J. Blues in B́Ư.--Lewis, J. Precious joy.--Jackson, M. The martyr.--Jackson, M. The legendary profile.</subfield>
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
 <record xmlns="http://www.loc.gov/MARC21/slim">
  <leader>01293cjm a2200289 a 4500</leader>
  <controlfield tag="001">001878039</controlfield>
  <controlfield tag="005">20050110174900.0</controlfield>
  <controlfield tag="007">sd fungnn|||e|</controlfield>
  <controlfield tag="008">940202r19931981nyujzn   i              d</controlfield>
  <datafield tag="024" ind1="1" ind2=" ">
   <subfield code="a">7464573372</subfield>
  </datafield>
  <datafield tag="028" ind1="0" ind2="2">
   <subfield code="a">JK 57337</subfield>
   <subfield code="b">Red Baron</subfield>
  </datafield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="a">(OCoLC)29737267</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">SVP</subfield>
   <subfield code="c">SVP</subfield>
   <subfield code="d">LGG</subfield>
  </datafield>
  <datafield tag="100" ind1="1" ind2=" ">
   <subfield code="a">Desmond, Paul,</subfield>
   <subfield code="d">1924-</subfield>
  </datafield>
  <datafield tag="245" ind1="1" ind2="0">
   <subfield code="a">Paul Desmond &amp; the Modern Jazz Quartet</subfield>
   <subfield code="h">[sound recording]</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">New York, N.Y. :</subfield>
   <subfield code="b">Red Baron :</subfield>
   <subfield code="b">Manufactured by Sony Music Entertainment,</subfield>
   <subfield code="c">p1993.</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">1 sound disc (39 min.) :</subfield>
   <subfield code="b">digital ;</subfield>
   <subfield code="c">4 3/4 in.</subfield>
  </datafield>
  <datafield tag="511" ind1="0" ind2=" ">
   <subfield code="a">Paul Desmond, alto saxophone; Modern Jazz Quartet: John Lewis, piano; Milt Jackson, vibraphone; Percy Heath, bass; Connie Kay, drums.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">All arrangements by John Lewis.</subfield>
  </datafield>
  <datafield tag="518" ind1=" " ind2=" ">
   <subfield code="a">Recorded live on December 25, 1971 at Town Hall, NYC.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Originally released in 1981 by Finesse as LP FW 27487.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Program notes by Irving Townsend, June 1981, on container insert.</subfield>
  </datafield>
  <datafield tag="505" ind1="0" ind2=" ">
   <subfield code="a">Greensleeves -- You go to my head -- Blue dove -- Jesus Christ Superstar -- Here's that rainy day -- East of the sun -- Bags' new groove.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Jazz</subfield>
   <subfield code="y">1971-1980.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Lewis, John,</subfield>
   <subfield code="d">1920-</subfield>
  </datafield>
  <datafield tag="710" ind1="2" ind2=" ">
   <subfield code="a">Modern Jazz Quartet.</subfield>
  </datafield>
  <datafield tag="740" ind1="0" ind2=" ">
   <subfield code="a">Paul Desmond and the Modern Jazz Quartet.</subfield>
  </datafield>
 </record>
 <record xmlns="http://www.loc.gov/MARC21/slim">
  <leader>01829cjm a2200385 a 4500</leader>
  <controlfield tag="001">001964482</controlfield>
  <controlfield tag="005">20060626132700.0</controlfield>
  <controlfield tag="007">sd fzngnn|m|e|</controlfield>
  <controlfield tag="008">871211p19871957nyujzn                  d</controlfield>
  <datafield tag="024" ind1="1" ind2=" ">
   <subfield code="a">4228332902</subfield>
  </datafield>
  <datafield tag="028" ind1="0" ind2="1">
   <subfield code="a">833 290-2</subfield>
   <subfield code="b">Verve</subfield>
  </datafield>
  <datafield tag="033" ind1="0" ind2=" ">
   <subfield code="a">19571027</subfield>
   <subfield code="b">6299</subfield>
   <subfield code="c">D56</subfield>
  </datafield>
  <datafield tag="033" ind1="0" ind2=" ">
   <subfield code="a">196112--</subfield>
   <subfield code="b">3804</subfield>
   <subfield code="c">N4</subfield>
  </datafield>
  <datafield tag="033" ind1="0" ind2=" ">
   <subfield code="a">19571019</subfield>
   <subfield code="b">4104</subfield>
   <subfield code="c">C6</subfield>
  </datafield>
  <datafield tag="033" ind1="0" ind2=" ">
   <subfield code="a">197107--</subfield>
   <subfield code="b">6299</subfield>
   <subfield code="c">V7</subfield>
  </datafield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="a">(OCoLC)17222092</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">CPL</subfield>
   <subfield code="c">CPL</subfield>
   <subfield code="d">OCL</subfield>
   <subfield code="d">LGG</subfield>
  </datafield>
  <datafield tag="048" ind1=" " ind2=" ">
   <subfield code="a">pz01</subfield>
   <subfield code="a">ka01</subfield>
   <subfield code="a">sd01</subfield>
   <subfield code="a">pd01</subfield>
  </datafield>
  <datafield tag="110" ind1="2" ind2=" ">
   <subfield code="a">Modern Jazz Quartet.</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="245" ind1="1" ind2="4">
   <subfield code="a">The Modern Jazz Quartet plus</subfield>
   <subfield code="h">[sound recording].</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">[New York] :</subfield>
   <subfield code="b">Verve,</subfield>
   <subfield code="c">p1987.</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">1 sound disc :</subfield>
   <subfield code="b">digital ;</subfield>
   <subfield code="c">4 3/4 in.</subfield>
  </datafield>
  <datafield tag="440" ind1=" " ind2="0">
   <subfield code="a">Compact jazz</subfield>
  </datafield>
  <datafield tag="511" ind1="0" ind2=" ">
   <subfield code="a">Modern Jazz Quartet (principally) ; Milt Jackson, vibraphone (2nd and 8th works) ; Oscar Peterson, piano (2nd and 8th works) ; Ray Brown, bass (2nd and 8th works) ; Ed Thigpen (2nd work), Louis Hayes (8th work), drums.</subfield>
  </datafield>
  <datafield tag="518" ind1=" " ind2=" ">
   <subfield code="a">Recorded live, Oct. 27, 1957, at the Donaueschingen Jazz Festival (1st, 5th, 7th, and 10th works); Dec. 1961, in New York (2nd work); live, Oct. 19, 1957, at the Opera House, Chicago (3rd, 4th, 6th, and 9th works); July 1971, in Villingen, Germany (8th work).</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Compact disc.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Analog recording.</subfield>
  </datafield>
  <datafield tag="505" ind1="0" ind2=" ">
   <subfield code="a">The golden striker (4:08) -- On Green Dolphin Street (7:28) -- D &amp; E (4:55) -- I'll remember April (4:51) -- Cort©·ge (7:15) -- Now's the time (4:43) -- J.B. blues (5:09) -- Reunion blues (6:35) -- 'Round midnight (3:56) -- Three windows (7:20).</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Jazz.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Jackson, Milt.</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Peterson, Oscar,</subfield>
   <subfield code="d">1925-</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Brown, Ray,</subfield>
   <subfield code="d">1926-2002.</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Thigpen, Ed.</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Hayes, Louis,</subfield>
   <subfield code="d">1937-</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
  <datafield tag="852" ind1="8" ind2="0">
   <subfield code="b">MUSIC</subfield>
   <subfield code="c">Audio-Visual</subfield>
   <subfield code="h">CD 1131</subfield>
   <subfield code="4">Marvin Duchow Music</subfield>
   <subfield code="5">Audio-Visual</subfield>
  </datafield>
 </record>
</collection>
