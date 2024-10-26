<?php
include "../../config/autoload.php";

Http::isAjaxRequest('post', true);
$system = new System('ajax');
		
if(isset($_POST['model'], $_POST['add-filter']) && $system -> registry -> checkModel($_POST['model']))
{
	$system -> runModel($_POST['model']);
	
	header("Content-Type: text/html");
	echo $system -> model -> filter -> displayAdminFilters($_POST['add-filter'], false);
}
else if(isset($_POST['model'], $_POST['show-filters']) && $system -> registry -> checkModel($_POST['model']))
{
	$system -> runModel($_POST['model']);
	$_SESSION['mv']['settings'][$system -> model -> getModelClass()]['show-filters'] = $_POST['show-filters'] ? 1 : 0;	
}