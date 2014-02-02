<div class='close'><a href='#'><img src='<?php print $this->request->getThemeURLPath(); ?>/graphics/buttons/x.png' border='0'/></a></div>
<div class='content'>
	<h2><?php print _t('How to use the image viewer'); ?></h2>

	<p class='tileviewerHelpText'>
	<?php print _t('Click and drag with the mouse to pan across the image. You can use the mousewheel or trackpad to zoom in or out of the image. 
	The <code>+</code> and <code>-</code> buttons and zoom slider bar at the top of viewer may also be used. The viewer displays the image at full resolution, loading 
	detail as you zoom. The more you zoom the more detail will be visible.
	'); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('You may add text annotations to the image. Each annotation is associated with a point, rectangular or polygon area on the image. To create an annotation
	select the point (line icon), rectangle or polygon tool from the tool bar, then click on the location you wish to annotate. '); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('For point annotations a line will appear connecting the location with a text box. The location will also be highlighted with a red translucent circle. You may drag both the point of the line and the text box to adjust position.'); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('For rectangular annotations a rectangle will appear with a text box below and adjacent. Drag on the edges at the highlighted locations to resize the rectangle. Drag the rectangle on its interior to reposition it.'); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('For polygon annotations click 
	repeatedly on the image to establish line segments at various locations for the polygon shape. The shape may have any number of points, and consequently sides. To complete the shape double-click
	when placing the final point or click on the pan, rectangle or point tools. You can adjust the shape by clicking and dragging on any point. To move the shape, click and drag on the outline,
	away from any point. To remove a point click on it while holding down the option key. To add a point click on the outline, away from any point, while holding down the option key.
	'); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('You may also drag the text box into position. Click on the text box to add text. All changes to annotations are saved automatically as you work.'); ?>
	</p>

	<h3><?php print _t('Keyboard shortcuts'); ?></h3>

	<p class='tileviewerHelpText'>
	<?php print _t('Single-key shortcuts are available for common actions. Pressing the key is equivalent to the corresponding mouse action.'); ?>

	<ul class='tileviewerHelpList'>
		<li><?php print _t('%1 to activate image panning', '<code>space</code>'); ?></li>
		<li><?php print _t('%1 to select the rectangle annotation tool', '<code>r</code>'); ?></li>
		<li><?php print _t('%1 to select the point annotation tool', '<code>p</code>'); ?></li>
		<li><?php print _t('%1 to delete the selected annotation', '<code>d</code>'); ?></li>
		<li><?php print _t('%1 to toggle visibility of the image overview', '<code>n</code>'); ?></li>
		<li><?php print _t('%1 to return the image to the centered, zoomed out "home" position', '<code>h</code>'); ?></li>
		<li><?php print _t('%1 to toggle visibility of viewer controls', '<code>c</code>'); ?></li>
		<li><?php print _t('%1 to hide controls and labels', '<code>TAB</code>'); ?></li>
		<li><?php print _t('%1 or %2 to zoom in in small increments', '<code>+</code>', '<code>]</code>'); ?></li>
		<li><?php print _t('%1 or %2 to zoom out in small increments', '<code>-</code>', '<code>[</code>'); ?></li>
		<li><?php print _t('%1, %2, %3 or %4 to pan the image in small increments', '<code>←</code>', '<code>↑</code>', '<code>→</code>', '<code>↓</code>'); ?></li>
	</ul>
</div>