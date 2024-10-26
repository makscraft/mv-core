<?php
include_once "../../config/autoload.php";

$system = new System();
$system -> detectModel();

if(!$system -> model -> checkDisplayParam("create_actions") || 
   !$system -> user -> checkModelRights($system -> model -> getModelClass(), "create"))
{
	$system -> displayInternalError("error-no-rights");
}

if(isset($_SESSION['mv']['settings']['pager-limit']))
{
	$system -> model -> pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);
	$system -> model -> processUrlRarams();
}

if($system -> model -> getParentId())
	$system -> model -> setValue($system -> model -> getParentField(), $system -> model -> getParentId());

$url_params = $back_url_params = $system -> model -> getAllUrlParams(array('parent','model','filter','pager'));
$current_tab = $system -> model -> checkCurrentTab();

if($current_tab)
	$url_params .= "&current-tab=".$current_tab;

if(isset($_GET['action']) && $_GET['action'] == 'create' && !empty($_POST))
{
	$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "create");
	$form_errors = $system -> model -> getDataFromPost() -> validate();
	
	if(!isset($_POST["admin-panel-csrf-token"]) || $_POST["admin-panel-csrf-token"] != $system -> getToken())
	{
		$system -> model -> addError(I18n::locale("error-wrong-token"));
		$form_errors = true;
	}
	
	if(!$form_errors)
	{
		$system -> db -> beginTransaction();
		$new_id = $system -> model -> create();
		$system -> db -> commitTransaction();
		
		$url_to_go = "model/";
		
		if(!isset($_GET['edit']))
			$url_params = str_replace("&current-tab=".$current_tab, "", $url_params);
		
		if(isset($_GET['continue']))
			$url_to_go .= "create.php?".$url_params;
		else if(isset($_GET['edit']))
			$url_to_go .= "update.php?".$url_params."&id=".$new_id;
		else
			$url_to_go .= "?".$url_params;
			
		$_SESSION["message"]["created"] = true;
		$_SESSION["message"]["done"] = "create";

		$system -> reload($url_to_go);
	}
}

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
	<div id="model-form">
       <div class="column-inner">
	       <h3 class="column-header with-navigation">
                <?php 
                	echo $system -> model -> getName();
                	echo "<span class=\"header-info\">".I18n::locale("create-record")."</span>\n";
                ?>
				<span id="header-navigation">
                	<input class="button-light" type="button" id="top-save-button" value="<?php echo I18n::locale('save'); ?>" />
                	<input class="button-dark button-back" type="button" onclick="location.href='<?php echo $registry -> getSetting('AdminPanelPath').'model/?'.$back_url_params; ?>'" value="<?php echo I18n::locale('cancel'); ?>" />
				</span>
           </h3>
           <?php      
	           if(isset($form_errors) && $form_errors)
    	          echo $system -> model -> displayFormErrors();
    	       else if(isset($_SESSION["message"]["created"]))
    	       {
			      echo "<div class=\"form-no-errors\"><p>".I18n::locale('done-create')."</p></div>\n";
			      unset($_SESSION["message"]);
    	       }
			      
			   if($file_name = $system -> model -> checkIncludeCode("create-top.php"))
			   		include $file_name;
			   		
			   if($file_name = $system -> model -> checkIncludeCode("action-top.php"))
			   		include $file_name;
           ?>
		   <form method="post" id="<?php echo $system -> model -> getModelClass(); ?>" enctype="multipart/form-data" action="?<?php echo $url_params; ?>&action=create" class="model-elements-form">
              <?php
              	  $form_html = $system -> model -> displayModelFormInAdminPanel('create', $current_tab);
			  	  
              	  if(is_array($form_html))
              	  	  echo $form_html[1];
              ?>
		      <table>
		         <?php
			   		 echo is_array($form_html) ? $form_html[0] : $form_html;
			   		 
			   		 if($file_name = $system -> model -> checkIncludeCode("create-form.php"))
			  	     	 include $file_name;
			  	     
			   		 if($file_name = $system -> model -> checkIncludeCode("action-form.php"))
			  	     	 include $file_name;
			  	 ?>
		         <tr class="model-form-navigation">
			         <td colspan="2" class="bottom-navigation">
			            <input class="button-light" type="button" id="submit-button" value="<?php echo I18n::locale('save'); ?>" />
	                    <input class="button-light" type="button" id="continue-button" value="<?php echo I18n::locale('create-and-continue'); ?>" />
	                    <input class="button-light" type="button" id="create-edit-button" value="<?php echo I18n::locale('create-and-edit'); ?>" />
                        <input class="button-dark" id="model-cancel" type="button" rel="<?php echo $registry -> getSetting('AdminPanelPath')."model/?".$back_url_params; ?>" value="<?php echo I18n::locale('cancel'); ?>" />
                        <input type="hidden" name="admin-panel-csrf-token" value="<?php echo $system -> getToken(); ?>" />
			         </td>
		         </tr>
		      </table>
		   </form>
           <?php
		       if($file_name = $system -> model -> checkIncludeCode("create-bottom.php"))
			       include $file_name;
			   
		       if($file_name = $system -> model -> checkIncludeCode("action-bottom.php"))
			       include $file_name;
		   ?>
       </div>
	</div>
	<div id="model-versions">
	   <div class="column-inner">
	      <h3><?php echo I18n::locale('versions-history'); ?></h3>
	      <p><?php echo $system -> model -> getVersionsLimit() ? I18n::locale('versions-history-new') : I18n::locale("versions-disabled"); ?></p>
	   </div>
	</div>
    <div class="clear"></div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>