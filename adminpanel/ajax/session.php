<?php
include '../../config/autoload.php';
Http :: isAjaxRequest('post', true);

if(isset($_POST['check']) || isset($_POST['continue']) || isset($_POST['get-online-users']))
{
	$system = new System('ajax');

	if(isset($_POST['check']))
		echo $system -> ajaxRequestCheck() ? '1' : '';
	else if(isset($_POST['get-online-users']))
		Http :: responseJson($system -> user -> session -> checkOnlineUsers());
}