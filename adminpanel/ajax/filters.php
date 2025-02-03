<?php
Http::isAjaxRequest('post', true);
$model = Http::fromPost('model');

if(Registry::checkModel($model))
{
	$model = new $model();
	$model -> loadRelatedData() -> runPagerFilterSorter() -> setUser($admin_panel -> user);

	if($filter = Http::fromPost('add-filter'))
		Http::responseHtml($model -> filter -> displayAdminFilters($filter, false));
	else if(null !== $show = Http::fromPost('show-filters'))	
		$admin_panel -> updateModelSessionSetting($model -> getModelClass(), 'show-filters', ($show ? 1 : 0));
}