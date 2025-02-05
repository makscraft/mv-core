<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
<title><?php echo I18n::locale('mv'); ?></title>
<?php 
$admin_panel_path = Registry::get('AdminPanelPath');
$admin_media_path = Registry::get('AdminFolder').'/interface/';
$cache_drop = CacheMedia::instance()::getDropMark();

CacheMedia::addCssFile([
	$admin_media_path.'css/style.css',
	$admin_media_path.'air-datepicker/air-datepicker.css',
]);

if(Router::isLocalHost())
	echo CacheMedia::getInitialFiles('css');
else
	echo CacheMedia::getCssCache();
?>
<script type="text/javascript" src="<?php echo $admin_panel_path; ?>interface/js/mv.js<?php echo $cache_drop; ?>"></script>
<script type="text/javascript">
MVobject.mainPath = '<?php echo $registry -> getSetting('MainPath'); ?>';
MVobject.adminPanelPath = '<?php echo $admin_panel_path; ?>';
MVobject.currentView = '<? echo isset($admin_panel) ? $admin_panel -> getCurrentView() : '' ?>';
MVobject.urlParams = '<?php if(isset($model)) echo $model -> getAllUrlParams(array('pager','filter','model','parent','id')); ?>';
<?php
if(isset($model))
   echo "MVobject.currentModel = '".$model -> getModelClass()."';\n";

if(isset($model -> sorter))
   echo "MVobject.sortField = '".$model -> sorter -> getField()."';\n";

if(isset($model))
{
	$parent = $model -> findForeignParent();
	$linked_order_fields = $model -> findDependedOrderFilters();	
}

if(isset($parent) && is_array($parent) && isset($model -> filter))
	if(!$model -> filter -> allowChangeOrderLinkedWithEnum($parent['name']))
		echo "MVobject.relatedParentFilter = '".$parent['caption']."';\n";

if(isset($linked_order_fields) && count($linked_order_fields))
	foreach($linked_order_fields as $name => $data)
		if(!$model -> filter -> allowChangeOrderLinkedWithEnum($data[0]))
			echo "MVobject.dependedOrderFields.".$name." = '".$data[1]."';\n";
		
$has_applied_filters = (int) (isset($model -> filter) && $model -> filter -> ifAnyFilterApplied());
echo "MVobject.hasAppliedFilters = ".$has_applied_filters.";\n";      
      
if(isset($model -> filter))
   if($caption = $model -> filter -> ifFilteredByAllParents())
      echo "MVobject.allParentsFilter = '".$caption."';\n";
   else if(isset($model -> pager))
      echo "MVobject.startOrder = ".($model -> pager -> getStart() + 1).";\n";

$region = $registry -> getSetting('Region');
?>
MVobject.region = '<?php echo $region; ?>';
MVobject.dateFormat = '<?php echo I18n::getDateFormat(); ?>';
</script>
<?php

CacheMedia::addJavaScriptFile([
	$admin_media_path.'js/jquery.js',
	$admin_media_path.'js/jquery-ui.js',
	$admin_media_path.'js/form.js',
	$admin_media_path.'js/jquery.overlay.js',
	$admin_media_path.'js/dialogs.js',
	$admin_media_path.'js/jquery.autocomplete.js',
	$admin_media_path.'air-datepicker/air-datepicker.js',
	Registry::get('AdminFolder').'/i18n/'.$region.'/datepicker.'.$region.'.js',
	$admin_media_path.'js/modal.js',
	$admin_media_path.'js/utils.js'
]);

if(Router::isLocalHost())
	echo CacheMedia::getInitialFiles('js');
else
	echo CacheMedia::getJavaScriptCache();
?>
<script type="text/javascript" src="<?php echo $admin_panel_path; ?>?service=locale&region=<?php echo $region; ?>"></script>

<?php
$skin = $admin_panel -> user -> getUserSkin();

if($skin)
{
	if($skin != "none")
	{
		$skin = $admin_panel_path."interface/skins/".$skin."/skin.css".$cache_drop;
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$skin."\" id=\"skin-css\" />\n";
	}
}
else
{
   $skins = $admin_panel -> user -> getAvailableSkins();
   echo "<script type=\"text/javascript\">$(document).ready(function() { openSkinChooseDialog([\"".implode("\",\"", $skins)."\"]); });</script>\n";
}
?>

<link rel="icon" href="<?php echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $admin_panel_path; ?>interface/images/favicon.svg" type="image/x-icon" />
</head>
<body>
<?php include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php"; ?>
<div id="container">
   <div id="header">
	      <div class="inner">
			<a id="logo" href="<?php echo $admin_panel_path; ?>">
				<img src="<?php echo $admin_panel_path; ?>interface/images/logo.svg<?php echo $cache_drop; ?>" alt="MV logo" />
			</a>
			<div id="models-buttons">
				<ul>
					<li>
						<span><?php echo I18n::locale("modules"); ?></span>
						<div id="models-list">
							<?php echo (new Menu) -> displayModelsMenu(); ?>
						</div>
					</li>
				</ul>
			</div>
			<div id="header-search">
				<form action="<?php echo $admin_panel_path; ?>" method="get">
					<div>
						<?php
							$header_search_value = "";
							
							if(isset($search_text) && Http::fromGet('view') == 'search')
								$header_search_value = $search_text;
						?>
						<input class="string" type="text" name="text" placeholder="<?php echo I18n::locale('search-in-all-modules'); ?>" value="<?php echo $header_search_value; ?>" />
						<input type="submit" class="search-button" value="<?php echo I18n::locale('find'); ?>" />
						<input type="hidden" name="view" value="search" />
					</div>
				</form>
			</div>
			<div id="user-settings">
				<ul>
					<li id="user-name"><span class="skin-color"><?php echo $admin_panel -> user -> getField('name'); ?></span></li>
					<li><a href="<?php echo $admin_panel_path; ?>?view=user-settings"><?php echo I18n::locale("my-settings"); ?></a></li>
					<?php $logout_link = $admin_panel_path."login?logout=".Login::getLogoutToken(); ?>
					<li><a href="<?php echo $registry -> getSetting('MainPath') ?>" target="_blank"><?php echo I18n::locale("to-site"); ?></a></li>
					<li><a href="<?php echo $logout_link; ?>"><?php echo I18n::locale("exit"); ?></a></li>
				</ul>
			</div>
      	</div>
   	</div>
   	<?php echo $admin_panel -> displayWarningMessages(); ?>