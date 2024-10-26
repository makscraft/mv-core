<?php
include "../../config/autoload.php";

Http::isAjaxRequest('post', true);
$system = new System('ajax');

if(isset($_POST['orders_update_data'], $_POST['model'], $_POST['model_field'], $_POST["admin-panel-csrf-token"]) && 
   $system -> registry -> checkModel($_POST['model']) && $_POST["admin-panel-csrf-token"] == $system -> getToken())
{
	$system -> runModel($_POST['model']);
	$orders = array();
	
	if($object = $system -> model -> getElement($_POST['model_field']))
		if($object -> getType() == 'order')
			foreach(explode('_', $_POST['orders_update_data']) as $value)
			{
				$data = explode('-', $value);
				$orders[intval($data[0])] = intval($data[1]);
			}
			
	if(count($orders) && $system -> model -> checkDisplayParam('update_actions'))
		$system -> model -> updateOrderField($_POST['model_field'], $orders);
	
	echo '1';
}