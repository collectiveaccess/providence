<!DOCTYPE html>
<html>
	<head>
		<?= caGetPrintablesCSSTags($this, ['url' => true]); ?>
		
		<style>
<?php
		require(__CA_THEME_DIR__."/printables/page/chrome/fonts.php"); 
?>
			@media print {
				@page {
					size: {{{pageWidth}}} {{{pageHeight}}};
					margin: {{{marginTop}}} {{{marginRight}}} {{{marginBottom}}} {{{marginLeft}}}; 
	<?php
		if($this->getVar('param_showTimestampInFooter')) {
	?>					
					@bottom-left {
						font-family: "DejaVuSans";
						font-size: 12px;
						content: "<?= caGetLocalizedDate(null, ['dateFormat' => 'delimited']); ?>";
					}
	<?php
		}
		if($this->getVar('param_includePageNumbers')) {
	?>				
					@bottom-right {
						font-family: "DejaVuSans";
						font-size: 12px;
						content: counter(page);
					}
	<?php
		}
?>
				}
				
				<?= $fonts; ?>
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
