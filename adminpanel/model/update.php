<?php
include_once '../../config/autoload.php';

$query = str_replace('model=', 'action=update&model=', $_SERVER['QUERY_STRING']);
Http::redirect(Registry::get('AdminPanelPath').'?'.$query);