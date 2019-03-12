<?php
	$data = $this->getVar('data');
	$totals = is_array($data['logins']) ? $data['logins'] : [];

?>
	<h3><?php print _t('Logins'); ?></h3>
	
	<?php if ($totals['most_recent']) { ?>
	<div><?php print _t("Most recent login: %1", date("c", $totals['most_recent'])); ?></div>
	<?php } ?>
<?php
	if(is_array($totals['counts'])) { 
		if(is_array($totals['counts']['by_class'])) { 
?>
		<div><?php print _t("User accounts:"); ?></div>
		<ul>
	<?php
			foreach($totals['counts']['by_class'] as $class => $total) {
				print "<li>{$class}: {$total}</li>\n";
			}
	?>
		</ul>
<?php
		}
		
		if(is_array($totals['counts']['by_interval'])) { 
?>
		<div><?php print _t("User logins:"); ?></div>
		<ul>
	<?php
			foreach($totals['counts']['by_interval'] as $interval => $total) {
				print "<li>{$interval}: {$total}</li>\n";
			}
	?>
		</ul>
<?php
		}
		
	}
