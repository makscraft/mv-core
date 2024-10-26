
<script type="text/javascript" src="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/rights-table.js"></script>

<tr>
   <td class="field-name"><?php echo I18n::locale("users-rights"); ?></td>
   <td class="field-content">
      <table cellpadding="0" cellspacing="0" id="rights-table">
	      <tr>
		      <th class="modules-rights"><?php echo I18n::locale('modules')." / ".I18n::locale('operations'); ?></th>
		      <th><?php echo I18n::locale('create'); ?></th>
		      <th><?php echo I18n::locale('read'); ?></th>
		      <th><?php echo I18n::locale('edit'); ?></th>
		      <th><?php echo I18n::locale('delete'); ?></th>
	      </tr>
	      <?php
	      	  echo $system -> model -> displayUsersRights(); 
	      ?>
      </table>      
   </td>
</tr>
<tr>
   <td class="field-name" colspan="2">
      <?php $checked = (isset($_POST["send_admin_info_email"]) && $_POST["send_admin_info_email"]) ? " checked=\"checked\"" : ""; ;?>
      <input type="checkbox"<?php echo $checked; ?> id="send-admin-info-email" name="send_admin_info_email" value="1" />
      <label for="send-admin-info-email"><?php echo I18n::locale("send-user-info"); ?></label>
   </td>
</tr>