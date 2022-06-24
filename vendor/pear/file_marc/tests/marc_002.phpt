--TEST--
marc_002: iterate and pretty print MARC records from a file with multiple records
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_file = new File_MARC($dir . '/' . 'music.mrc');

while ($marc_record = $marc_file->next()) {
  print $marc_record;
  print "\n";
}
?>
--EXPECT--
LDR 01145ncm  2200277 i 4500
001     000073594
004     AAJ5802
005     20030415102100.0
008     801107s1977    nyujza                   
010    _a   77771106 
035    _a(CaOTUIC)15460184
035 9  _aAAJ5802
040    _aLC
050 00 _aM1366
       _b.M62
       _dM1527.2
245 04 _aThe Modern Jazz Quartet :
       _bThe legendary profile. --
260    _aNew York :
       _bM.J.Q. Music,
       _cc1977.
300    _ascore (72 p.) ;
       _c31 cm.
500    _aFor piano, vibraphone, drums, and double bass.
505 0  _aLewis, J. Django.--Lewis, J. Plastic dreams (music from the film Kemek).--Lewis, J. Dancing (music from the film Kemek).--Lewis, J. Blues in A minor.--Lewis, J. Blues in B♭.--Lewis, J. Precious joy.--Jackson, M. The martyr.--Jackson, M. The legendary profile.
650  0 _aJazz.
650  0 _aMotion picture music
       _vExcerpts
       _vScores.
700 12 _aLewis, John,
       _d1920-
       _tSelections.
       _f1977.
700 12 _aJackson, Milt.
       _tMartyrs.
       _f1977.
700 12 _aJackson, Milt.
       _tLegendary profile.
       _f1977.
740 4  _aThe legendary profile.
852 00 _bMUSIC
       _cMAIN
       _kfolio
       _hM1366
       _iM62
       _91
       _4Marvin Duchow Music
       _5

LDR 01293cjm  2200289 a 4500
001     001878039
005     20050110174900.0
007     sd fungnn|||e|
008     940202r19931981nyujzn   i              d
024 1  _a7464573372
028 02 _aJK 57337
       _bRed Baron
035    _a(OCoLC)29737267
040    _aSVP
       _cSVP
       _dLGG
100 1  _aDesmond, Paul,
       _d1924-
245 10 _aPaul Desmond & the Modern Jazz Quartet
       _h[sound recording]
260    _aNew York, N.Y. :
       _bRed Baron :
       _bManufactured by Sony Music Entertainment,
       _cp1993.
300    _a1 sound disc (39 min.) :
       _bdigital ;
       _c4 3/4 in.
511 0  _aPaul Desmond, alto saxophone; Modern Jazz Quartet: John Lewis, piano; Milt Jackson, vibraphone; Percy Heath, bass; Connie Kay, drums.
500    _aAll arrangements by John Lewis.
518    _aRecorded live on December 25, 1971 at Town Hall, NYC.
500    _aOriginally released in 1981 by Finesse as LP FW 27487.
500    _aProgram notes by Irving Townsend, June 1981, on container insert.
505 0  _aGreensleeves -- You go to my head -- Blue dove -- Jesus Christ Superstar -- Here's that rainy day -- East of the sun -- Bags' new groove.
650  0 _aJazz
       _y1971-1980.
700 1  _aLewis, John,
       _d1920-
710 2  _aModern Jazz Quartet.
740 0  _aPaul Desmond and the Modern Jazz Quartet.

LDR 01829cjm  2200385 a 4500
001     001964482
005     20060626132700.0
007     sd fzngnn|m|e|
008     871211p19871957nyujzn                  d
024 1  _a4228332902
028 01 _a833 290-2
       _bVerve
033 0  _a19571027
       _b6299
       _cD56
033 0  _a196112--
       _b3804
       _cN4
033 0  _a19571019
       _b4104
       _cC6
033 0  _a197107--
       _b6299
       _cV7
035    _a(OCoLC)17222092
040    _aCPL
       _cCPL
       _dOCL
       _dLGG
048    _apz01
       _aka01
       _asd01
       _apd01
110 2  _aModern Jazz Quartet.
       _4prf
245 14 _aThe Modern Jazz Quartet plus
       _h[sound recording].
260    _a[New York] :
       _bVerve,
       _cp1987.
300    _a1 sound disc :
       _bdigital ;
       _c4 3/4 in.
440  0 _aCompact jazz
511 0  _aModern Jazz Quartet (principally) ; Milt Jackson, vibraphone (2nd and 8th works) ; Oscar Peterson, piano (2nd and 8th works) ; Ray Brown, bass (2nd and 8th works) ; Ed Thigpen (2nd work), Louis Hayes (8th work), drums.
518    _aRecorded live, Oct. 27, 1957, at the Donaueschingen Jazz Festival (1st, 5th, 7th, and 10th works); Dec. 1961, in New York (2nd work); live, Oct. 19, 1957, at the Opera House, Chicago (3rd, 4th, 6th, and 9th works); July 1971, in Villingen, Germany (8th work).
500    _aCompact disc.
500    _aAnalog recording.
505 0  _aThe golden striker (4:08) -- On Green Dolphin Street (7:28) -- D & E (4:55) -- I'll remember April (4:51) -- Cortège (7:15) -- Now's the time (4:43) -- J.B. blues (5:09) -- Reunion blues (6:35) -- 'Round midnight (3:56) -- Three windows (7:20).
650  0 _aJazz.
700 1  _aJackson, Milt.
       _4prf
700 1  _aPeterson, Oscar,
       _d1925-
       _4prf
700 1  _aBrown, Ray,
       _d1926-2002.
       _4prf
700 1  _aThigpen, Ed.
       _4prf
700 1  _aHayes, Louis,
       _d1937-
       _4prf
852 80 _bMUSIC
       _cAV
       _hCD 1131
       _4Marvin Duchow Music
       _5Audio-Visual
