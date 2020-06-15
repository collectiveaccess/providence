<?php
    $t_model = $this->getVar('t_model');
    //var_dump($t_model);
    $o_dm = Datamodel::load();
    $va_comments_list = $this->getVar('comments_list');
    if(sizeof($va_comments_list) > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caItemList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
<?php 
				print caFormControlBox(
					'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>', 
					'', 
					''
				); 
?>
    <!-- <div id="commentsResults"> -->
       <table id="caItemList" class="listtable">
			<thead>
				<tr>
					<th class="list-header-unsorted">
<?php 
						print _t('Author');
?>
					</th>
					<th class="list-header-unsorted">
<?php 
						print _t('Recommendation'); 
?>
					</th>
					<th class="list-header-unsorted">
<?php
						print _t('Type');
?>
					</th>
					<th class="list-header-unsorted">
<?php 
						print _t('Date');
?>
					</th>
					<th class="list-header-unsorted">
<?php
						print _t('Recommended On');
?>
                    </th>
                    <th class="list-header-unsorted">
<?php
                        print _t('Associate With');
?>
                    </th>
					<th class="list-header-unsorted">
<?php
						print _t('Status/Operations');
?>
					</th>
					<th class="list-header-unsorted">
<?php
						print _t('Access for Action');
?>
					</th>
				</tr>
			</thead>
			<tbody>

<?php	
		foreach($t_model->getRecommendationList() as $recommendation) {
?>
				<tr>
					<td>
						<div class="caUserCommentsListName">
<?php
                        print $recommendation['name'] . '<br/>' . $recommendation['email'];
?>
						</div>
					</td>
					<td>
						<div class="caUserCommentsListComment">
<?php
                            print $recommendation['recommendation'];
?>
						</div>
					</td>	
					<td>
<?php
                    	print $t_model->getChoiceListValue('type', $recommendation['type']);
?>
					</td>
					<td>
<?php 
                        print $recommendation['created_on'];
?>
					</td>
					<td>
<?php
                        $vs_commented_on = "";
                        $t_table = $o_dm->getInstanceByTableNum($recommendation['table_num']);
						if ($t_table->load($recommendation['row_id'])) {
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
                        $vs_associate_on = "";
                        $t_assoc_table = $o_dm->getInstanceByTableNum($recommendation['assoc_table_num']);
                        if($t_assoc_table !== null) {
                            if(isset($recommendation['assoc_row_id'])) {
                                if ($t_assoc_table->load($recommendation['assoc_row_id'])) {
							        $vs_associate_on = $t_assoc_table->getLabelForDisplay(false);
							        if ($vs_idno = $t_assoc_table->get('idno')) {
								        $vs_associate_on .= ' ['.$vs_idno.']';
							        }
                                }
                            }
                            else  {
                                $vs_associate_on = $recommendation['new_assoc_name'];
                            }
                        }

                        print $vs_associate_on;
?>
                    </td>
<?php
                    if($recommendation['moderated_on']) {
?>
                    <td>
<?php
                            print _t("Approved");
?>
                    </td>
<?php
                    }
                    else {
?>
                    <form id="recommendationModerateForm_<?php print $recommendation['recommendation_id']; ?>">
					    <td>
                            <input type="hidden" name="type" value="<?php print $t_model->getChoiceListValue('type', $recommendation['type']); ?>">
                            <input type="hidden" name="recommendation_id" value="<?php print $recommendation['recommendation_id']; ?>">
                            <a href='#' onclick='jQuery("#recommendationModerateForm_<?php print $recommendation['recommendation_id']; ?>").attr("action", "<?php print caNavUrl($this->request, 'UserRecommendations', 'Recommendation', 'Approve'); ?>").submit();' class='form-button'>
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
                            <a href='#' onclick='jQuery("#recommendationModerateForm_<?php print $recommendation['recommendation_id']; ?>").attr("action", "<?php print caNavUrl($this->request, 'UserRecommendations', 'Recommendation', 'Delete'); ?>").submit();' class='form-button'>
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
					    </td>
					    <td>
							<select name="recommendation_access">
<?php
							foreach($t_table->getFieldInfo('access')['BOUNDS_CHOICE_LIST'] as $key => $value) {
								print '<option value="' . $value . '"';
								if($recommendation['access'] == $value) {
									print ' selected';
								}
								print '>' . $key . '</option>';
							}
?>
							</select>
                        </td>
                    </form>
<?php
                    }
?>
				</tr>
<?php
			}
?>
			</tbody>
        </table>
     </div> <!--end sectionBox -->
<?php
     }
?>
