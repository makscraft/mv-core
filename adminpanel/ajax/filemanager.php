<?php
Http::isAjaxRequest('post', true);
$filemanager = new Filemanager();
$filemanager -> setUser($admin_panel -> user);

if(isset($_POST['number_files']) && intval($_POST['number_files']))
{
	$text = I18n::locale('number-files', ['number' => intval($_POST['number_files']), 'files' => '*number']);
	Http::responseText($text);
}