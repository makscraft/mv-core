<?php
include_once '../config/autoload.php';
$system = new System();
$registry = Registry::instance();
$admin_panel = new AdminPanel($system -> user);
$admin_panel -> defineCurrentUserRegion();
$view = $admin_panel -> defineRequestedView();

//Debug::pre($view);
//Debug::pre($_SESSION);

include_once $view;