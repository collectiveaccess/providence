<?php
$dir = dirname(__FILE__);
require 'File/MARC.php';

// Define the usable fields for our CCL query
$ccl_fields = array(
    "ti" => "1=4",
    "au"  => "1=1",
    "isbn" => "1=7"
);

// Declare the array that will hold the parsed results
$ccl_results = array();

// Connect to the laurentian.ca Z39.50 server
$conn = yaz_connect('142.51.8.7:2200/UNICORN');
yaz_ccl_conf($conn, $ccl_fields);

// Define our query for a most excellent text
$ccl_query = "ti='derby' and au='scott'";

// Parse the CCL query into yaz's native format
$result = yaz_ccl_parse($conn, $ccl_query, $ccl_results);
if (!$result) {
    echo "Error: " . $ccl_results['errorstring'];
    exit();
}

// Submit the query
$rpn = $ccl_results['rpn'];
yaz_search($conn, 'rpn', $rpn);
yaz_wait();

// Any errors trying to retrieve this record?
$error = yaz_error($conn);
if ($error) {
    print "Error: $error\n";
    exit();
}

// Retrieve the first MARC record as raw MARC
$rec = yaz_record($conn, 1, "raw");
if (!$rec) {
    print "Error: Retrieved no results.\n";
    exit();
}

// Parse the retrieved MARC record
$marc_file = new File_MARC($rec, File_MARC::SOURCE_STRING); 

while ($marc_record = $marc_file->next()) {
    print $marc_record;
    print "\n";
}
?>
