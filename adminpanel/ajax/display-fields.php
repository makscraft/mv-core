<?php
include "../../config/autoload.php";

Http::isAjaxRequest('post', true);
$system = new System('ajax');

if(isset($_POST['model'], $_POST['model_display_fields']) && $system -> registry -> checkModel($_POST['model']))
{
	$system -> runModel($_POST['model']);
	$passed_fields = explode(',', $_POST['model_display_fields']);
	$checked_fields = array();
	
	foreach($passed_fields as $name)
		if($system -> model -> getElement($name) || $name == 'id')
			$checked_fields[] = $name;

	if(count($checked_fields))
	{
		$_SESSION['mv']['settings'][$system -> model -> getModelClass()]['display-fields'] = implode(',', $checked_fields);
		$system -> user -> saveSettings($_SESSION['mv']['settings']); 
	}
}
else if(isset($_POST['set-user-skin']) && $_POST['set-user-skin'])
	echo $system -> user -> setUserSkin($_POST['set-user-skin']);