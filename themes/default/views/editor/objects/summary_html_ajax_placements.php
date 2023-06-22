<?php
    $va_placements = $this->getVar('placements');
?>

<?php if (isset($va_placements)) : ?>
    <?php foreach ($va_placements as $vn_placement_id => $va_info) : ?>
        <div class="_content" placementid="<?php print $vn_placement_id; ?>"></div>
    <?php endforeach; ?>
<?php endif; ?>
