<?php
$class_name = $model -> getModelClass();
$link_create = str_replace('action=index', 'action=create', $url_params);

if($admin_panel -> user -> checkModelRights($class_name, "create"))
	$action = "location.href='".Registry::get('AdminPanelPath').'?'.$link_create."'";
else
	$action = "$.modalWindow.open(MVobject.locale('no_rights'), {css_class: 'alert'});";
?>

<input class="button-create" type="button" onclick="<?php echo $action; ?>" value="<?php echo I18n::locale('create'); ?>" />
