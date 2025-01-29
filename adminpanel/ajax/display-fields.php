<?php
include "../../config/autoload.php";

Http::isAjaxRequest('post', true);
$system = new System('ajax');
$admin_panel = new AdminPanel($system -> user);

if(isset($_POST['model'], $_POST['model_display_fields']) && $system -> registry -> checkModel($_POST['model']))
{
	$system -> runModel($_POST['model']);
	$passed_fields = explode(',', $_POST['model_display_fields']);
	$checked_fields = [];
	
	foreach($passed_fields as $name)
		if($system -> model -> getElement($name) || $name == 'id')
			$checked_fields[] = $name;

	if(count($checked_fields))
	{
		$admin_panel -> updateModelSessionSetting($system -> model -> getModelClass(),
												  'display-fields',
												  implode(',', $checked_fields));
		
		Session::start('adminpanel');
        $settings = Session::get('settings');
        $settings[$system -> model -> getModelClass()]['display-fields'] = implode(',', $checked_fields);
		$system -> user -> saveSettings($settings); 
	}
}
else if($skin = Http::fromPost('set-user-skin'))
	echo $system -> user -> setUserSkin($skin);