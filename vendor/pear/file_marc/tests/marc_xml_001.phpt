--TEST--
marc_xml_001: iterate and pretty print a MARC record
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
$marc_file = new File_MARC($dir . '/' . 'example.mrc');

while ($marc_record = $marc_file->next()) {
  /* Note that this adds characters to the leader to satisfy MARCXML schema */
  print $marc_record->toXML();
  print "\n";
}
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<collection xmlns="http://www.loc.gov/MARC21/slim">
 <record>
  <leader>01850na   2200517   4500</leader>
  <controlfield tag="001">0000000044</controlfield>
  <controlfield tag="003">EMILDA</controlfield>
  <controlfield tag="008">980120s1998    fi     j      000 0 swe</controlfield>
  <datafield tag="020" ind1=" " ind2=" ">
   <subfield code="a">9515008808</subfield>
   <subfield code="c">FIM 72:00</subfield>
  </datafield>
  <datafield tag="035" ind1=" " ind2=" ">
   <subfield code="9">9515008808</subfield>
  </datafield>
  <datafield tag="040" ind1=" " ind2=" ">
   <subfield code="a">NB</subfield>
  </datafield>
  <datafield tag="042" ind1=" " ind2=" ">
   <subfield code="9">NB</subfield>
   <subfield code="9">SEE</subfield>
  </datafield>
  <datafield tag="084" ind1=" " ind2=" ">
   <subfield code="a">Hcd,u</subfield>
   <subfield code="2">kssb/6</subfield>
  </datafield>
  <datafield tag="084" ind1=" " ind2=" ">
   <subfield code="5">NB</subfield>
   <subfield code="a">uHc</subfield>
   <subfield code="2">kssb</subfield>
  </datafield>
  <datafield tag="084" ind1=" " ind2=" ">
   <subfield code="5">SEE</subfield>
   <subfield code="a">Hcf</subfield>
   <subfield code="2">kssb/6</subfield>
  </datafield>
  <datafield tag="084" ind1=" " ind2=" ">
   <subfield code="5">Q</subfield>
   <subfield code="a">Hcd,uf</subfield>
   <subfield code="2">kssb/6</subfield>
  </datafield>
  <datafield tag="100" ind1="1" ind2=" ">
   <subfield code="a">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="245" ind1="0" ind2="4">
   <subfield code="a">Det osynliga barnet och andra berättelser /</subfield>
   <subfield code="c">Tove Jansson</subfield>
  </datafield>
  <datafield tag="250" ind1=" " ind2=" ">
   <subfield code="a">7. uppl.</subfield>
  </datafield>
  <datafield tag="260" ind1=" " ind2=" ">
   <subfield code="a">Helsingfors :</subfield>
   <subfield code="b">Schildt,</subfield>
   <subfield code="c">1998 ;</subfield>
   <subfield code="e">(Falun :</subfield>
   <subfield code="f">Scandbook)</subfield>
  </datafield>
  <datafield tag="300" ind1=" " ind2=" ">
   <subfield code="a">166, [4] s. :</subfield>
   <subfield code="b">ill. ;</subfield>
   <subfield code="c">21 cm</subfield>
  </datafield>
  <datafield tag="440" ind1=" " ind2="0">
   <subfield code="a">Mumin-biblioteket,</subfield>
   <subfield code="x">99-0698931-9</subfield>
  </datafield>
  <datafield tag="500" ind1=" " ind2=" ">
   <subfield code="a">Originaluppl. 1962</subfield>
  </datafield>
  <datafield tag="599" ind1=" " ind2=" ">
   <subfield code="a">Li: S</subfield>
  </datafield>
  <datafield tag="740" ind1="4" ind2=" ">
   <subfield code="a">Det osynliga barnet</subfield>
  </datafield>
  <datafield tag="775" ind1="1" ind2=" ">
   <subfield code="z">951-50-0385-7</subfield>
   <subfield code="w">9515003857</subfield>
   <subfield code="9">07</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">Li</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">SEE</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">L</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">NB</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">Q</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="841" ind1=" " ind2=" ">
   <subfield code="5">S</subfield>
   <subfield code="a">xa</subfield>
   <subfield code="b">0201080u    0   4000uu   |000000</subfield>
   <subfield code="e">1</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">NB</subfield>
   <subfield code="b">NB</subfield>
   <subfield code="c">NB98:12</subfield>
   <subfield code="h">plikt</subfield>
   <subfield code="j">R, 980520</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">Li</subfield>
   <subfield code="b">Li</subfield>
   <subfield code="c">CNB</subfield>
   <subfield code="h">h,u</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">SEE</subfield>
   <subfield code="b">SEE</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">Q</subfield>
   <subfield code="b">Q</subfield>
   <subfield code="j">98947</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">L</subfield>
   <subfield code="b">L</subfield>
   <subfield code="c">0100</subfield>
   <subfield code="h">98/</subfield>
   <subfield code="j">3043 H</subfield>
  </datafield>
  <datafield tag="852" ind1=" " ind2=" ">
   <subfield code="5">S</subfield>
   <subfield code="b">S</subfield>
   <subfield code="h">Sv97</subfield>
   <subfield code="j">7235</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Yanson, Tobe,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Janssonová, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Jansone, Tuve,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Janson, Tuve,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Jansson, Tuve,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="900" ind1="1" ind2="s">
   <subfield code="a">Janssonova, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
   <subfield code="u">Jansson, Tove,</subfield>
   <subfield code="d">1914-2001</subfield>
  </datafield>
  <datafield tag="976" ind1=" " ind2="2">
   <subfield code="a">Hcd,u</subfield>
   <subfield code="b">Skönlitteratur</subfield>
  </datafield>
  <controlfield tag="005">20050204111518.0</controlfield>
 </record>
</collection>
