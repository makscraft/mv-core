<?php
$model = new (Http::fromGet('model'));
$menu_manager = new Menu();
$admin_panel -> user -> extraCheckModelRights($model -> getModelClass(), 'read');
Registry::set('AdminPanelCSRFToken', $admin_panel -> createCSRFToken());

$model -> loadRelatedData() -> runPagerFilterSorter() -> setUser($admin_panel -> user);
$model -> pager -> setLimit($admin_panel -> getPaginationLimit());

$sorting = $admin_panel -> getModelSessionSetting($model -> getModelClass(), 'sorting') ?? ['id', 'desc'];

if($model -> checkIfAdminSortingFieldAllowed($sorting[0]))
    $model -> sorter -> setParams($sorting[0], $sorting[1]);

$url_params = $model -> getAllUrlParams(['model','parent','filter','pager']);
$show_filters_column = $admin_panel -> getModelSessionSetting($model -> getModelClass(), 'show-filters') ?? true;

if($limit = Http::fromGet('pager-limit'))
    if($admin_panel -> savePaginationLimit($limit))
        Http::reload($url_params.'&action=index');

// $model -> pager -> setLimit($_SESSION['mv']['settings']['pager-limit']);
// $model -> processUrlRarams();
// $url_params = $model -> getAllUrlParams(array('model','parent','filter','pager'));
//Debug::pre();

if(Http::isGetRequest() && isset($_GET['sort-field'], $_GET['sort-order']))
{
    $model -> sorter -> setParams(Http::fromGet('sort-field'), Http::fromGet('sort-order'));
    $admin_panel -> updateModelSessionSetting($model -> getModelClass(),
                                              'sorting',
                                              [Http::fromGet('sort-field'), Http::fromGet('sort-order')]);

    Http::reload($url_params.'&action=index');
}






//Columns list in main table
$display_fields = $admin_panel -> getModelSessionSetting($model -> getModelClass(), 'display-fields');
$model -> defineTableFields($display_fields);

//Counts records to show the number near header
$model -> createSqlForTable();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
  <div id="model-table-wrapper"<?php if(!$show_filters_column) echo ' class="hidden-filters"'; ?>>
   <div id="model-table">
         <h3 class="column-header">
             <?php 
               	 echo $model -> getName();
                         	 	
            	 if($model -> filter -> ifAnyFilterApplied())
            	 {
            	 	echo "<span class=\"header-info\">".I18n::locale("filtration-applied")."</span>";
            	 	$number_of_records = $model -> pager -> getTotal();
            	 	echo "<span class=\"header-info\">";
            	 	
            	 	if($number_of_records)
            	 	{
            	 		$number_of_records = I18n::formatIntNumber($number_of_records);
            	 		
            	 		$i18n_arguments = array('number' => $number_of_records, 'records' => '*number',
            	 								'number_found' => $number_of_records, 'found' => '*number_found');
            	 		
						echo I18n::locale('found-records', $i18n_arguments);
            	 	}
            	 	else
            	 		echo I18n::locale('no-records-found');
            	 	
            	 	echo "</span>\n";
            	 }
            	 else
            	 {
            	 	$total_records = $model -> db -> getCount($model -> getTable());
            	 	$i18n_arguments = array('number' => $total_records, 'records' => '*number');
            	 	
            	 	if($total_records)
            	 	{
            	 		$string = "<span class=\"header-info\">".I18n::locale('number-records', $i18n_arguments)."</span>";
            	 		echo str_replace($total_records, I18n::formatIntNumber($total_records), $string);
            	 	}
            	 }

				if($model -> checkDisplayParam('create_actions'))
         			include $registry -> getSetting('IncludeAdminPath')."includes/create.php";
             ?>
         </h3>
         <?php
         	if(isset($_SESSION["message"]["done"]) && in_array($_SESSION["message"]["done"], array('create','update','delete','restore')))
          		echo "<div class=\"form-no-errors\"><p>".I18n::locale('done-'.$_SESSION["message"]["done"])."</p></div>\n";
			else if(isset($_SESSION["message"]['not-deleted']))
         	{
         		if($_SESSION["message"]['not-deleted'] == 'root')
         			$message = 'no-delete-root';
         		else if($_SESSION["message"]['not-deleted'])
         			$message = 'no-delete-model';
         		else
         			$message = 'no-delete-parent';
         		
         		$arguments = [];
         		
         		if($_SESSION["message"]['not-deleted'] && $_SESSION["message"]['not-deleted'] != 'root' && 
         		   $registry -> checkModel($_SESSION["message"]['not-deleted']))
         		{
         			$model_class = trim($_SESSION["message"]['not-deleted']);
         			$object = new $model_class();
         			$arguments['module'] = $object -> getName();
         		}
         		
         		echo "<div class=\"form-errors\"><p>".I18n::locale($message, $arguments)."</p></div>\n";         		
         	}
         	else if(isset($_SESSION["message"]["custom-errors"]) && $_SESSION["message"]["custom-errors"])
			      echo $_SESSION["message"]["custom-errors"];
         	else if(isset($_SESSION["message"]["token-error"]))
         	{
			      echo "<div class=\"form-errors\"><p>".I18n::locale("error-failed")." ";
			      echo I18n::locale("error-wrong-token")."</p></div>\n";
         	}
			      
			unset($_SESSION["message"]);

	        if($model -> getParentField() && $model -> getParentId())
	        {
	            echo "<div class=\"parents-path\">\n";
	            echo $model -> displayParentsPath($model -> getParentId())."</div>\n";
	        }
	         
		   	if($file_name = $model -> checkIncludeCode("index-top.php"))
				include $file_name;
         ?>
         <div id="top-navigation">
	         <?php
	         	$multi_actions_menu = $menu_manager -> displayMultiActionMenu($model, $admin_panel -> user);
	         	$model_class = $model -> getModelClass();
	         	
	         	if($model -> checkDisplayParam('mass_actions') && $model -> checkDisplayParam('update_actions') && 
	         	   $model_class != "users" && $model_class != "garbage")
	         	{
	         		$quick_limit = "quick-limit-".$model -> getPagerLimitForQuickEdit();
	         		$quick_edit_buttons = "<input id=\"".$quick_limit."\" class=\"button-light mass-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n::locale('quick-edit')."\" />\n";
	         		$quick_edit_buttons .= "<input class=\"button-light save-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n::locale('save')."\" />\n";
	         		$quick_edit_buttons .= "<input class=\"button-dark cancel-quick-edit\" type=\"button\" ";
	         		$quick_edit_buttons .= "value=\"".I18n::locale('cancel')."\" />\n";	         		
	         		
	         		echo str_replace("</div>", $quick_edit_buttons."</div>", $multi_actions_menu);
	         	}
	         	else if($model_class == "garbage")
	         	{
	         		$rights_css = $admin_panel -> user -> checkModelRights("garbage", "delete") ? "" : " has-no-rights";
	         		$button_empty = "<input class=\"button-light".$rights_css."\" id=\"empty-recycle-bin\" type=\"button\" ";
	         		$button_empty .= "value=\"".I18n::locale('empty-recylce-bin')."\" />\n";
	         		
	         		echo str_replace("</div>", $button_empty."</div>", $multi_actions_menu);
	         	}
	         	else
	         		echo $multi_actions_menu;
	         ?>
            <div id="fields-list">
               <input class="button-light<?php if($show_filters_column) echo " no-display"; ?>" type="button" id="show-filters" value="<?php echo I18n::locale('filters'); ?>" />
               <input class="button-list" type="button" id="fields-list-button" value="<?php echo I18n::locale('display-fields'); ?>" />
               <div class="list">
                     <div class="m2m-wrapper">
                        <div class="column">
					       <div class="header"><?php echo I18n::locale("not-selected"); ?></div>
		                   <select class="m2m-not-selected" multiple="multiple">
		                           <?php 
		                              $selects_html = $menu_manager -> displayTableFields($model);
		                              echo $selects_html['not-selected'];
		                           ?>
		                   </select>                      
                        </div>					    
					    <div class="m2m-buttons">
						    <span class="m2m-right" title="<?php echo I18n::locale('move-selected'); ?>"></span>
						    <span class="m2m-left" title="<?php echo I18n::locale('move-not-selected'); ?>"></span>						
                        </div>
                        <div class="column">
                           <div class="header"><?php echo I18n::locale("selected"); ?></div>
					       <select class="m2m-selected" multiple="multiple">
                              <?php echo $selects_html['selected']; ?>
					       </select>
                        </div>
                        <div class="m2m-buttons">
                           <span class="m2m-up" title="<?php echo I18n::locale('move-up'); ?>"></span>
                           <span class="m2m-down" title="<?php echo I18n::locale('move-down'); ?>"></span>						
                        </div>
					    <input type="hidden" value="" name="display-table-fields" />
					 </div>
                     <div class="controls">
                        <input class="apply button-light" type="button" value="<?php echo I18n::locale('apply') ?>" />
                        <input class="cancel button-dark" value="<?php echo I18n::locale('cancel') ?>" type="button" />
                     </div>
               </div>
               <?php
               		if($model -> getModelClass() != "log" && $model -> getModelClass() != "garbage")
               			include $registry -> getSetting('IncludeAdminPath')."includes/operations.php";
               ?>
            </div>
         </div>         
         <form id="model-table-form" method="post" action="?<?php echo $model -> getAllUrlParams(array('model','parent','filter','pager')); ?>">
            <?php echo $model -> displaySortableTable(); ?>
            <input type="hidden" name="adminpanel_csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
         </form>
         <?php echo str_replace('class="multi-actions-menu"', 'class="multi-actions-menu" id="bottom-actions-menu"', $multi_actions_menu); ?>
       <div class="pager-limit">
         <span><?php echo I18n::locale('pager-limit'); ?></span>
         <select>
            <?php echo $model -> pager -> displayPagerLimits(AdminPanel::PAGINATION_LIMITS); ?>
         </select>
         <input type="hidden" value="<?php echo $model -> getAllUrlParams(array('model','parent','filter')); ?>" />
       </div>
       <div class="clear"></div>
         <?php
			echo $model -> pager -> displayPagesAdmin();
			
		   	if($file_name = $model -> checkIncludeCode("index-bottom.php"))
			    include $file_name;
         ?>
       </div>
    </div>	
	<div id="model-filters"<?php if(!$show_filters_column) echo ' class="no-display"'; ?>>
	       <h3><?php echo I18n::locale('filters'); ?>
               <span><input id="hide-filters" type="button" value="<?php echo I18n::locale('hide') ?>" /></span>
           </h3>
		   <div id="admin-filters">
		      <?php 
		            $model -> filter -> setAllowedCountFilter($model -> sorter -> getField());
		            $default_filters = $model -> getDisplayParam("default_filters");
		            $show_empty_default_filters = $model -> getDisplayParam("show_empty_default_filters");
		            echo $model -> filter -> displayAdminFilters($default_filters, $show_empty_default_filters);
		            
		            include $registry -> getSetting('IncludeAdminPath')."includes/filters-manager.php";
		      ?>
		      <div class="controls">
		         <input type="hidden" name="initial-form-params" value="<?php echo $model -> getAllUrlParams(array('sorter','model','parent')); ?>" />
                 <input class="button-light" type="button" id="filters-submit" value="<?php echo I18n::locale('apply-filters'); ?>" />
		         <input class="button-dark" type="button" id="filters-reset" value="<?php echo I18n::locale('reset'); ?>" />
		      </div>
		   </div>         
	</div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>