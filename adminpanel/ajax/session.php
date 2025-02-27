<?php
Http::isAjaxRequest('post', true);

if(isset($_POST['check']) || isset($_POST['continue']) || isset($_POST['get-online-users']))
{
	if(isset($_POST['check']))
		Http::responseText('1');
	else if(isset($_POST['get-online-users']))
		Http::responseJson($admin_panel -> user -> session -> checkOnlineUsers());
}