<?php
$class_name = $system -> model -> getModelClass();

if($system -> user -> checkModelRights($class_name, "create"))
	$action = "location.href='".Registry::get('AdminPanelPath')."?action=create&".$url_params."'";
else
	$action = "$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});";
?>

<input class="button-create" type="button" onclick="<?php echo $action; ?>" value="<?php echo I18n::locale('create'); ?>" />
