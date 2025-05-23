<?php
/**
 * Garbage model stores deleted records from all models in the admin panel.
 * Records are moved to the garbage after deletion in the admin panel or via the delete() method from the front.
 * Records can either be restored or permanently deleted, including all associated data and versions.
 */
class Garbage extends Model
{
	protected $name = '{garbage}';
	
	protected $model_elements = [
		['{module}', 'enum', 'module'],
		['{row_id}', 'int', 'row_id'],
		['{name}', 'char', 'name'],
		['{content}', 'text', 'content'],
		['{date}', 'date_time', 'date']
	];
	
	protected $model_display_params = [
		'hidden_fields' => ['row_id','content'],
		'create_actions' => false
	];
	
	public function __construct()
	{
		$values_list = [];
		$db = Database::instance();
		$registry = Registry::instance();
		$values = $db -> getColumn("SELECT DISTINCT `module` FROM `garbage`");
		
		foreach($values as $model_class)
			if($model_class != 'log' && $registry -> checkModel($model_class))
			{
				$object = new $model_class();
				$values_list[$model_class] = $object -> getName();
			}
			
		natcasesort($values_list);		
		$this -> model_elements[0][] = ['values_list' => $values_list];
		
		parent::__construct();
	}
	
	public function displaySortableTable()
	{
		return parent::displaySortableTable('garbage');
	}
	
	public function save($model, $row_id, $name, $content)
	{
		$content = Service::serializeArray($content);
		
		$this -> db -> query("INSERT INTO `".$this -> table."`(`module`,`row_id`,`name`,`content`,`date`)
							  VALUES(".$this -> db -> secure($model).",'".$row_id."',".$this -> db -> secure($name).",
							  		 ".$this -> db -> secure($content).",".$this -> db -> now("with-seconds").")");
	}
	
	public function delete()
	{
		$content = $this -> getById();		
		$model_object = $this -> registry -> checkModel($content['module']) ? new $content['module']() : null;
		
		if($model_object && method_exists($model_object, "beforeFinalDelete"))
			$model_object -> beforeFinalDelete($content['row_id'], Service::unserializeArray($content['content']));
		
		//Final delete sql query
		$this -> db -> query("DELETE FROM `".$this -> table."` WHERE `id`='".$this -> id."'");
		
		$versions = new Versions($content['module'], $content['row_id']);
		
		if($model_object) //Deletes old versions of just deleted record 
		{
			$versions -> cleanFiles(Service::unserializeArray($content['content']), $model_object -> defineFilesTypesFields());
			$versions -> clean();
		}
		else
			$versions -> cleanRecordVersions();

		$name = $this -> tryToDefineName($content);
		
		if($this -> user)
			Log::write($this -> getModelClass(), $this -> id, $name, $this -> user -> getId(), "delete");		
		
		$this -> updateManyToManyTables();
		
		if($model_object && method_exists($model_object, "afterFinalDelete"))
			$model_object -> afterFinalDelete($content['row_id'], Service::unserializeArray($content['content']));		
	}
	
	public function restore()
	{
		$content = $this -> getById();
		$content['content'] = Service::unserializeArray($content['content']);
		$model = new $content['module']();
		
		if($error = $model -> checkUniqueFields($content['content']))
		{
			$this -> addError([$error, "{error-unique-restore}", ""]);
			return false; //Stop operation and exit
		}
		
		$names = $values = [];
		
		foreach($content['content'] as $field => $value) //Collects record's values
		{
			$object = $model -> getElement($field);
			
			if($object && $object -> getType() == "many_to_many")
				$object -> setValue($value);
			else if(($object && $field != "many_to_one") || $field == "id")
			{
				$names[] = "`".$field."`";
				$values[] = $this -> db -> secure($value);
			}
		}
		
		$in_transaction = (method_exists(Database::$pdo, "inTransaction")) ? Database::$pdo -> inTransaction() : true;
		
		if(!$in_transaction)
			$this -> db -> beginTransaction();

		//SQL to get record back to model table
		$query = "INSERT INTO `".$model -> getTable()."`(".implode(',', $names).") VALUES(".implode(',', $values).")";

		if(is_array($content['content']) && count($content['content']) && isset($content['content']['id']) && 
		   $content['content']['id'] && !$this -> db -> getCount($model -> getTable(), "`id`='".$content['content']['id']."'"))
		{
			if(method_exists($model, "beforeRestore"))
				if($model -> beforeRestore($content['content']['id'], $content['content']) === false)
				{
					$this -> addErrors($model -> getErrors());					
					return false; //Stop operation and exit if false was returned by pre action
				}
			
			$this -> db -> query($query);
			$model -> setId($content['content']['id']) -> updateManyToManyTables();
			$this -> db -> query("DELETE FROM `".$this -> table."` WHERE `id`='".$this -> id."'"); //Removes from garbage
			
			$name = $this -> tryToDefineName($content);
			
			if($this -> user)
				Log::write($this -> getModelClass(), $this -> id, $name, $this -> user -> getId(), "restore");
			
			if(method_exists($model, "afterRestore"))
				$model -> afterRestore($content['content']['id'], $content['content']);
				
			Cache::cleanByModel($content['module']);

			if(!$in_transaction)
				$this -> db -> commitTransaction();
		}
		else
		{
			$this -> addError(I18n::locale("error-unique-restore", array("field" => "id")));

			return false;
		}
	}
	
	public function emptyGarbage(int $limit = 0)
	{
		$this -> db -> beginTransaction();
		
		$ids = $this -> db -> getColumn("SELECT `id` FROM `".$this -> table."`".($limit ? " LIMIT ".$limit : ""));
		
		foreach($ids as $id)
			$this -> setId($id) -> delete();
			
		$this -> db -> commitTransaction();
			
		return (bool) count($ids);
	}
}
