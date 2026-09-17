@media print {
<?php
$fonts = [
	'DejaVuSans.ttf' => [
		'font-family' => "DejaVuSans",
		'font-style' => 'normal',
		'font-weight' => 'normal',
		'path' => __CA_APP_DIR__.'/fonts/DejaVuSans.ttf',
		'format' => 'truetype'
	],
	'DejaVuSans-Bold.ttf' => [
		'font-family' => "DejaVuSans",
		'font-style' => 'normal',
		'font-weight' => 'bold',
		'path' => __CA_APP_DIR__.'/fonts/DejaVuSans-Bold.ttf',
		'format' => 'truetype'
	],
	'DejaVuSans-Oblique.ttf' => [
		'font-family' => "DejaVuSans",
		'font-style' => 'italic',
		'font-weight' => 'normal',
		'path' => __CA_APP_DIR__.'/fonts/DejaVuSans-Oblique.ttf',
		'format' => 'truetype'
	],
];

foreach($fonts as $n => $f) {
?>
	@font-face {
		font-family: "<?= $f['font-family']; ?>";
		font-style: <?= $f['font-style']; ?>;
		font-weight: <?= $f['font-weight']; ?>;
		src: url(data:font/truetype;charset=utf-8;base64,<?= base64_encode(file_get_contents($f['path'])); ?>) format('<?= $f['format']; ?>');
	}

<?php
}
?>
}
