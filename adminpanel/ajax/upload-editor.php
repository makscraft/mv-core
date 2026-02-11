<?php
$result = ['error' => ['message' => I18n::locale('error-data-transfer')]];

if(Http::isPostRequest() && Http::requestHas('type') && Http::fromGet('token') === Editor::createSecurityToken() && isset($_FILES['upload']))
{
	$object = null;

 	if(Http::fromGet('type') === 'image')
		$object = new ImageModelElement(I18n::locale('upload-image'), 'image', 'image', ['files_folder' => 'images']);
	else if(Http::fromGet('type') === 'file')
		$object = new FileModelElement(I18n::locale('upload-file'), 'file', 'file', ['files_folder' => 'files']);
	
	if(is_object($object))
	{
		$object -> setValue($_FILES['upload']);

		if($object -> getError())
		{
			$error = [$object -> getCaption(), $object -> getError(), 'image'];
			$error = Model::processErrorText($error, $object);
			$error = str_replace(['&laquo;', '&raquo;'], '"', $error);

			$result = ['error' => ['message' => $error]];
		}
		else
			$result = [
				'url' => Service::addRootPath($object -> copyFile()),
				'name' => $object -> getProperty('file_name')
			];
	}
}

Http::responseJson($result);