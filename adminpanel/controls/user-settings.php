<?php
include_once '../../config/autoload.php';
$system = new System();
$admin_panel = new AdminPanel();

$system -> runModel('users');
$system -> model -> setId($system -> user -> getId()) -> read();
$system -> model -> setDisplayParam('hidden_fields', array('active'));
$system -> model -> setDisplayParam('not_editable_fields', array('date_registered', 'date_last_visit'));

$regions_values = I18n::getRegionsOptions();
$system -> model -> addElement(array('{language}', 'enum', 'region', array('values_list' => $regions_values)));
$system -> model -> setValue('region', $_SESSION['mv']['settings']['region']);

$migrations_path = $registry -> getSetting('AdminPanelPath').'controls/migrate.php';

if(isset($_GET['action']) && $_GET['action'] == 'update' && Http::isPostRequest())
{
	$form_errors = $system -> model -> getDataFromPost() -> validate();
	
	if(!isset($_POST['admin-panel-csrf-token']) || $_POST['admin-panel-csrf-token'] != $system -> getToken())
	{
		$system -> model -> addError(I18n::locale('error-wrong-token'));
		$form_errors = true;
	}
		
	if(!$form_errors)
	{
		if($system -> model -> getValue('region'))
		{
			$system -> user -> updateSetting('region', $system -> model -> getValue('region'));
			$_SESSION['mv']['settings']['region'] = $system -> model -> getValue('region');
			
			I18n::saveRegion($system -> model -> getValue('region'));
		}
			
		$system -> model -> removeElement('region');
		
		$system -> db -> beginTransaction();
		$system -> model -> update('self-update');
		$system -> db -> commitTransaction();
		
		$admin_panel -> addFlashMessage('success', I18n::locale('done-update'));
		
		if(isset($_POST['admin-panel-skin']) && $_POST['admin-panel-skin'])
			$system -> user -> setUserSkin($_POST['admin-panel-skin']);
		
		$system -> reload('controls/user-settings.php');
	}
}

include $registry -> getSetting('IncludeAdminPath').'includes/header.php';
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
		        echo $system -> model -> displayFormErrors();
			else
				echo $admin_panel -> displayAndClearFlashMessages();
         ?>
	     <form method="post" id="<?php echo $system -> model -> getModelClass(); ?>" enctype="multipart/form-data" action="?action=update" class="model-elements-form">
	          <table>
		          <?php echo $system -> model -> displayModelFormInAdminPanel(); ?>
                  <tr>
                     <td class="field-name"><?php echo I18n::locale("admin-panel-skin"); ?></td>
                     <td class="field-content">                        
                        <?php echo $system -> user -> displayUserSkinSelect(); ?>
                     </td>
                  </tr>
                  <tr>
	                  <td colspan="2" class="bottom-navigation">
				         <?php if($system -> user -> getId() == 1): ?>
				            <input class="button-light" type="button" onclick="location.href='<?php echo $migrations_path; ?>'" value="Migrations" />
				         <?php endif; ?>
                         <input class="button-light" type="button" id="submit-button" value="<?php echo I18n::locale('save'); ?>" />
                         <input class="button-dark" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath'); ?>'" type="button" value="<?php echo I18n::locale('cancel'); ?>" />
                         <input type="hidden" name="admin-panel-csrf-token" value="<?php echo $system -> getToken(); ?>" />
	                  </td>
                  </tr>                  
	         </table>
         </form>
    </div>         
 </div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>