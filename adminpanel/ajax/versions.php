<?php
Http::isAjaxRequest('post', true);
$model = Http::fromPost('model');
$id = Http::fromPost('id');

if($model && $id && Registry::checkModel($_POST['model']))
{			
	$model = new $model();
	$model -> setId($id);
	$is_simple_model = get_parent_class($model) === 'ModelSimple';
	
	if($is_simple_model)
		$url_params = 'model='.$model -> getModelClass().'&action=simple';
	else
		$url_params = $model -> getAllUrlParams(['parent','model','filter','pager','id']).'&action=update';
	
	if($current_tab = $model -> checkCurrentTab())
		$url_params .= '&current-tab='.$current_tab;
		
	$admin_panel -> runVersions($model);
	$admin_panel -> versions -> setUrlParams($url_params);
	
	$id_check = ($_POST['id'] == -1) ? true : $model -> checkRecordById($model -> getId());
	
	if($id_check && $version = Http::fromPost('version'))
		if($admin_panel -> versions -> checkVersion($version))
			$admin_panel -> versions -> setVersion($version);

	if($limit = Http::fromPost('versions-pager-limit'))
		$admin_panel -> updateUserSessionSetting('versions-pager-limit', intval($limit));

	header('Content-Type: text/html');
	include Registry::get('IncludeAdminPath')."includes/versions.php";
	exit();
}