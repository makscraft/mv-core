<?php
/**
 * Main class for records management (similar to Active Record).
 * Record does not extend the methods of model, it uses only own methods.
 * This class is used mostly at the front of project.
 * Contains CRUD methods and base methods for passing the content into records.
 */
class Record extends Content
{
	public function __construct(array $data, object $model)
	{
		//Keeps the link to parent model object
		$this -> model = $model;
			
		parent::__construct($data);
	}

	/**
	 * Returns the class name lowercased of the record's parent model.
	 */
	public function getModelName()
	{
		return $this -> model -> getModelClass();
	}

	/**
	 * Returns the class name of the record's parent model.
	 */
	public function getModelClass()
	{
		return get_class($this -> model);
	}
	
	/**
	 * Returns field value by key (if exists).
	 */
	public function __get(string $key)
	{
		if(isset($this -> content[$key]))
			return $this -> content[$key];
		else
		{
			//Lazy load of m2m values from linking table
			if($object = $this -> model -> getElement($key))
				if($object -> getType() == 'many_to_many' && $this -> id)
				{
					$ids = $object -> setRelatedId($this -> id) -> getSelectedValues();
					$this -> content[$key] = $ids;

					return $this -> content[$key];
				}
		}
	}
	
	/**
	 * Sets field value by key (if exists).
	 */
	public function __set(string $key, mixed $value)
	{
		if($key != 'id' && $this -> model -> getElement($key))		
			$this -> content[$key] = is_string($value) ? trim($value) : $value;
	}

	/**
	 * Add one or many ids for mny to many field value.
	 * @param string $field name of field in model
	 * @param mixed $value one id or array of ids (opposite model in m2m)
	 */
	public function addValueM2M(string $field, mixed $value)
	{
		if($object = $this -> model -> getElement($field))
			if($object -> getType() == 'many_to_many' && $value)
			{
				$ids = $this -> __get($field);
				$ids = is_array($ids) ? $ids : ($ids == '' ? [] : preg_split('/\s*\,\s*/', $ids));

				if(!in_array($value, $ids))
					$ids[] = $value;

				$this -> content[$field] = $ids;
			}
	}
	
	/**
	 * Returns the array of all fields' values of the record.
	 */
	public function getValues()
	{
		return $this -> content;
	}

	/**
	 * Returns the array of all fields' values of the record.
	 */
	public function all()
	{
		return $this -> getValues();
	}
	
	/**
	 * Sets the list (array) of values for record's fields.
	 * @param array $values like ['name' => 'Hello']
	 */
	public function setValues(array $values)
	{
		foreach($values as $field => $value)			
			if($this -> model -> getElement($field))
				$this -> content[$field] = is_string($value) ? trim($value) : $value;
				
		return $this;
	}
	
	/**
	 * Gets values of fields ready for create/update action for current DB record.
	 */
	public function prepareContentValues()
	{
		$prepared_values = [];
		$version = Registry::getInitialVersion();
		$fast = ['int', 'float', 'bool', 'parent', 'enum', 'password'];
		
		foreach($this -> content as $field => $value)
		{
			if($field == "id")
			{
				$prepared_values["id"] = intval($value);
				continue;
			}	
			
			$object = $this -> model -> getElement($field);
			
			if(!$object)
			{
				unset($this -> content[$field]);
		 		continue;
			}
			
			$object -> setValue(null);
			$type = $object -> getType();
			$value = is_string($value) ? trim($value) : $value;
			
			if($type === 'text' && $object -> getProperty('virtual'))
				continue;
			
			if(in_array($type, $fast))
				$prepared_values[$field] = $object -> setValue($value) -> prepareValue();
			else if($type === 'date' || $type === 'date_time')
			{
				if(!preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}(:\d{2})?)?$/", $value))
					$prepared_values[$field] = I18n::dateForSQL($value);
				else
					$prepared_values[$field] = $value;
			}
			else if($type === 'order')
			{
				if($value)
				{
					$prepared_values[$field] = intval($value);
					continue;
				}

				$object = $this -> model -> getElement($field);
				
				if($parent_field = $this -> model -> getParentField())
					if(isset($this -> content[$parent_field]) && $this -> content[$parent_field])
						$this -> model -> setParentId($this -> content[$parent_field]);
							
				$prepared_values[$field] = $this -> content[$field] = $this -> model -> setLastOrder($object);
			}
			else if($type === 'text')
			{
				if($object -> getProperty('display_method'))
				{
					if($version >= 2.4)
						$prepared_values[$field] = str_replace("'", "&#039;", $value);
					
					continue;
				}

				if($object -> getProperty('json'))
				{
					$value = is_array($value) ? $value : json_decode(trim($value), true);
					$prepared_values[$field] = is_array($value) ? json_encode($value) : '';

					continue;
				}

				$search = array("'", "\t");
				$replace = array("&#039;", "");
				$value = is_string($value) ? $value : "";
				$prepared_values[$field] = str_replace($search, $replace, $value);
								
				if(!$object -> getProperty('rich_text'))
				{
					$search = array("&", "<", ">", '"', "\\");
					$replace = array("&amp;", "&lt;", "&gt;", "&quot;", "&#92;");		
					
					$prepared_values[$field] = str_replace($search, $replace, $prepared_values[$field]);
				}
			}
			else if($type == 'image' || $type == 'file')
			{
				if(preg_match("/^userfiles\/models\//", $value))
					$value = Service::addFileRoot($value);
				
				$object -> setRealValue($value, basename($value));
				
				if($new_path = $object -> copyFile(get_class($this -> model)))
				{
					$prepared_values[$field] = Service::removeFileRoot($new_path);
					
					if($version >= 2.2)
						$this -> content[$field] = $prepared_values[$field];
				}
				else
					$prepared_values[$field] = '';
			}
			else if($type == 'multi_images')
			{
				$object -> setValue($this -> content[$field]);
				$object -> copyImages($this -> model -> getModelClass());
				$prepared_values[$field] = $object -> getValue();
			}
			else if($type == 'many_to_many')
			{
				$value = is_array($value) ? implode(',', $value) : $value;
				$prepared_values[$field] = preg_replace('/[^\d\,]/', '', $value);
			}
			else
			{
				$value = strval($value);
				$prepared_values[$field] = htmlspecialchars($value, ENT_QUOTES);
			}
		}
		
		return $prepared_values;
	}
	
	/**
	 * Returns title (caption, not key) of particular enum field. 
	 */
	public function getEnumTitle(string $field)
	{
		$object = $this -> model -> getElement($field);
		
		if($object && isset($this -> content[$field])) //If this field exists
			if(!isset($this -> enum_values[$field][$this -> content[$field]]))
			{
				if($object -> getProperty("long_list")) //If long list we get only one cell
					//Some inside cache to keep the extracted values
					$this -> enum_values[$field][$this -> content[$field]] = $object -> getValueName($this -> content[$field]);
				else 
				{
					$object -> defineValuesList(); //We take full values list
					
					//Some inside cache to keep the extracted values
					$this -> enum_values[$field] = $object -> getValuesList();									
				}
				
				if(isset($this -> enum_values[$field][$this -> content[$field]]))
					return $this -> enum_values[$field][$this -> content[$field]];
			}
			else
				return $this -> enum_values[$field][$this -> content[$field]];
	}	
	
	/**
	 * Creates new record in model's table in database, via INSERT query.
	 */
	public function create()
	{
		if(get_parent_class($this -> model) === 'ModelSimple' || $this -> id)
			return;
			
		$params = [];
		$prepared_values = $this -> prepareContentValues();
						
		foreach($prepared_values as $field => $value)
			if($this -> model -> getElement($field))
				$params[$field] = $value;
				
		if(count($params))
		{
			$this -> id = $this -> model -> createRecord($params);

			if($this -> id)
			{
				$this -> content = $prepared_values;
				$this -> content['id'] = $this -> id;
			}

			return $this -> id;
		}
	}
	
	/**
	 * Updates the record in model's table in database, via UPDATE query.
	 */
	public function update()
	{
		if($this -> id && get_parent_class($this -> model) !== 'ModelSimple')
		{	
			$params = [];
			$prepared_values = $this -> prepareContentValues();
						
			foreach($prepared_values as $field => $value)
				if($this -> model -> getElement($field))
					$params[$field] = $value;
			
			if(count($params))
			{
				$this -> model -> updateRecord($this -> id, $params);
				$this -> content = $prepared_values;
			}
		}
		
		return $this;
	}

	/**
	 * Creates (or updates if created before) record in model's table in database, via INSERT / UPDATE query.
	 */
	public function save()
	{
		if($this -> id)
			$this -> update();
		else
			$this -> create();

		return $this;
	}
	
	/**
	 * Removes record from model's table in database, via DELETE query.
	 * Does NOT put it in garbage table like in admin panel.
	 */
	public function delete()
	{
		if($this -> id && get_parent_class($this -> model) != "ModelSimple")
		{
			$this -> model -> deleteRecord($this -> id) -> drop();
			$this -> id = null;
			$this -> content = [];
		}
			
		return $this;
	}

	/**
	 * Returns the value of record field (many_to_many or multi_images) in array format.
	 * Can apply sql conditions for m2m field, like ['active' => 1].
	 */
	public function asArrays(string $field, array $conditions = [])
	{
		if($element = $this -> model -> getElement($field))
			if($element -> getType() === 'many_to_many')
			{
				$ids = $this -> __get($field);
				$table = strtolower($element -> getProperty('related_model'));

				if(!is_array($ids) || !count($ids))
					return [];

				$conditions['table->'] = $table;
				$conditions['id->in'] = $ids;

				return $this -> model -> select($conditions);
			}
			else if($element -> getType() === 'multi_images')
				return MultiImagesModelElement::unpackValue($this -> __get($field));

		return [];
	}
	
	public function __call(string $method, $arguments)
	{
		if($method == "getContent")
			return $this -> getValues();
		else if($method == "getEnumValue")
			return $this -> getEnumTitle($arguments[0]);
		else
		{
			$trace = debug_backtrace();
			$message = "Call to undefiend method '".$method."' of Record object of model '".get_class($this -> model)."'";
			$message .= ', in line '.$trace[0]['line'].' of file ~'.Service::removeDocumentRoot($trace[0]['file']);

			Debug::displayError($message, $trace[0]['file'], $trace[0]['line']);			
		}
	}
}
