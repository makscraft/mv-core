<?php
include '../../config/autoload.php';

Http::isAjaxRequest('post', true);
$system = new System('ajax');

if(isset($_POST['switch-off']) && $_POST['switch-off'] == 'warnings')
{
	$_SESSION['mv']['closed-warnings'] = true;
	echo '1';
}