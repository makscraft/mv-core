<?php
Http::isAjaxRequest('post', true);
$model = Http::fromPost('model');

if(Registry::checkModel($model) && isset($_POST['field'], $_POST['query']))
{
	$model = new $model();
	$model -> loadRelatedData();
	$object = $model -> getElement($_POST['field']);

	$ids = (isset($_POST['ids']) && $_POST['ids']) ? explode(',', $_POST['ids']) : false;
	$self_id = (isset($_POST['self_id']) && $_POST['self_id']) ? intval($_POST['self_id']) : false;
	$request = htmlspecialchars(trim($_POST['query']), ENT_QUOTES);
	
	if(is_array($ids) && count($ids))
		foreach($ids as $key => $id)
			$ids[$key] = intval($id);
	
	if(is_object($object))
		Http::responseHtml($object -> getOptionsForSearch($request, $ids, $self_id));
}