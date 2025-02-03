<?php 
Http::isAjaxRequest('post', true);

//Process of garbage cleanup
if(isset($_POST['empty-recycle-bin']))
{
	$model = new Garbage();
	
	if($admin_panel -> user -> checkModelRights("garbage", "delete"))
		if($_POST['empty-recycle-bin'] == "count") //If just count records to delete from garbage
		{
			if(!$garbage_number = Database::instance() -> getCount("garbage"))
				exit();
			
			$arguments = array('number' => $garbage_number, 'records' => '*number');
			echo I18n::locale('number-records', $arguments);
		}
		else if($_POST['empty-recycle-bin'] == "process" && isset($_POST['iterations-left'])) //Process of final delete of records
		{
			sleep(1);
			set_time_limit(180);			
			
			$model -> emptyGarbage(50);
			
			if(intval($_POST['iterations-left']) == 1)
			{
				Filemanager::makeModelsFilesCleanUp();
				FlashMessages::add('success', 'done-delete');
			}
		}
	
	exit();
}

//Response when call bulk operation in main table of model in admin panel
$xml = "<response>\n";
$model = Http::fromPost('model');

if($model && (Registry::checkModel($model) || $model == 'garbage'))
{
	$ids = explode(',', $_POST['ids']);
	$simple_types = array("date", "date_time", "int", "float");
	
	if($_POST['action'] != 'delete' && $_POST['action'] != 'restore')
	{
		$model = new $model();
		$object = $model -> getElement($_POST['action']);
		
		if(!$object)
			$xml .= "<error>1</error>\n";
		else if($object -> getType() == 'bool')
		{
			$key = $_POST['value'] ? 'yes' : 'no';
			$xml .= "<value>".I18n::locale($key)."</value>\n";
		}
		else if($object -> getType() == 'enum')
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$object -> getDataForMultiAction()."</values_list>\n";
			
			$empty_value = $object -> getProperty("required") ? false : $object -> getProperty("empty_value");			
			$xml .= "<empty_value>".intval($empty_value)."</empty_value>\n";
		}
		else if($object -> getType() == 'parent')
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$model -> defineAvailableParents($ids)."</values_list>\n";
		}
		else if(($object -> getType() == 'many_to_many' ||  $object -> getType() == 'group') && 
		        ($_POST['value'] == "add" || $_POST['value'] == "remove"))
		{
			if($object -> getProperty("long_list"))
				$xml .= "<long_list>".intval($object -> getProperty("long_list"))."</long_list>\n";
			else
				$xml .= "<values_list>\n".$object -> getDataForMultiAction()."</values_list>\n";
		}
		else if(!in_array($object -> getType(), $simple_types))
			$xml .= "<error>1</error>\n";
			
		if($object)
		{
			$xml .= "<caption>".$object -> getCaption()."</caption>\n";
			$xml .= "<type>".$object -> getType()."</type>\n";
		}
	}
	
	if($_POST['action'] == 'delete' || $_POST['action'] == 'restore')
	{
		$arguments = array('number' => count($ids), 'records' => '*number');
		$xml .= "<number_records>".I18n::locale('number-records', $arguments)."</number_records>\n";
	}
	else
	{
		$arguments = array('number' => count($ids), 'for-record' => '*number');
		$xml .= "<number_records>".I18n::locale('number-for-records', $arguments)."</number_records>\n";
	}
}
else
	$xml .= "<error>1</error>\n";

$xml .= "</response>\n";

Http::responseXml($xml);