<?php
$model = new (Http::fromGet('model'));

if(!$model -> checkDisplayParam('create_actions') || 
   !$admin_panel -> user -> checkModelRights($model -> getModelClass(), 'create'))
{
	$admin_panel -> displayInternalError('error-no-rights');
}

$model -> loadRelatedData();
$model -> setUser($admin_panel -> user);

if($model -> getParentId())
	$model -> setValue($model -> getParentField(), $model -> getParentId());

$url_params = $back_url_params = $model -> getAllUrlParams(['parent','model','filter','pager']);
$back_url_params .= '&action=index';

if($current_tab = $model -> checkCurrentTab())
	$url_params .= '&current-tab='.$current_tab;

if(Http::isPostRequest() && 'create' === Http::fromGet('action'))
{
	$admin_panel -> user -> extraCheckModelRights($model -> getModelClass(), 'create');

	$form_errors = $model -> getDataFromPost() -> validate();
	
    if($admin_panel -> createCSRFToken() !== Http::fromPost('adminpanel_csrf_token'))
    {
        $model -> addError(I18n::locale('error-wrong-token'));
		$form_errors = true;
    }

	if(!$form_errors)
	{
		if(Http::fromGet('edit') !== null)
			$url_params = str_replace('&current-tab='.$current_tab, '', $url_params);

		$new_id = $model -> create();
		$redirect = Registry::get('AdminPanelPath').'?'.$url_params;
				
		if(Http::fromGet('continue') !== null)
			$redirect .= '&action=create';
		else if(Http::fromGet('edit') !== null)
			$redirect .= '&action=update&id='.$new_id;
		else
			$redirect .= '&action=index';
		
		FlashMessages::add('success', I18n::locale('done-create'));
		Http::redirect($redirect);
	}
}

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
	<div id="model-form">
        <div class="column-inner">
	        <h3 class="column-header with-navigation">
                <?php 
                	echo $model -> getName();
                	echo "<span class=\"header-info\">".I18n::locale("create-record")."</span>\n";
                ?>
				<span id="header-navigation">
                	<input class="button-light" type="button" id="top-save-button" value="<?php echo I18n::locale('save'); ?>" />
                	<input class="button-dark button-back" type="button" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath').'?'.$back_url_params; ?>'" value="<?php echo I18n::locale('cancel'); ?>" />
				</span>
            </h3>
            <?php      
	           if(isset($form_errors) && $form_errors)
    	        	echo $model -> displayFormErrors();
				else if(FlashMessages::hasAny())
				  	echo FlashMessages::displayAndClear();
			    
			    if($file_name = $model -> checkIncludeCode("create-top.php"))
			   		include $file_name;
			   		
			    if($file_name = $model -> checkIncludeCode("action-top.php"))
			   		include $file_name;
            ?>
		   <form method="post" id="<?php echo $model -> getModelClass(); ?>" enctype="multipart/form-data" action="?<?php echo $url_params; ?>&action=create" class="model-elements-form">
              <?php
              	  $form_html = $model -> displayModelFormInAdminPanel('create', $current_tab);
			  	  
              	  if(is_array($form_html))
              	  	  echo $form_html[1];
              ?>
		      <table>
		         <?php
			   		 echo is_array($form_html) ? $form_html[0] : $form_html;
			   		 
			   		 if($file_name = $model -> checkIncludeCode("create-form.php"))
			  	     	 include $file_name;
			  	     
			   		 if($file_name = $model -> checkIncludeCode("action-form.php"))
			  	     	 include $file_name;
			  	 ?>
		         <tr class="model-form-navigation">
			         <td colspan="2" class="bottom-navigation">
			            <input class="button-light" type="button" id="submit-button" value="<?php echo I18n::locale('save'); ?>" />
	                    <input class="button-light" type="button" id="continue-button" value="<?php echo I18n::locale('create-and-continue'); ?>" />
	                    <input class="button-light" type="button" id="create-edit-button" value="<?php echo I18n::locale('create-and-edit'); ?>" />
                        <input class="button-dark" id="model-cancel" type="button" rel="<?php echo $registry -> getSetting('AdminPanelPath')."?".$back_url_params; ?>" value="<?php echo I18n::locale('cancel'); ?>" />
                        <input type="hidden" name="adminpanel_csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
			         </td>
		         </tr>
		      </table>
		   </form>
           <?php
		       if($file_name = $model -> checkIncludeCode("create-bottom.php"))
			       include $file_name;
			   
		       if($file_name = $model -> checkIncludeCode("action-bottom.php"))
			       include $file_name;
		   ?>
       </div>
	</div>
	<div id="model-versions">
	   <div class="column-inner">
	      <h3><?php echo I18n::locale('versions-history'); ?></h3>
	      <p><?php echo $model -> getVersionsLimit() ? I18n::locale('versions-history-new') : I18n::locale("versions-disabled"); ?></p>
	   </div>
	</div>
    <div class="clear"></div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>