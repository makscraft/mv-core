<?php
$db = Database::instance();
$model = new Users();
$model -> loadRelatedData() -> setUser($admin_panel -> user);
$model -> setId($admin_panel -> user -> getId()) -> read();
$model -> setDisplayParam('hidden_fields', ['active']);
$model -> setDisplayParam('not_editable_fields', ['date_registered', 'date_last_visit']);

$region = Session::get('settings')['region'] ?? Registry::get('Region');
$regions_values = I18n::getRegionsOptions();
$model -> addElement(['{language}', 'enum', 'region', ['values_list' => $regions_values]]);
$model -> setValue('region', $region);

$migrations_path = $registry -> getSetting('AdminPanelPath').'?view=migrations';

if(Http::isPostRequest() && Http::fromGet('action') === 'update')
{
    $form_errors = $model -> getDataFromPost() -> validate();

    if($admin_panel -> createCSRFToken() !== Http::fromPost('adminpanel_csrf_token'))
    {
        $model -> addError(I18n::locale('error-wrong-token'));
		$form_errors = true;
    }
    
    if(!$form_errors)
	{
		if($model -> getValue('region'))
		{
            $admin_panel -> updateUserSessionSetting('region', $model -> getValue('region'));			
			I18n::saveRegion($model -> getValue('region'));
		}

        $model -> removeElement('region');
		
		$db -> beginTransaction();
		$model -> update('self-update');
		$db -> commitTransaction();
		
		FlashMessages::add('success', I18n::locale('done-update'));
		
		if($skin = Http::fromPost('admin-panel-skin'))
		    $system -> user -> setUserSkin($skin);
		
        Http::reload('view=user-settings');
    }
}

include Registry::get('IncludeAdminPath').'includes/header.php';
?>
<script type="text/javascript">
$(document).ready(function()
{
	$("tr:has(select[name='admin-panel-skin'])").insertAfter("tr:has(input[name='password_repeat'])");
	$("tr:has(select[name='region'])").insertAfter("tr:has(input[name='password_repeat'])");
	$("select[name='region'] option[value='']").remove();
});   
</script>
<div id="columns-wrapper">
    <div id="model-form" class="one-column">
         <h3 class="column-header with-navigation"><?php echo I18n::locale('my-settings'); ?></h3>
         <?php
          	if(isset($form_errors) && $form_errors)
				echo $model -> displayFormErrors();
			else if(FlashMessages::hasAny())
				echo FlashMessages::displayAndClear();
         ?>
	     <form method="post" id="users" action="?view=user-settings&action=update" class="model-elements-form">
	          <table>
		          <?php echo $model -> displayModelFormInAdminPanel(); ?>
                  <tr>
                     <td class="field-name"><?php echo I18n::locale("admin-panel-skin"); ?></td>
                     <td class="field-content">                        
                        <?php echo $admin_panel -> user -> displayUserSkinSelect(); ?>
                     </td>
                  </tr>
                  <tr>
	                  <td colspan="2" class="bottom-navigation">
				         <?php if($admin_panel -> user -> getId() == 1): ?>
				            <input class="button-light" type="button" onclick="location.href='<?php echo $migrations_path; ?>'" value="Migrations" />
				         <?php endif; ?>
                         <input class="button-light" type="button" id="submit-button" value="<?php echo I18n::locale('save'); ?>" />
                         <input class="button-dark" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath'); ?>'" type="button" value="<?php echo I18n::locale('cancel'); ?>" />
                         <input type="hidden" name="adminpanel_csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
	                  </td>
                  </tr>
	         </table>
         </form>
    </div>
 </div>
<?php include Registry::get('IncludeAdminPath').'includes/footer.php'; ?>