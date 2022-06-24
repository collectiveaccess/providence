--TEST--
marc_020: Test MARC binary output
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';

// Get ourselves a MARC record
$marc_file = new File_MARC($dir . '/' . 'example.mrc');
$marc_record = $marc_file->next();

// create some subfields
$subfields[] = new File_MARC_Subfield('a', 'nothing');
$subfields[] = new File_MARC_Subfield('z', 'everything');

// create a data field
$data_field = new File_MARC_Data_Field('100', $subfields, '0');

// append the data field
$marc_record->appendField($data_field);

// create a control field
$ctrl_field = new File_MARC_Control_Field('001', '01234567890');

// prepend the control field
$marc_record->prependField($ctrl_field);

// reproduce test case reported by Mark Jordan
$subfields_966_2[] = new File_MARC_Subfield('l', 'web');
$subfields_966_2[] = new File_MARC_Subfield('r', '0');
$subfields_966_2[] = new File_MARC_Subfield('s', 'b');
$subfields_966_2[] = new File_MARC_Subfield('i', '49');
$subfields_966_2[] = new File_MARC_Subfield('c', '1');
$field_966_2 = new File_MARC_Data_Field('966', $subfields_966_2, null, null);
$marc_record->appendField($field_966_2);

// let's see the results
print convert_uuencode($marc_record->toRaw());

?>
--EXPECT--
M,#$Y-#,@("`@(#(R,#`U-3,@("`T-3`P,#`Q,#`Q,C`P,#`P,#`Q,#`Q,3`P
M,#$R,#`S,#`P-S`P,#(S,#`X,#`S.3`P,#,P,#(P,#`R-C`P,#8Y,#,U,#`Q
M-3`P,#DU,#0P,#`P-S`P,3$P,#0R,#`Q,C`P,3$W,#@T,#`Q.#`P,3(Y,#@T
M,#`Q.#`P,30W,#@T,#`R,3`P,38U,#@T,#`R,C`P,3@V,3`P,#`S,#`P,C`X
M,C0U,#`V,C`P,C,X,C4P,#`Q,S`P,S`P,C8P,#`U.#`P,S$S,S`P,#`S,S`P
M,S<Q-#0P,#`S-S`P-#`T-3`P,#`R,S`P-#0Q-3DY,#`Q,#`P-#8T-S0P,#`R
M-#`P-#<T-S<U,#`S-#`P-#DX.#0Q,#`T.#`P-3,R.#0Q,#`T.3`P-3@P.#0Q
M,#`T-S`P-C(Y.#0Q,#`T.#`P-C<V.#0Q,#`T-S`P-S(T.#0Q,#`T-S`P-S<Q
M.#4R,#`S.#`P.#$X.#4R,#`R,3`P.#4V.#4R,#`Q,S`P.#<W.#4R,#`Q-C`P
M.#DP.#4R,#`R.#`P.3`V.#4R,#`R,3`P.3,T.3`P,#`U-C`P.34U.3`P,#`V
M,#`Q,#$Q.3`P,#`U-S`Q,#<Q.3`P,#`U-C`Q,3(X.3`P,#`U-S`Q,3@T.3`P
M,#`V,#`Q,C0Q.3<V,#`R-C`Q,S`Q,#`U,#`Q-S`Q,S(W,3`P,#`R-#`Q,S0T
M.38V,#`R,3`Q,S8X'C`Q,C,T-38W.#DP'C`P,#`P,#`P-#0>14U)3$1!'CDX
M,#$R,',Q.3DX("`@(&9I("`@("!J("`@("`@,#`P(#`@<W=E'B`@'V$Y-3$U
M,#`X.#`X'V-&24T@-S(Z,#`>("`?.3DU,34P,#@X,#@>("`?84Y"'B`@'SE.
M0A\Y4T5%'B`@'V%(8V0L=1\R:W-S8B\V'B`@'S5.0A]A=4AC'S)K<W-B'B`@
M'S53144?84AC9A\R:W-S8B\V'B`@'S51'V%(8V0L=68?,FMS<V(O-AXQ(!]A
M2F%N<W-O;BP@5&]V92P?9#$Y,30M,C`P,1XP-!]A1&5T(&]S>6YL:6=A(&)A
M<FYE="!O8V@@86YD<F$@8F5RY'1T96QS97(@+Q]C5&]V92!*86YS<V]N'B`@
M'V$W+B!U<'!L+AX@(!]A2&5L<VEN9V9O<G,@.A]B4V-H:6QD="P?8S$Y.3@@
M.Q]E*$9A;'5N(#H?9E-C86YD8F]O:RD>("`?83$V-BP@6S1=(',N(#H?8FEL
M;"X@.Q]C,C$@8VT>(#`?84UU;6EN+6)I8FQI;W1E:V5T+!]X.3DM,#8Y.#DS
M,2TY'B`@'V%/<FEG:6YA;'5P<&PN(#$Y-C(>("`?84QI.B!3'C0@'V%$970@
M;W-Y;FQI9V$@8F%R;F5T'C$@'WHY-3$M-3`M,#,X-2TW'W<Y-3$U,#`S.#4W
M'SDP-QX@(!\U3&D?87AA'V(P,C`Q,#@P=2`@("`P("`@-#`P,'5U("`@?#`P
M,#`P,!]E,1X@(!\U4T5%'V%X81]B,#(P,3`X,'4@("`@,"`@(#0P,#!U=2`@
M('PP,#`P,#`?93$>("`?-4P?87AA'V(P,C`Q,#@P=2`@("`P("`@-#`P,'5U
M("`@?#`P,#`P,!]E,1X@(!\U3D(?87AA'V(P,C`Q,#@P=2`@("`P("`@-#`P
M,'5U("`@?#`P,#`P,!]E,1X@(!\U41]A>&$?8C`R,#$P.#!U("`@(#`@("`T
M,#`P=74@("!\,#`P,#`P'V4Q'B`@'S53'V%X81]B,#(P,3`X,'4@("`@,"`@
M(#0P,#!U=2`@('PP,#`P,#`?93$>("`?-4Y"'V).0A]C3D(Y.#HQ,A]H<&QI
M:W0?:E(L(#DX,#4R,!X@(!\U3&D?8DQI'V-#3D(?:&@L=1X@(!\U4T5%'V)3
M144>("`?-5$?8E$?:CDX.30W'B`@'S5,'V),'V,P,3`P'V@Y."\?:C,P-#,@
M2!X@(!\U4Q]B4Q]H4W8Y-Q]J-S(S-1XQ<Q]A66%N<V]N+"!4;V)E+!]D,3DQ
M-"TR,#`Q'W5*86YS<V]N+"!4;W9E+!]D,3DQ-"TR,#`Q'C%S'V%*86YS<V]N
M;W;A+"!4;W9E+!]D,3DQ-"TR,#`Q'W5*86YS<V]N+"!4;W9E+!]D,3DQ-"TR
M,#`Q'C%S'V%*86YS;VYE+"!4=79E+!]D,3DQ-"TR,#`Q'W5*86YS<V]N+"!4
M;W9E+!]D,3DQ-"TR,#`Q'C%S'V%*86YS;VXL(%1U=F4L'V0Q.3$T+3(P,#$?
M=4IA;G-S;VXL(%1O=F4L'V0Q.3$T+3(P,#$>,7,?84IA;G-S;VXL(%1U=F4L
M'V0Q.3$T+3(P,#$?=4IA;G-S;VXL(%1O=F4L'V0Q.3$T+3(P,#$>,7,?84IA
M;G-S;VYO=F$L(%1O=F4L'V0Q.3$T+3(P,#$?=4IA;G-S;VXL(%1O=F4L'V0Q
M.3$T+3(P,#$>(#(?84AC9"QU'V)3:_9N;&ET=&5R871U<AXR,#`U,#(P-#$Q
M,34Q."XP'C`@'V%N;W1H:6YG'WIE=F5R>71H:6YG'B`@'VQW96(?<C`?<V(?
(:30Y'V,Q'AT`
`
