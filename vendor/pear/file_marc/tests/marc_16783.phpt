--TEST--
marc_16783: iterate and pretty print a non-compliant MARC record (tag = '30-')
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
$marc_file = new File_MARC($dir . '/' . 'bad_example.mrc');

while ($marc_record = $marc_file->next()) {
  print $marc_record;
  print "\n";
}
?>
--EXPECT--
LDR 01853    a2200517   4500
001     0000000044
003     EMILDA
008     980120s1998    fi     j      000 0 swe
020    _a9515008808
       _cFIM 72:00
035    _99515008808
040    _aNB
042    _9NB
       _9SEE
084    _aHcd,u
       _2kssb/6
084    _5NB
       _auHc
       _2kssb
084    _5SEE
       _aHcf
       _2kssb/6
084    _5Q
       _aHcd,uf
       _2kssb/6
100 1  _aJansson, Tove,
       _d1914-2001
245 04 _aDet osynliga barnet och andra bert̃telser /
       _cTove Jansson
250    _a7. uppl.
260    _aHelsingfors :
       _bSchildt,
       _c1998 ;
       _e(Falun :
       _fScandbook)
440  0 _aMumin-biblioteket,
       _x99-0698931-9
500    _aOriginaluppl. 1962
599    _aLi: S
740 4  _aDet osynliga barnet
775 1  _z951-50-0385-7
       _w9515003857
       _907
841    _5Li
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
841    _5SEE
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
841    _5L
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
841    _5NB
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
841    _5Q
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
841    _5S
       _axa
       _b0201080u    0   4000uu   |000000
       _e1
852    _5NB
       _bNB
       _cNB98:12
       _hplikt
       _jR, 980520
852    _5Li
       _bLi
       _cCNB
       _hh,u
852    _5SEE
       _bSEE
852    _5Q
       _bQ
       _j98947
852    _5L
       _bL
       _c0100
       _h98/
       _j3043 H
852    _5S
       _bS
       _hSv97
       _j7235
900 1s _aYanson, Tobe,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
900 1s _aJanssonov,̀ Tove,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
900 1s _aJansone, Tuve,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
900 1s _aJanson, Tuve,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
900 1s _aJansson, Tuve,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
900 1s _aJanssonova, Tove,
       _d1914-2001
       _uJansson, Tove,
       _d1914-2001
976  2 _aHcd,u
       _bSkn̲litteratur
005     20050204111518.0
