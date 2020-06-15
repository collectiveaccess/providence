<?php
/* ----------------------------------------------------------------------
 * app/plugins/UserRecommendations/widget_recommendations_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 */
 
    $vn_association_recommendation_count = $this->getVar('association_recommendation_count');
    $vn_creation_recommendation_count = $this->getVar('creation_recommendation_count');
	$vn_moderated_recommendation_count = $this->getVar('moderated_recommendation_count');
	$vn_unmoderated_recommendation_count = $this->getVar('unmoderated_recommendation_count');
	$vn_total_recommendation_count = $this->getVar('total_recommendation_count');
?>
	<h3 class='comments'><?php print _t('User recommendations'); ?>:
    <div><?php
			if ($vn_association_recommendation_count == 1) {
				print _t("1 association recommendation");
            }
            else {
				print _t("%1 association recommendations", $vn_association_recommendation_count);
			}
	?></div>
	<div><?php
			if ($vn_creation_recommendation_count == 1) {
				print _t("1 creation recommendation");
			} else {
				print _t("%1 creation recommendations", $vn_creation_recommendation_count);
			}
	?></div>
    <div><?php
			if ($vn_unmoderated_recommendation_count == 1) {
				print _t("1 recommendation needs moderation");
            }
            else {
				print _t("%1 recommendations need moderation", $vn_unmoderated_recommendation_count);
			}
	?></div>
	<div><?php
			if ($vn_moderated_recommendation_count == 1) {
				print _t("1 moderated recommendation");
			} else {
				print _t("%1 moderated recommendations", $vn_moderated_recommendation_count);
			}
	?></div>
	<div><?php
			if ($vn_total_recommendation_count == 1) {
				print _t("1 recommendation total");
			} else {
				print _t("%1 recommendations total", $vn_total_recommendation_count);
			}
	?></div>
	</h3>