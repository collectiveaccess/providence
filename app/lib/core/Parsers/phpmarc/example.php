<?php
require_once("MARC.php");

if(!@$_REQUEST['spec'])
	$_REQUEST['spec'] = '245$a';
?>
<html>
<head><title>Show MARC values</title></head>
<body>
<h1>Show Marc Values</h1>
<p>Upload a file with MARC records and values matching the given
specification will be shown.</p>
<form action="example.php" method="POST" enctype="multipart/form-data">
Spec: <input type="text" name="spec" 
    value="<?php echo htmlspecialchars($_REQUEST['spec']) ?>" /><br />
Type: <input type="radio" name="type" value="marc" /> MARC
    <input type="radio" name="type" value="mnem" /> Mnemonic<br />
File: <input type="file" name="data" /><br />
<input type="submit" />
</form>

<?php
if(count($_FILES) == 0){
	echo '</body></html>';
	exit();
}

$f = @fopen($_FILES['data']['tmp_name'], 'rb');
if(!$f)
	die("Can't open uploaded file");
	
if(@$_POST['type'] == 'marc')
	handle_marc($f);
else
	handle_mnem($f);
echo '</body></html>';
exit();

function handle_marc($f)
{
	$p = new MarcParser();
	while($buf = fread($f, 8192)){
		$err = $p->parse($buf);
		if(is_a($err, 'MarcParseError'))
			die("Bad MARC record, giving up: ".$err->toStr());
		print_recs($p->records);
		$p->records = array();
	}
	$p->eof();
	print_recs($p->records);
}
function handle_mnem($f)
{
	$p = new MarcMnemParser();
	while($buf = fread($f, 8192)){
		$err = $p->parse($buf);
		if(is_a($err, 'MarcParseError'))
			die("Bad MARC record, giving up: ".$err->toStr());
		print_recs($p->records);
		$p->records = array();
	}
	$p->eof();
	print_recs($p->records);
}
function print_recs($recs)
{
	foreach($recs as $rec)
		foreach($rec->getValues($_REQUEST['spec']) as $val)
			echo htmlspecialchars($val).'<br />';
}
