--TEST--
marc_009: Parse a record where leader record length != real record length
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
$marc_file = new File_MARC($dir . '/' . 'wronglen.mrc');

while ($marc_record = $marc_file->next()) {
  print $marc_record;
  print "\n";
  print "WARNINGS:\n";
  foreach ($marc_record->getWarnings() as $warning) {
    print "  * $warning\n";
  }
}
?>
--EXPECT--
LDR 00727nam  2200205 a 4500
001     03-0016458
005     19971103184734.0
008     970701s1997    oru          u000 0 eng u
035    _a(Sirsi) a351664
050 00 _aML270.2
       _b.A6 1997
100 1  _aAnthony, James R.
245 00 _aFrench baroque music from Beaujoyeulx to Rameau
250    _aRev. and expanded ed.
260    _aPortland, OR :
       _bAmadeus Press,
       _c1997.
300    _a586 p. :
       _bmusic
650  0 _aMusic
       _<France
       _y16th century
       _xHistory and criticism.
650  0 _aMusic
       _zFrance
       _y17th century
       _xHistory and criticism.
650  0 _aMusic
       _zFrance
       _y18th century
       _xHistory and criticism.
949    _aML 270.2 A6 1997
       _wLC
       _i30007006841505
       _rY
       _tBOOKS
       _lHUNT-CIRC
       _mHUNEXTRALON

WARNINGS:
  * Invalid record length: Leader says "00727" bytes; actual record length is "741"
  * Field for tag "949" does not end with an end of field character
  * Field for tag "596" does not end with an end of field character
  * Invalid indicators "GSTUFF" forced to blanks for tag "596"
