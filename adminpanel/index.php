<?php
include_once '../config/autoload.php';
$system = new System();
$admin_panel = new AdminPanel($system -> user);
$view = $admin_panel -> defineRequestedView();

//Debug::pre($view);
//Debug::pre($_SESSION);

include_once $view;