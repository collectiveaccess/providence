--TEST--
marc_xml_012: load from SimpleXMLElement object
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

$xml_obj = simplexml_load_file($dir . '/namespace.xml', "SimpleXMLElement", 0, "http://www.loc.gov/MARC21/slim", false);

$marc_file = new File_MARCXML($xml_obj);
$marc_file->toXMLHeader();
while ($marc_record = $marc_file->next()) {
  print $marc_record->toXML('UTF-8', true, false);
}
print $marc_file->toXMLFooter();

?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
 <record xmlns="http://www.loc.gov/MARC21/slim">
  <leader>00925njm  22002777a 4500</leader>
  <controlfield tag="001">5637241</controlfield>
  <controlfield tag="003">DLC</controlfield>
  <controlfield tag="005">19920826084036.0</controlfield>
  <controlfield tag="007">sdubumennmplu</controlfield>
  <controlfield tag="008">910926s1957    nyuuun              eng  </controlfield>
  <datafield tag="010" ind1=" " ind2=" ">
   <subfield code="a">   91758335 </subfield>
  </datafield>
  <datafield tag="028" ind1="0" ind2="0">
   <subfield code="a">1259</subfield>
   <subfield code="b">Atlantic</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">DLC</subfield>
   <subfield code="c">DLC</subfield>
  </datafield>
  <datafield tag="050" ind1="0" ind2="0">
   <subfield code="a">Atlantic 1259</subfield>
  </datafield>
  <datafield tag="245" ind1="0" ind2="4">
   <subfield code="a">The Great Ray Charles</subfield>
   <subfield code="h">[sound recording].</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">New York, N.Y. :</subfield>
   <subfield code="b">Atlantic,</subfield>
   <subfield code="c">[1957?]</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">1 sound disc :</subfield>
   <subfield code="b">analog, 33 1/3 rpm ;</subfield>
   <subfield code="c">12 in.</subfield>
  </datafield>
  <datafield tag="511" ind1="0" ind2=" ">
   <subfield code="a">Ray Charles, piano &amp; celeste.</subfield>
  </datafield>
  <datafield tag="505" ind1="0" ind2=" ">
   <subfield code="a">The Ray -- My melancholy baby -- Black coffee -- There's no you -- Doodlin' -- Sweet sixteen bars -- I surrender dear -- Undecided.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Brief record.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Jazz</subfield>
   <subfield code="y">1951-1960.</subfield>
  </datafield>
  <datafield tag="650" ind1=" " ind2="0">
   <subfield code="a">Piano with jazz ensemble.</subfield>
  </datafield>
  <datafield tag="700" ind1="1" ind2=" ">
   <subfield code="a">Charles, Ray,</subfield>
   <subfield code="d">1930-</subfield>
   <subfield code="4">prf</subfield>
  </datafield>
 </record>
 <record xmlns="http://www.loc.gov/MARC21/slim">
  <leader>01832cmma 2200349 a 4500</leader>
  <controlfield tag="001">12149120</controlfield>
  <controlfield tag="005">20001005175443.0</controlfield>
  <controlfield tag="007">cr |||</controlfield>
  <controlfield tag="008">000407m19949999dcu    g   m        eng d</controlfield>
  <datafield tag="906" ind1=" " ind2=" ">
   <subfield code="a">0</subfield>
   <subfield code="b">ibc</subfield>
   <subfield code="c">copycat</subfield>
   <subfield code="d">1</subfield>
   <subfield code="e">ncip</subfield>
   <subfield code="f">20</subfield>
   <subfield code="g">y-gencompf</subfield>
  </datafield>
  <datafield tag="925" ind1="0" ind2=" ">
   <subfield code="a">undetermined</subfield>
   <subfield code="x">web preservation project (wpp)</subfield>
  </datafield>
  <datafield tag="955" ind1=" " ind2=" ">
   <subfield code="a">vb07 (stars done) 08-19-00 to HLCD lk00; AA3s lk29 received for subject Aug 25, 2000; to DEWEY 08-25-00; aa11 08-28-00</subfield>
  </datafield>
  <datafield tag="010" ind1=" " ind2=" ">
   <subfield code="a">   00530046 </subfield>
  </datafield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="a">(OCoLC)ocm44279786</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">IEU</subfield>
   <subfield code="c">IEU</subfield>
   <subfield code="d">N@F</subfield>
   <subfield code="d">DLC</subfield>
  </datafield>
  <datafield tag="042" ind1=" " ind2=" ">
   <subfield code="a">lccopycat</subfield>
  </datafield>
  <datafield tag="043" ind1=" " ind2=" ">
   <subfield code="a">n-us-dc</subfield>
   <subfield code="a">n-us---</subfield>
  </datafield>
  <datafield tag="050" ind1="0" ind2="0">
   <subfield code="a">F204.W5</subfield>
  </datafield>
  <datafield tag="082" ind1="1" ind2="0">
   <subfield code="a">975.3</subfield>
   <subfield code="2">13</subfield>
  </datafield>
  <datafield tag="245" ind1="0" ind2="4">
   <subfield code="a">The White House</subfield>
   <subfield code="h">[computer file].</subfield>
  </datafield>
  <datafield tag="256" ind1=" " ind2=" ">
   <subfield code="a">Computer data.</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">Washington, D.C. :</subfield>
   <subfield code="b">White House Web Team,</subfield>
   <subfield code="c">1994-</subfield>
  </datafield>
  <datafield tag="538" ind1=" " ind2=" ">
   <subfield code="a">Mode of access: Internet.</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Title from home page as viewed on Aug. 19, 2000.</subfield>
  </datafield>
  <datafield tag="520" ind1="8" ind2=" ">
   <subfield code="a">Features the White House. Highlights the Executive Office of the President, which includes senior policy advisors and offices responsible for the President's correspondence and communications, the Office of the Vice President, and the Office of the First Lady. Posts contact information via mailing address, telephone and fax numbers, and e-mail. Contains the Interactive Citizens' Handbook with information on health, travel and tourism, education and training, and housing. Provides a tour and the history of the White House. Links to White House for Kids.</subfield>
  </datafield>
  <datafield tag="610" ind1="2" ind2="0">
   <subfield code="a">White House (Washington, D.C.)</subfield>
  </datafield>
  <datafield tag="610" ind1="1" ind2="0">
   <subfield code="a">United States.</subfield>
   <subfield code="b">Executive Office of the President.</subfield>
  </datafield>
  <datafield tag="610" ind1="1" ind2="0">
   <subfield code="a">United States.</subfield>
   <subfield code="b">Office of the Vice President.</subfield>
  </datafield>
  <datafield tag="610" ind1="1" ind2="0">
   <subfield code="a">United States.</subfield>
   <subfield code="b">Office of the First Lady.</subfield>
  </datafield>
  <datafield tag="710" ind1="2" ind2=" ">
   <subfield code="a">White House Web Team.</subfield>
  </datafield>
  <datafield tag="856" ind1="4" ind2="0">
   <subfield code="u">http://www.whitehouse.gov</subfield>
  </datafield>
  <datafield tag="856" ind1="4" ind2="0">
   <subfield code="u">http://lcweb.loc.gov/staff/wpp/whitehouse.html</subfield>
   <subfield code="z">Web site archive</subfield>
  </datafield>
 </record>
</collection>
