<?php
require_once '../../../../../../setup.php';

require_once __CA_LIB_DIR__."/core/Search/Common/Parsers/LuceneSyntaxParser.php";
require_once __CA_LIB_DIR__."/core/Zend/Search/Lucene.php";
require_once __CA_LIB_DIR__."/core/Zend/Search/Lucene/Search/QueryParser.php";

$vo_old_parser = new Zend_Search_Lucene_Search_QueryParser();
$vo_parser = new LuceneSyntaxParser();

$va_searches = array(
	'accession:X91298',
	'idno:SDK.2008.43',
	'length:24\"',
	'location:41.442N,-74.433W',
	'content:(+deer -bear)',
	'Kai likes Lego',
	'length:{4m to 8m}',
	// some examples from lucene.apache.org
	'title:"The Right Way" AND text:go',
	'title:Do it right',
	'test*',
	't?st',
	'roam~0.8',
	'"jakarta apache"~10',
	'jakarta^4 apache',
	'"jakarta apache" NOT "Apache Lucene"',
	'"jakarta apache" -"Apache Lucene"',
	'title:(+return +"pink panther")',
	'media/strawberry_flag/159_Strawberry Gazette #5_2010.08.29/Strawberry Gazette 5.pdf',
	'\(1\+1\)\:2'
);

foreach($va_searches as $vs_search){
	$vo_query = $vo_parser->parse($vs_search);
	$vo_old_query = $vo_old_parser->parse($vs_search);

	print ("SEARCH TEXT: {$vs_search}\n");
	print("NEW QUERY PARSE TREE TO STRING: {$vo_query->__toString()}\n");
	print("OLD QUERY PARSE TREE TO STRING: {$vo_old_query->__toString()}\n");
	print("# ---------------------------------\n");
}
