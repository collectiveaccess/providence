--TEST--
marc_005: Ensure a duplicated record is a deep copy; test deleteFields()
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_file = new File_MARC($dir . '/' . 'example.mrc');

$marc_record = $marc_file->next();
$copy_record = $marc_record;
$duplicate_record = clone $marc_record;

$num_deleted1 = $marc_record->deleteFields('020');
print "Deleted $num_deleted1 fields from the original record.\n";

$num_deleted2 = $copy_record->deleteFields('8\\d\\d', true);
print "Deleted $num_deleted2 fields from the shallow copy record.\n";

$num_deleted3 = $duplicate_record->deleteFields('9\\d\\d', true);
print "Deleted $num_deleted3 fields from the duplicate record.\n";

print "Original:\n";
print $marc_record;

print "\nCopy:\n";
print $copy_record;

print "\nDuplicate:\n";
print $duplicate_record;
print "\n";

?>
--EXPECT--
Deleted 1 fields from the original record.
Deleted 12 fields from the shallow copy record.
Deleted 7 fields from the duplicate record.
Original:
LDR 01850     2200517   4500
001     0000000044
003     EMILDA
008     980120s1998    fi     j      000 0 swe
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
245 04 _aDet osynliga barnet och andra berättelser /
       _cTove Jansson
250    _a7. uppl.
260    _aHelsingfors :
       _bSchildt,
       _c1998 ;
       _e(Falun :
       _fScandbook)
300    _a166, [4] s. :
       _bill. ;
       _c21 cm
440  0 _aMumin-biblioteket,
       _x99-0698931-9
500    _aOriginaluppl. 1962
599    _aLi: S
740 4  _aDet osynliga barnet
775 1  _z951-50-0385-7
       _w9515003857
       _907
005     20050204111518.0

Copy:
LDR 01850     2200517   4500
001     0000000044
003     EMILDA
008     980120s1998    fi     j      000 0 swe
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
245 04 _aDet osynliga barnet och andra berättelser /
       _cTove Jansson
250    _a7. uppl.
260    _aHelsingfors :
       _bSchildt,
       _c1998 ;
       _e(Falun :
       _fScandbook)
300    _a166, [4] s. :
       _bill. ;
       _c21 cm
440  0 _aMumin-biblioteket,
       _x99-0698931-9
500    _aOriginaluppl. 1962
599    _aLi: S
740 4  _aDet osynliga barnet
775 1  _z951-50-0385-7
       _w9515003857
       _907
005     20050204111518.0

Duplicate:
LDR 01850     2200517   4500
001     0000000044
003     EMILDA
008     980120s1998    fi     j      000 0 swe
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
245 04 _aDet osynliga barnet och andra berättelser /
       _cTove Jansson
250    _a7. uppl.
260    _aHelsingfors :
       _bSchildt,
       _c1998 ;
       _e(Falun :
       _fScandbook)
300    _a166, [4] s. :
       _bill. ;
       _c21 cm
440  0 _aMumin-biblioteket,
       _x99-0698931-9
500    _aOriginaluppl. 1962
599    _aLi: S
740 4  _aDet osynliga barnet
775 1  _z951-50-0385-7
       _w9515003857
       _907
005     20050204111518.0
