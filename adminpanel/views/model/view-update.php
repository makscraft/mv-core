<?php
$model = new (Http::fromGet('model'));

if(!$model -> checkDisplayParam('create_actions') || 
   !$admin_panel -> user -> checkModelRights($model -> getModelClass(), 'read'))
{
	$admin_panel -> displayInternalError('error-no-rights');
}

$model -> loadRelatedData() -> setUser($admin_panel -> user);

if($model -> getParentId())
	$model -> setValue($model -> getParentField(), $model -> getParentId());

$url_params = $back_url_params = $model -> getAllUrlParams(['parent','model','filter','pager']);
$current_tab = $model -> checkCurrentTab();

if(!$current_id = Http::fromGet('id'))
    $admin_panel -> displayInternalError('error-params-needed');

if($model -> checkRecordById($current_id))
    $model -> setId($current_id);
else
    $admin_panel -> displayInternalError('error-wrong-record');

$url_params = $model -> getAllUrlParams(['parent','model','filter','pager','id']);
$back_url_params = $model -> getAllUrlParams(['parent','model','filter','pager']);
$back_url_params .= '&action=index';

if($current_tab = $model -> checkCurrentTab())
    $url_params .= '&current-tab='.$current_tab;

$admin_panel -> runVersions($model);
$admin_panel -> versions -> setUrlParams($url_params.'&action=update');

if(Http::isPostRequest() && 'update' === Http::fromGet('action'))
{
    $admin_panel -> user -> extraCheckModelRights($model -> getModelClass(), 'update');
    $editable_fields = $model -> getEditableFields(); 
    $form_errors = $model -> getDataFromPost() -> validate($editable_fields);

    if($admin_panel -> createCSRFToken() !== Http::fromPost('adminpanel_csrf_token'))
    {
        $model -> addError(I18n::locale('error-wrong-token'));
		$form_errors = true;
    }
    
    if(!$form_errors)
    {
        $model -> update();
		
		if(Http::fromGet('continue') !== null)
			$url_params .= '&action=update';
        else
		{
            $url_params = str_replace('&current-tab='.$current_tab, '', $url_params);
			$url_params = preg_replace('/.id=\d+/', '',$url_params).'&action=index';
		}

		$redirect = Registry::get('AdminPanelPath').'?'.$url_params;

		FlashMessages::add('success', I18n::locale('done-update'));
		Http::redirect($redirect);
    }
}
else if(Http::isGetRequest() && $version = Http::fromGet('version'))
{
    if($admin_panel -> versions -> checkVersion($version))
    {
        $admin_panel -> versions -> setVersion($version);
        $admin_panel -> passVersionContent($model);
    }
    else
        $admin_panel -> displayInternalError('error-wrong-record');
}
else
    $model -> read();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
	<div id="model-form">
	      <div class="column-inner">
	         <h3 class="column-header with-navigation">
                <?php
                	echo $model -> getName();
                	echo "<span class=\"header-info\">".I18n::locale("update-record")."</span>";
                	
                	if($version = $admin_panel -> versions -> getVersion())
                		echo "<span class=\"header-info\">".I18n::locale("version-loaded").$version."</span>\n";
                ?>
				<span id="header-navigation">
					<?php if($model -> getEditableFields() !== false): ?>
						<input class="button-light" type="button" id="top-save-button" value="<?php echo I18n::locale('save'); ?>" />
					<?php endif; ?>
					<input class="button-dark button-back" type="button" onclick="location.href='<?php echo Registry::get('AdminPanelPath').'?'.$back_url_params; ?>'" value="<?php echo I18n::locale('cancel'); ?>" />             
				</span>				
             </h3>
	   		 <?php      
			    if(isset($form_errors) && $form_errors)
			        echo $model -> displayFormErrors();
                else if(FlashMessages::hasAny())
				    echo FlashMessages::displayAndClear();
			      
		      	if($file_name = $model -> checkIncludeCode("update-top.php"))
			        include $file_name;
			      
		      	if($file_name = $model -> checkIncludeCode("action-top.php"))
			        include $file_name;
			 ?>
		    <form method="post" id="<?php echo $model -> getModelClass(); ?>" enctype="multipart/form-data" action="?<?php echo $url_params; ?>&action=update" class="model-elements-form">
                <?php
              	    $form_html = $model -> displayModelFormInAdminPanel($current_tab);
	              
	                if(is_array($form_html))
	                    echo $form_html[1];
                ?>          
		      <table>
		         <?php
		         	echo is_array($form_html) ? $form_html[0] : $form_html;
		      	    
		            if($file_name = $model -> checkIncludeCode("update-form.php"))
			     	    include $file_name;
			     	
		            if($file_name = $model -> checkIncludeCode("action-form.php"))
			     	    include $file_name;
			     ?>
		         <tr class="model-form-navigation">
			         <td colspan="2" class="bottom-navigation">
                        <?php 
                        	if($admin_panel -> user -> checkModelRights($model -> getModelClass(), "update"))
                        	{
                        		$submit_button = "type=\"button\" id=\"submit-button\"";
                        		$continue_button = "id=\"continue-button\"";
                        	}
                        	else
                        	{
                        		$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(MVobject.locale('no_rights'), {css_class: 'alert'});\"";
                        		$continue_button = "onclick=\"$.modalWindow.open(MVobject.locale('no_rights'), {css_class: 'alert'});\"";
                        	}
                        ?>
                        <?php if($model -> getEditableFields() !== false): ?>
                            <input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n::locale('save'); ?>" />
                            <input class="button-light" type="button" <?php echo $continue_button; ?> value="<?php echo I18n::locale('update-and-continue'); ?>" />                        
                            <input class="button-dark" id="model-cancel" type="button" rel="<?php echo $registry -> getSetting('AdminPanelPath')."?".$back_url_params; ?>" value="<?php echo I18n::locale('cancel'); ?>" />
                        <?php endif; ?>
                        <input type="hidden" name="adminpanel_csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
			         </td>
		         </tr>
		      </table>
		    </form>
            <?php 
                if($file_name = $model -> checkIncludeCode("update-bottom.php"))
                    include $file_name;
                
                if($file_name = $model -> checkIncludeCode("action-bottom.php"))
                    include $file_name;
		    ?>
	   </div>
	</div>
	<div id="model-versions">
	   <div class="column-inner">
	      <h3><?php echo I18n::locale('versions-history'); ?></h3>
          <?php include $registry -> getSetting('IncludeAdminPath')."includes/versions.php"; ?>
	   </div>
	</div>
   <div class="clear"></div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>