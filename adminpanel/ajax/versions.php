<?php
include "../../config/autoload.php";

Http::isAjaxRequest('post', true);
$system = new System('ajax');
$admin_panel = new AdminPanel($system -> user);

if(isset($_POST['model'], $_POST['id']) && $system -> registry -> checkModel($_POST['model']))
{			
	$model = new $_POST['model']();
	$model -> setId($_POST['id']);
	
	$url_params = $model -> getAllUrlParams(array('parent','model','filter','pager','id'));
	$current_tab = $model -> checkCurrentTab();

	if($current_tab)
		$url_params .= "&current-tab=".$current_tab;
		
	$admin_panel -> runVersions($model);
	$admin_panel -> versions -> setUrlParams($url_params.'&action=update');
	
	$id_check = ($_POST['id'] == -1) ? true : $model -> checkRecordById($model -> getId());
	
	if($id_check && $version = Http::fromPost('version'))
		if($admin_panel -> versions -> checkVersion($version))
			$admin_panel -> versions -> setVersion($version);

	if($limit = Http::fromPost('versions-pager-limit'))
		$admin_panel -> updateUserSessionSetting('versions-pager-limit', intval($limit));

	header('Content-Type: text/html');
	include $system -> registry -> getSetting('IncludeAdminPath')."includes/versions.php";
}