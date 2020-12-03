--TEST--
marc_021: test MARC-in-JSON serialization with subfield 0
--SKIPIF--
<?php include('tests/skipif.inc'); ?>
--FILE--
<?php
$dir = dirname(__FILE__);
require __DIR__ . '/bootstrap.php';
$marc_file = new File_MARC($dir . '/' . 'example.mrc');

while ($marc_record = $marc_file->next()) {
  // create some subfields
  $subfields[] = new File_MARC_Subfield('0', 'nothing');
  $subfields[] = new File_MARC_Subfield('1', 'everything');
  $field = new File_MARC_Data_Field('999', $subfields, '', '');
  $marc_record->appendField($field);
  
  $subfields = null;
  $field = null;
  $subfields[] = new File_MARC_Subfield('a', 'bee');
  $subfields[] = new File_MARC_Subfield('0', 'cee');
  $subfields[] = new File_MARC_Subfield('d', 'eee');
  $field = new File_MARC_Data_Field('999', $subfields, '', '');

  $marc_record->appendField($field);

  print $marc_record->toJSON();
  print "\n";
}
?>
--EXPECT--
{"leader":"01850     2200517   4500","fields":[{"001":"0000000044"},{"003":"EMILDA"},{"008":"980120s1998    fi     j      000 0 swe"},{"020":{"ind1":" ","ind2":" ","subfields":[{"a":"9515008808"},{"c":"FIM 72:00"}]}},{"035":{"ind1":" ","ind2":" ","subfields":[{"9":"9515008808"}]}},{"040":{"ind1":" ","ind2":" ","subfields":[{"a":"NB"}]}},{"042":{"ind1":" ","ind2":" ","subfields":[{"9":"NB"},{"9":"SEE"}]}},{"084":{"ind1":" ","ind2":" ","subfields":[{"a":"Hcd,u"},{"2":"kssb\/6"}]}},{"084":{"ind1":" ","ind2":" ","subfields":[{"5":"NB"},{"a":"uHc"},{"2":"kssb"}]}},{"084":{"ind1":" ","ind2":" ","subfields":[{"5":"SEE"},{"a":"Hcf"},{"2":"kssb\/6"}]}},{"084":{"ind1":" ","ind2":" ","subfields":[{"5":"Q"},{"a":"Hcd,uf"},{"2":"kssb\/6"}]}},{"100":{"ind1":"1","ind2":" ","subfields":[{"a":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"245":{"ind1":"0","ind2":"4","subfields":[{"a":"Det osynliga barnet och andra ber\u00e4ttelser \/"},{"c":"Tove Jansson"}]}},{"250":{"ind1":" ","ind2":" ","subfields":[{"a":"7. uppl."}]}},{"260":{"ind1":" ","ind2":" ","subfields":[{"a":"Helsingfors :"},{"b":"Schildt,"},{"c":"1998 ;"},{"e":"(Falun :"},{"f":"Scandbook)"}]}},{"300":{"ind1":" ","ind2":" ","subfields":[{"a":"166, [4] s. :"},{"b":"ill. ;"},{"c":"21 cm"}]}},{"440":{"ind1":" ","ind2":"0","subfields":[{"a":"Mumin-biblioteket,"},{"x":"99-0698931-9"}]}},{"500":{"ind1":" ","ind2":" ","subfields":[{"a":"Originaluppl. 1962"}]}},{"599":{"ind1":" ","ind2":" ","subfields":[{"a":"Li: S"}]}},{"740":{"ind1":"4","ind2":" ","subfields":[{"a":"Det osynliga barnet"}]}},{"775":{"ind1":"1","ind2":" ","subfields":[{"z":"951-50-0385-7"},{"w":"9515003857"},{"9":"07"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"Li"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"SEE"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"L"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"NB"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"Q"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"841":{"ind1":" ","ind2":" ","subfields":[{"5":"S"},{"a":"xa"},{"b":"0201080u    0   4000uu   |000000"},{"e":"1"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"NB"},{"b":"NB"},{"c":"NB98:12"},{"h":"plikt"},{"j":"R, 980520"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"Li"},{"b":"Li"},{"c":"CNB"},{"h":"h,u"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"SEE"},{"b":"SEE"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"Q"},{"b":"Q"},{"j":"98947"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"L"},{"b":"L"},{"c":"0100"},{"h":"98\/"},{"j":"3043 H"}]}},{"852":{"ind1":" ","ind2":" ","subfields":[{"5":"S"},{"b":"S"},{"h":"Sv97"},{"j":"7235"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Yanson, Tobe,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Janssonov\u00e1, Tove,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Jansone, Tuve,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Janson, Tuve,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Jansson, Tuve,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"900":{"ind1":"1","ind2":"s","subfields":[{"a":"Janssonova, Tove,"},{"d":"1914-2001"},{"u":"Jansson, Tove,"},{"d":"1914-2001"}]}},{"976":{"ind1":" ","ind2":"2","subfields":[{"a":"Hcd,u"},{"b":"Sk\u00f6nlitteratur"}]}},{"005":"20050204111518.0"},{"999":{"ind1":" ","ind2":" ","subfields":[{"0":"nothing"},{"1":"everything"}]}},{"999":{"ind1":" ","ind2":" ","subfields":[{"a":"bee"},{"0":"cee"},{"d":"eee"}]}}]}
