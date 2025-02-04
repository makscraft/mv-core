<?php 
Http::isAjaxRequest('post', true);
$model = Http::fromPost('model');
$field = Http::fromPost('current_multi_images_field');
$json = [];

if($model && $field && !empty($_FILES))
	if(Registry::checkModel($model))
	{
		$model = new $model();
		$json = $model -> uploadMultiImages($_POST['current_multi_images_field']);
	}
	else
		$json = ['error' => I18n::locale('upload-file-error')];

Http::responseJson($json);