<?php
include_once '../config/autoload.php';

$admin_panel = new AdminPanel();

if(!$user_id = $admin_panel -> checkSessionAuthorization())
    $user_id = $admin_panel -> checkCookieAuthorization();

if(!$user_id)
    if(Http::isAjaxRequest())
        Http::sendStatusCodeHeader(401, true);
    else
    {
        Session::start('admin_panel_login');
        
        if($_SERVER['QUERY_STRING'])
            Session::set('login-back-url', '?'.$_SERVER['QUERY_STRING']);

        Session::destroy('admin_panel');
        Http::redirect(Registry::get('AdminPanelPath').'login/');
    }

$admin_panel -> setUser(new User($user_id));
$admin_panel -> user -> session -> continueSession();

$admin_panel -> defineCurrentUserRegion();
$view = $admin_panel -> defineRequestedView();

include_once $view;