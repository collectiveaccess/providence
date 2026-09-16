<!DOCTYPE html>
<html>
	<head>
		<?= caGetPrintablesCSSTags($this); ?>
		
		<style>
			@page {
				size: {{{pageWidth}}} {{{pageHeight}}};
				margin: {{{marginTop}}} {{{marginRight}}} {{{marginBottom}}} {{{marginLeft}}}; 
<?php
	if($this->getVar('param_showTimestampInFooter')) {
?>					
				@bottom-left {
					font-family: "DejaVuSans";
					font-size: 12px;
					content: "<?= date('Y-m-d @ h:m'); ?>";
				}
<?php
	}
	if($this->getVar('param_includePageNumbers')) {
?>				
				@bottom-right {
					font-family: "DejaVuSans";
					font-size: 12px;
					content: counter(page) "/" counter(pages);
				}
<?php
	}
?>
			}
		</style>
	</head>
	<body>
		<header>
			<?= caRenderPrintableFilePath($this, 'templates', 'header.php'); ?>
		</header>
		<footer>
			<?= caRenderPrintableFilePath($this, 'templates', 'footer.php'); ?>
		</footer>
