<?php
include_once '../../config/autoload.php';

$query = str_replace('model=', 'action=create&model=', $_SERVER['QUERY_STRING']);
Http::redirect(Registry::get('AdminPanelPath').'?'.$query);