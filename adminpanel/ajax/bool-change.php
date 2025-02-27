<?php 
Http::isAjaxRequest('post', true);
$token = Http::fromPost('adminpanel_csrf_token');
$data = Http::fromPost('id');

if($admin_panel -> createCSRFToken() !== $token)
	exit();

$id = intval(preg_replace('/.*-(\d+)-.*/', '$1', $data));
$model = preg_replace('/.*-\d+-(.*)$/', '$1', $data);
$field = preg_replace('/^(.*)-\d+-.*$/', '$1', $data);

if(!Registry::checkModel($model))
	exit();

$model = new $model();
$model -> loadRelatedData() -> setUser($admin_panel -> user);
		
if($model -> checkRecordById($id) && $model -> checkIfFieldEditable($field) && 
	$model -> checkIfFieldVisible($field) && 
	$model -> checkDisplayParam('update_actions') && 
	$admin_panel -> user -> checkModelRights($model -> getModelClass(), 'update'))
{
	$model -> setId($id) -> read();
	$element = $model -> getElement($field);
	
	if($element -> getType() != 'bool' || 
		($model == 'users' && ($id == 1 || $id == $admin_panel -> user -> getId())) || 
		!$element -> getProperty('quick_change'))
		exit();
						
	$value = $element -> getValue() ? 0 : 1;
	$argument = ($model == 'users') ? 'self-update' : null;

	$model -> setValue($field, $value) -> update($argument) -> read();
	
	if($model -> getValue($field) != $value)
		exit();
	
	$css_class = $value ? 'bool-true' : 'bool-false';
	$bool_title = $value ? 'switch-off' : 'switch-on';
	
	Http::responseJson([
		'css_class' => $css_class,
		'title' => I18n::locale($bool_title)
	]);
}