--TEST--
marc_xml_namespace: iterate and pretty print a MARC record
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARCXML.php';
$marc_file = new File_MARCXML($dir . '/' . 'namespace.xml',File_MARC::SOURCE_FILE,"http://www.loc.gov/MARC21/slim");
while ($marc_record = $marc_file->next()) {
  print $marc_record->getLeader();
  print "\n";
  $field = $marc_record->getField('050');
  print $field->getIndicator(1);
  print "\n";
  print $field->getIndicator(2);
  print "\n";
  $subfield = $field->getSubfield('a');
  print $subfield->getData();
  print "\n";
}
?>
--EXPECT--
00925njm  22002777a 4500
0
0
Atlantic 1259
01832cmma 2200349 a 4500
0
0
F204.W5
