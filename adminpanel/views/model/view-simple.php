<?php
$model = new (Http::fromGet('model'));
$admin_panel -> user -> extraCheckModelRights($model -> getModelClass(), 'read');

$model -> setUser($admin_panel -> user);
$model -> setId(-1) -> getDataFromDb();
$current_tab = $model -> checkCurrentTab();

$admin_panel -> runVersions($model);
$url_params = 'model='.$model -> getModelClass().'&action=simple';

if($current_tab)
	$url_params .= '&current-tab='.$current_tab;
			
$admin_panel -> versions -> setUrlParams($url_params);

if(Http::isPostRequest() && 'simple' === Http::fromGet('update'))
{
	$form_errors = $model -> getDataFromPost() -> validate();
	
    if($admin_panel -> createCSRFToken() !== Http::fromPost('adminpanel_csrf_token'))
    {
        $model -> addError(I18n::locale('error-wrong-token'));
		$form_errors = true;
    }
	
	if(!$form_errors)
	{
		$model -> update('backend');
		$redirect = Registry::get('AdminPanelPath').'?model='.$model -> getModelClass().'&action=simple';	

        if($current_tab)
            $redirect .= '&current-tab='.$current_tab;

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
	$model -> getDataFromDb() -> passDataFromDb();

include $registry -> getSetting("IncludeAdminPath")."includes/header.php";
?>
<div id="columns-wrapper">
   <div id="model-form">
      <div class="column-inner">
         <h3 class="column-header with-navigation">
            <?php
            	echo $model -> getName();
            	echo "<span class=\"header-info\">".I18n::locale("simple-module")."</span>";
            	
              	if($version = $admin_panel -> versions -> getVersion())
              		echo "<span class=\"header-info\">".I18n::locale("version-loaded").$version."</span>\n";
            ?>
			<span id="header-navigation">
				<input class="button-light" type="button" id="top-save-button" value="<?php echo I18n::locale('update'); ?>" />
				<input class="button-dark button-back" type="button" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath'); ?>'" value="<?php echo I18n::locale('cancel'); ?>" />
			</span>			            
         </h3>       
		 <?php      
			if(isset($form_errors) && $form_errors)
                echo $model -> displayFormErrors();
            else if(FlashMessages::hasAny())
                echo FlashMessages::displayAndClear();
		          
			if($file_name = $model -> checkIncludeCode("index-top.php"))
			    include $file_name;
			  	  
		 	$form_action = "?model=".$model -> getModelClass()."&action=simple&update=simple";

		 	if($current_tab)
		 	    $form_action .= "&current-tab=".$current_tab;
		 ?>
	     <form class="model-elements-form" method="post" id="<?php echo $model -> getModelClass(); ?>" enctype="multipart/form-data" action="<?php echo $form_action; ?>">
            <?php 
         	    $form_html = $model -> displayModelFormInAdminPanel($current_tab);
			  	  
                if(is_array($form_html))
               	    echo $form_html[1];
            ?>
	      	<table>
	            <?php
	                echo is_array($form_html) ? $form_html[0] : $form_html;
		   	  	  
	                if($file_name = $model -> checkIncludeCode("index-form.php"))
			   	       include $file_name;
	         	?>
	        	<tr class="model-form-navigation">
					<td colspan="2" class="bottom-navigation">
						<?php
							if($admin_panel -> user -> checkModelRights($model -> getModelClass(), "update"))
								$submit_button = "type=\"button\" id=\"submit-button\"";
							else
								$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(MVobject.locale('no_rights'), {css_class: 'alert'});\"";
						?>                 
						<input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n::locale('update'); ?>" />
						<input class="button-dark" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath'); ?>'" type="button" value="<?php echo I18n::locale('cancel'); ?>" />
						<input type="hidden" name="adminpanel_csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
					</td>
	      		</tr>
		    </table>          
	    </form>
        <?php 
		    if($file_name = $model -> checkIncludeCode("index-bottom.php"))
		  	    include $file_name;
        ?>
      </div>
   </div>
   <div id="model-versions">
      <div class="column-inner">
         <h3><?php echo I18n::locale('versions-history'); ?></h3>
	     <?php include $registry -> getSetting("IncludeAdminPath")."includes/versions.php"; ?>
      </div>
   </div>
</div>
<?php
include $registry -> getSetting("IncludeAdminPath")."includes/footer.php";
?>