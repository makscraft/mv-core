<?php 
include '../../config/autoload.php';

Http::isAjaxRequest('post', true);
$system = new System('ajax');
$json = [];

if(isset($_POST['model'], $_POST['current_multi_images_field']) && !empty($_FILES))
	if($system -> registry -> checkModel($_POST['model']))
	{
		$system -> runModel(strtolower($_POST['model']));
		$json = $system -> model -> uploadMultiImages($_POST['current_multi_images_field']);
	}
	else
		$json = array('error' => I18n::locale('upload-file-error'));

Http::responseJson($json);