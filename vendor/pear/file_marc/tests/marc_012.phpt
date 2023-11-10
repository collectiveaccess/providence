--TEST--
marc_012: test isControlField() and isDataField() convenience methods
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_file = new File_MARC($dir . '/' . 'music.mrc');

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
004	Control field!
005	Control field!
008	Control field!
010	Data field!
035	Data field!
035	Data field!
040	Data field!
050	Data field!
245	Data field!
260	Data field!
300	Data field!
500	Data field!
505	Data field!
650	Data field!
650	Data field!
700	Data field!
700	Data field!
700	Data field!
740	Data field!
852	Data field!
001	Control field!
005	Control field!
007	Control field!
008	Control field!
024	Data field!
028	Data field!
035	Data field!
040	Data field!
100	Data field!
245	Data field!
260	Data field!
300	Data field!
511	Data field!
500	Data field!
518	Data field!
500	Data field!
500	Data field!
505	Data field!
650	Data field!
700	Data field!
710	Data field!
740	Data field!
001	Control field!
005	Control field!
007	Control field!
008	Control field!
024	Data field!
028	Data field!
033	Data field!
033	Data field!
033	Data field!
033	Data field!
035	Data field!
040	Data field!
048	Data field!
110	Data field!
245	Data field!
260	Data field!
300	Data field!
440	Data field!
511	Data field!
518	Data field!
500	Data field!
500	Data field!
505	Data field!
650	Data field!
700	Data field!
700	Data field!
700	Data field!
700	Data field!
700	Data field!
852	Data field!
