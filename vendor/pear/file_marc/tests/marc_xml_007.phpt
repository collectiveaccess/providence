--TEST--
marc_xml_007: test getTag(), isControlField(), and isDataField() convenience methods on MARCXML
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARCXML.php';
$marc_file = new File_MARCXML($dir . '/' . 'bigarchive.xml');

while ($marc_record = $marc_file->next()) {
  $fields = $marc_record->getFields();
  foreach ($fields as $field) {
    print $field->getTag();
    if ($field->isControlField()) {
      print "\tControl field!";
    }
    if ($field->isDataField()) {
      print "\tData field!";
    }
    print "\n";
  }
}

?>
--EXPECT--
001	Control field!
003	Control field!
005	Control field!
006	Control field!
007	Control field!
008	Control field!
037	Data field!
040	Data field!
245	Data field!
246	Data field!
260	Data field!
300	Data field!
500	Data field!
500	Data field!
500	Data field!
510	Data field!
510	Data field!
533	Data field!
651	Data field!
830	Data field!
856	Data field!
909	Data field!
