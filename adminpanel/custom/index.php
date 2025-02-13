<?php
include_once '../../config/autoload.php';

$query = str_replace('view=', 'custom=', $_SERVER['QUERY_STRING']);
Http::redirect(Registry::get('AdminPanelPath').'?'.$query);