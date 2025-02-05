<?php
include_once '../config/autoload.php';

$admin_panel = new AdminPanel();

if(!$user_id = $admin_panel -> checkSessionAuthorization())
    $user_id = $admin_panel -> checkCookieAuthorization();

if(!$user_id)
    if(Http::isAjaxRequest())
        Http::sendStatusCodeHeader(401, true);
    else
        Http::redirect(Registry::get('AdminPanelPath').'login/');

$admin_panel -> setUser(new User($user_id));
$admin_panel -> user -> session -> continueSession();

$admin_panel -> defineCurrentUserRegion();
$view = $admin_panel -> defineRequestedView();

include_once $view;