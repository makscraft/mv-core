<?php
Http::isAjaxRequest('post', true);
$token = Http::fromPost('adminpanel_csrf_token');
$model = Http::fromPost('model');

if($model == 'users')
	exit();
	
if(Registry::checkModel($model))
{
	$result = array("general_errors" => "", "updated" => 0, "wrong_fields" => []);
	$data = [];
	$model = new $model();
	$model -> loadRelatedData() -> setUser($admin_panel -> user);
	
	if($admin_panel -> createCSRFToken() !== $token)
	{
		$result["general_errors"] = "<p>".I18n::locale("error-wrong-token")."</p>";
		Http::responseJson($result);
	}
	
	foreach($_POST as $key => $value) //Collecting fields and values for operation
		if(preg_match("/^quick-edit-.*-\d+$/", $key))
		{
			$values = explode("-", $key);
			$data[intval($values[3])][$values[2]] = trim($value);
		}
		
	$unique_fields = [];
	
	foreach($model -> getElements() as $name => $object) //All fields which must have unique values
		if($object -> getProperty("unique"))
			$unique_fields[$name] = [];
			
	foreach($data as $id => $values) //Data processing and validation
	{
		if(!$model -> checkRecordById($id))
			continue;
			
		$has_errors = $model -> drop() -> read($id) -> getDataFromArray($values) -> validate(array_keys($values));
		
		if($has_errors) //In case of move of values of unique fields to avoid not correct errors
		{
			$errors = $model -> getErrors();
			
			foreach($errors as $key => $error)
				if(is_array($error) && isset($error[1], $error[2]) && $error[1] == "{error-unique-value}")
				{
					$query = "SELECT `id` FROM `".$model -> getTable()."` 
							  WHERE `".$error[2]."`=".$model -> db -> secure($values[$error[2]]);
					
					$record_id = $model -> db -> getCell($query);
					
					if($record_id && array_key_exists($record_id, $data) && $data[$record_id][$error[2]] != $values[$error[2]])
					{
						$model -> removeError($key);
						
						if(!count($model -> getErrors()))
							$has_errors = false;
					}
				}
		}
		
		if(!$has_errors) //Extra unique fileds check if we have new same values
			foreach($values as $key => $value)
				if(isset($unique_fields[$key]) && $value != "")
					if(!in_array($value, $unique_fields[$key]))
						$unique_fields[$key][] = $value;
					else
					{
						$model -> addError(array($model -> getCaption($key), "{error-unique-value}", $key));
						$has_errors = true;
					}
		
		if($has_errors)
		{
			$errors = $model -> getErrors();
			
			foreach($errors as $error)
			{
				if(is_array($error) && isset($error[2]))
				{
					$error_text = Model::processErrorText($error, $model -> getElement($error[2]));
					$result["wrong_fields"][] = "#quick-edit-".$error[2]."-".$id;
				}
				else
					$error_text = $error;
				
				if(strpos($result["general_errors"], $error_text) === false)
					$result["general_errors"] .= "<p>".$error_text."</p>";
			}				
		}
	}

	if(!count($result["wrong_fields"]) && !$result["general_errors"]) //Final data updating if no errors
	{
		$db = Database::instance();
		$db -> beginTransaction();
		
		foreach($data as $id => $values)
		{
			if(!$model -> checkRecordById($id) || !count($values))
				continue;
				
			$model -> drop() -> read($id) -> getDataFromArray($values) -> update();
			$result['updated'] ++;
		}
		
		$db -> commitTransaction();
		
		FlashMessages::add('success', I18n::locale('done-update'));
	}
	
	$result["wrong_fields"] = implode(",", $result["wrong_fields"]);

	Http::responseJson($result);
}