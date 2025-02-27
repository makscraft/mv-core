<?php
Http::isAjaxRequest('post', true);

if(isset($_POST['model'], $_POST['model_display_fields']) && Registry::checkModel($_POST['model']))
{
	$model = new $_POST['model']();
	$passed_fields = explode(',', $_POST['model_display_fields']);
	$checked_fields = [];
	
	foreach($passed_fields as $name)
		if($model -> getElement($name) || $name == 'id')
			$checked_fields[] = $name;

	
	if(count($checked_fields))
	{
		$settings = $admin_panel -> updateModelSessionSetting(
			$model -> getModelClass(),
			'display-fields',
			implode(',', $checked_fields));

		$admin_panel -> user -> saveSettings($settings); 
	}
}
else if($skin = Http::fromPost('set-user-skin'))
	Http::responseText($admin_panel -> user -> setUserSkin($skin));