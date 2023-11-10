--TEST--
marc_003: getFields() with various regular expressions
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_file = new File_MARC($dir . '/' . 'music.mrc');

print "Test with a simple string\n";
while ($marc_record = $marc_file->next()) {
  print "\nNext record:\n";
  $fields = $marc_record->getFields('650');
  foreach ($fields as $field) {
    print $field;
    print "\n";
  }
}

print "\nTest with regular expression\n";
$marc_file = new File_MARC($dir . '/' . 'music.mrc');
while ($marc_record = $marc_file->next()) {
  print "\nNext record:\n";
  $fields = $marc_record->getFields('00\d', true);
  foreach ($fields as $field) {
    print $field;
    print "\n";
  }
}

?>
--EXPECT--
Test with a simple string

Next record:
650  0 _aJazz.
650  0 _aMotion picture music
       _vExcerpts
       _vScores.

Next record:
650  0 _aJazz
       _y1971-1980.

Next record:
650  0 _aJazz.

Test with regular expression

Next record:
001     000073594
004     AAJ5802
005     20030415102100.0
008     801107s1977    nyujza                   

Next record:
001     001878039
005     20050110174900.0
007     sd fungnn|||e|
008     940202r19931981nyujzn   i              d

Next record:
001     001964482
005     20060626132700.0
007     sd fzngnn|m|e|
008     871211p19871957nyujzn                  d
