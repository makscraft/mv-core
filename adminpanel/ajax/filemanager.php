<?php
include '../../config/autoload.php';

Http :: isAjaxRequest('post', true);

$system = new System('ajax');
$filemanager = new Filemanager();
$filemanager -> setUser($system -> user);

if(isset($_POST['number_files']) && intval($_POST['number_files']))
{
	$text = I18n :: locale('number-files', ['number' => intval($_POST['number_files']), 'files' => '*number']);
	Http :: responseText($text);
}