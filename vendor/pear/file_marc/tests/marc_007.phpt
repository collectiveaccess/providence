--TEST--
marc_007: Use key=>value iteration for tags and codes
--SKIPIF--
<?php include('skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';
$marc_file = new File_MARC($dir . '/' . 'example.mrc');

if ($marc_record = $marc_file->next()) {
    foreach ($marc_record->getFields() as $tag=>$value) {
        print "$tag: ";
	    if ($value instanceof File_MARC_Control_Field) {
                print $value->getData();
            }
	    else {
                foreach ($value->getSubfields() as $code=>$subdata) {
                    print "_$code";
                }
            }
        print "\n";
    }
}
?>
--EXPECT--
001: 0000000044
003: EMILDA
008: 980120s1998    fi     j      000 0 swe
020: _a_c
035: _9
040: _a
042: _9_9
084: _a_2
084: _5_a_2
084: _5_a_2
084: _5_a_2
100: _a_d
245: _a_c
250: _a
260: _a_b_c_e_f
300: _a_b_c
440: _a_x
500: _a
599: _a
740: _a
775: _z_w_9
841: _5_a_b_e
841: _5_a_b_e
841: _5_a_b_e
841: _5_a_b_e
841: _5_a_b_e
841: _5_a_b_e
852: _5_b_c_h_j
852: _5_b_c_h
852: _5_b
852: _5_b_j
852: _5_b_c_h_j
852: _5_b_h_j
900: _a_d_u_d
900: _a_d_u_d
900: _a_d_u_d
900: _a_d_u_d
900: _a_d_u_d
900: _a_d_u_d
976: _a_b
005: 20050204111518.0
