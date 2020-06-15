<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Results/ca_item_comments_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 */ 
 	$vo_result = $this->getVar('result');
	$vn_items_per_page = $this->getVar('current_items_per_page');
	
    $o_dm = Datamodel::load();
    //$o_dm->addModelToGraph('ca_item_recommendations', 238);

	$i = 0;
	$vn_item_count = 0;
	
	
?>
	<div id="commentsResults">
		<table id="caItemList" class="listtable" border="0" cellpadding="0" cellspacing="1" style="margin-top:10px;">
			<thead>
				<tr>
					<th class="list-header-nosort">
<?php 
						print _t('Author');
?>
					</th>
					<th class="list-header-nosort">
<?php 
						print _t('Comment'); 
?>
					</th>
					<th class="list-header-nosort">
<?php
						print _t('Media');
?>
					</th>
					<th class="list-header-nosort">
<?php
						print _t('Rating');
?>
					</th>
					<th class="list-header-nosort">
<?php 
						print _t('Date');
?>
					</th>
					<th class="list-header-nosort">
<?php
						print _t('Commented On');
?>
					</th>
					<th class="list-header-nosort">
<?php
						print _t('Status');
?>
					</th>
					<th class="list-header-nosort">
<?php
						print _t('Access');
?>
					</th>
				</tr>
			</thead>
			<tbody>

<?php	
		while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
			if (!($t_table = $o_dm->getInstanceByTableNum($vo_result->get('ca_item_recommendations.table_num'), true))) {
				continue;
			}
?>
				<tr>
					<td>
						<div class="caUserCommentsListName">
<?php 
						if($vo_result->get('ca_item_recommendations.user_id')){
							print $vo_result->get('ca_users.fname') . " " . $vo_result->get('ca_users.lname') . "<br/>" . $vo_result->get('ca_users.email');
						}
						else{
							print $vo_result->get('ca_item_recommendations.name') . "<br/>" . $vo_result->get('ca_item_recommendations.user_email');
						}
?>
						</div>
					</td>
					<td>
						<div class="caUserCommentsListComment">
<?php
							print $vo_result->get('ca_item_recommendations.recommendation');
?>
						</div>
					</td>	
					<td>
<?php
					if($vo_result->getMediaTag('ca_item_recommendations.media1', "thumbnail")){
						print "<span style='white-space: nowrap;'>"  . $vo_result->getMediaTag("ca_item_recommendations.media1", "thumbnail");
						print caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t('Download'), '', 'UserRecommendations', 'Recommendation', 'DownloadMedia', array('version' => 'original', 'recommendation_id' => $vo_result->get('ca_item_recommendations.recommendation_id'), 'mode' => 'search', 'download' => 1), array(), array('no_background' => true, 'dont_show_content' => true));
						print "</span>";
					}
?>
					</td>
					<td>
<?php
						print ($vn_tmp = $vo_result->get('ca_item_recommendations.type')) ? $vn_tmp : "-";
?>
					</td>
					<td>
<?php 
						print $vo_result->get('ca_item_recommendations.created_on');
?>
					</td>
					<td>
<?php
						$vs_commented_on = "";
						if ($t_table->load($vo_result->get('ca_item_recommendations.row_id'))) {
							$vs_commented_on = $t_table->getLabelForDisplay(false);
							if ($vs_idno = $t_table->get('idno')) {
								$vs_commented_on .= ' ['.$vs_idno.']';
							}
						}

						print $vs_commented_on;
?>
					</td>
					<td>
<?php
                    if($vo_result->get('ca_item_recommendations.moderated_on')) {
                        print _t("Approved");
                    }
                    else {
?>
                        <form id="commentModerateForm_<?php print $vo_result->get('recommendation_id'); ?>">
                            <input type="hidden" name="mode" value="search">
                            <input type="hidden" name="comment_id" value="<?php print $vo_result->get('recommendation_id'); ?>">
                            <a href='#' onclick='jQuery("#commentModerateForm_<?php print $vo_result->get('recommendation_id'); ?>").attr("action", "<?php print caNavUrl($this->request, 'UserRecommendations', 'Recommendation', 'Approve'); ?>").submit();' class='form-button'>
                                <span class='form-button approveDelete'>
<?php
                                    print caNavIcon(__CA_NAV_ICON_APPROVE__, 1);
?>
                                    <span class='formtext'>
<?php
                                        print _t("Approve"); 
?>
                                    </span>
                                </span>
                            </a>
                            <a href='#' onclick='jQuery("#commentModerateForm_<?php print $vo_result->get('recommendation_id'); ?>").attr("action", "<?php print caNavUrl($this->request, 'UserRecommendations', 'Recommendation', 'Delete'); ?>").submit();' class='form-button'>
                                <span class='form-button approveDelete'>
<?php
                                    print caNavIcon(__CA_NAV_ICON_DELETE__, 1); 
?>
                                    <span class='formtext'>
<?php 
                                        print _t("Delete");
?>
                                     </span>
                                </span>
                            </a>
                        </form>
<?php
                    }
?>
					</td>
					<td>
						<form id="commentAccessForm_<?php print $vo_result->get('comment_id'); ?>">
							<input type="hidden" name="comment_id" value="<?php print $vo_result->get('recommendation_id'); ?>">
							<select name="comment_new_access" onChange='jQuery("#commentAccessForm_<?php print $vo_result->get('recommendation_id'); ?>").attr("action", "<?php print caNavUrl($this->request, 'UserRecommendations', 'Recommendation', 'ChangeAccess'); ?>").submit()'>
<?php
							foreach($t_table->getFieldInfo('access')['BOUNDS_CHOICE_LIST'] as $key => $value) {
								print '<option value="' . $value . '"';
								if($vo_result->get('ca_item_recommendations.access') == $value) {
									print ' selected';
								}
								print '>' . $key . '</value>';
							}
							//print $t_table->getChoiceListValue('access', $vo_result->get('ca_item_comments.access')); 
?>
							</select>
						</form>
					</td>
				</tr>
<?php
				$i++;
				$vn_item_count++;
			}
?>
			</tbody>
		</table>
	</div><!--end commentsResults -->
