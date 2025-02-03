<?php
Http::isAjaxRequest('post', true);

if(isset($_POST['orders_update_data'], $_POST['model'], $_POST['model_field'], $_POST['adminpanel_csrf_token']) && 
   Registry::checkModel($_POST['model']) &&  $_POST['adminpanel_csrf_token'] == $admin_panel -> createCSRFToken())
{
	$model = new $_POST['model']();
	$orders = [];
	
	if($object = $model -> getElement($_POST['model_field']))
		if($object -> getType() === 'order')
			foreach(explode('_', $_POST['orders_update_data']) as $value)
			{
				$data = explode('-', $value);
				$orders[intval($data[0])] = intval($data[1]);
			}
	
	if(count($orders) && $model -> checkDisplayParam('update_actions'))
		$model -> updateOrderField($_POST['model_field'], $orders);
}