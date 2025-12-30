<?php
/**
 * Search manager to look for records in all active MV modeles accordin to passed text pattern.
 */
class Searcher
{
    /**
	 * Database manager object.
	 * @var object Database
	 */
	public $db;

	/**
	 * Models rows total numbers cache, to speed up the search.
	 * @var array
	 */
	protected $totals = [];

	/**
	 * Max allowed records limit in model for search in it.
	 */
	public const MODEL_ROWS_LIMIT = 5000;

	/**
	 * Max allowed records limit in model for search in it (if ajax autocomplete).
	 */
	public const MODEL_ROWS_LIMIT_AJAX = 2000;

    public function __construct()
	{
        $this -> db = Database::instance();
		
		if(null === $totals = AdminPanel::getAdminPanelSettingCacheValue('admin_search_models', 'totals'))
		{
			$totals = [];

			foreach(array_keys(Registry::get('ModelsLower')) as $model)
				$totals[$model] = (new $model) -> countRecords();

			AdminPanel::saveAdminPanelSettingCacheValue('admin_search_models', 'totals', $totals, 60 * 10);
		}

		$this -> totals = $totals;
    }

	public function allow($model, $is_ajax = false): bool
	{
		if(isset($this -> totals[$model]) && $this -> totals[$model] > 0)
		{
			$limit = $is_ajax ? self::MODEL_ROWS_LIMIT_AJAX : self::MODEL_ROWS_LIMIT;
			return $this -> totals[$model] <= $limit;
		}

		return false;
	}

    public function searchInAllModels(string $request)
	{
		$result = $this -> searchInAllModelsAjax($request, true);
		$request_re = Service::prepareRegularExpression($request);
		$number = 0;
		$html_strings = $sorted_result = [];
		
		if(!is_array($result) || !count($result) || !isset($result["rows"]))
			return array("number" => 0, "html" => "");
		
		foreach($result["rows"] as $key => $row) //Makes some relevence 
			if(!isset($row["simple_model"]))
			{
				$model = $result["models"][$row["model"]];
				$name = $model -> tryToDefineName($row);
			
				if(preg_match("/^".$request_re."$/ui", $name)) //Exact name matches go upper
				{
					array_unshift($sorted_result, $row);
					unset($result["rows"][$key]);
				}
				else if(preg_match("/".$request_re."/ui", $name))
				{
					$sorted_result[] = $row;					
					unset($result["rows"][$key]);
				}
			}
		
		$sorted_result = array_merge($sorted_result, $result["rows"]);
		
		//Final check if we have needed phraze in results
		foreach($sorted_result as $key => $row)
		{
			$found = false;
			
			foreach($row as $value)
				if(preg_match("/".$request_re."/ui", strip_tags(strval($value))))
				{
					$found = true;		
					break;
				}
			
			if(!$found)
				unset($sorted_result[$key]);
		}
		
		foreach($sorted_result as $row) //Html output process
		{
			$model = $result["models"][$row["model"]];
			$html = "<div>\n";
			$url = Registry::get("AdminPanelPath")."?model=";
			$name = "";
			
			if(isset($row["simple_model"])) //Name of result for simple nodel
			{
				$url .= $row["model"]."&action=simple";				
				$html .= "<p class=\"found-name\">".(++ $number).". ".I18n::locale("simple-module");
				$html .= " <a class=\"name\" href=\"".$url."\">".$model -> getName()."</a></p>\n";
			}
			else //Name of result for regular model
			{
				$html .= "<p class=\"found-name\">".(++ $number).". <a class=\"name\" href=\"".$url;
				$html .= $row["model"]."&action=update&id=".$row["id"]."\">\n";
				
				$name = $model -> tryToDefineName($row);
				
				$html .= preg_replace("/(".$request_re.")/ui", "<span>$1</span>", $name);				
				$html .= "</a> ".I18n::locale("module").": <a href=\"".$url.$row["model"]."&action=index\">";
				$html .= $model -> getName()."</a></p>\n";
			}
				
			$description = false;
			$fields_types = array("char", "email", "redirect", "url", "text");

            foreach($row as $field => $value)
                $row[$field] = strval($value);
				
			foreach($row as $field => $value) //If field contains request text we mark it with span
				if($field != "id" && $field != "model" && preg_match("/".$request_re."/ui", $value))
				{
					if(!$object = $model -> getElement($field))
						continue;

					$type = $object -> getType();
						
					if(!in_array($type, $fields_types) || ($name && $name == $value))
						continue;
					else if($type == "text") //Text field process, cut off text parts
					{
						$text = strip_tags($value);
						
						if(!preg_match("/".$request_re."/ui", $text))
							continue;
						
						$start = mb_stripos($text, $request, 0, "utf-8");
								
						if($start > 50)
						{
							$text = mb_substr($text, $start - 40, 400, "utf-8");
							$text = "... ".trim(preg_replace("/^[^\s]*/ui", "", $text));
						}
								
						$value = Service::cutText($text, 370, " ...");
					}

					if(!$description)
					{
						$description = true;
						$html .= "<p class=\"found-text\">";
					}
						
					$html .= $model -> getCaption($field).": ";
					$html .= preg_replace("/(".$request_re.")/ui", "<span>$1</span>", $value)."<br />";
				}
					
			if($description)
			{
				$html = preg_replace("/<br \/>$/", "", $html);
				$html .= "</p>\n";
			}
			
			$html .= "</div>\n";
			
			$html_strings[] = $html;
		}
		
		return [
			'number' => count($sorted_result),
			'html' => $html_strings
		];
	}
	
	public function searchInAllModelsAjax(string $request, bool $full_search)
	{
		$fields_types = ['char', 'email', 'redirect', 'url'];
		$results = $model_objects = $full_results = [];
		$request_sql = $this -> db -> secure('%'.$request.'%'); //Prepare search phrase
		$limit = 10;
		
		if(mb_strlen($request, 'utf-8') < 2) //Too short request
			return $results;

		foreach(array_keys(Registry::get('ModelsLower')) as $model_name)
			if($this -> allow($model_name, !$full_search))
				$model_objects[$model_name] = new $model_name(); //Creates models objects
		
		//Search in all allowed fields exept for text type
		$search_data = $this -> searchData($model_objects, $request_sql, $fields_types);
		
		if($full_search) //Data for full search in admin panel
			foreach($search_data["rows"] as $row)
				$full_results[$row["model"]][$row["id"]] = $row;
		
		foreach($search_data["rows"] as $row) //Autocomplete data process
			foreach($row as $field => $value)
				if(in_array($field, $search_data["fields"])) //If it's allowed field
				{
					$value = mb_strtolower(strip_tags(strval($value)), 'utf-8');
					$value = htmlspecialchars_decode($value, ENT_QUOTES);
								
					if(preg_match("/".Service::prepareRegularExpression($request)."/ui", $value))
						if(!in_array($value, $results))
							$results[] = $value; //Adds new value for autocompllete
										
					if(count($results) >= $limit && !$full_search) //If its limit for ajax autocomplete
						return $results;
				}

		if($full_search || count($results) < $limit) //Next step of search, goes throught text fields
		{
			$search_data = $this -> searchData($model_objects, $request_sql, ['text']);
			
			if($full_search) //Search results preparing for search page of admin panel
			{
				foreach($search_data["rows"] as $row)
					if(isset($row["simple_model"]))
					{
						foreach($row as $field => $value)
							if($field != "model" && $field != "simple_model" && $field != "id")
								$full_results[$row["model"]][0][$field] = $value;
							
						$full_results[$row["model"]][0]["model"] = $row["model"];
						$full_results[$row["model"]][0]["id"] = 0;
					}
					else if(!isset($full_results[$row["model"]][$row["id"]]))
						$full_results[$row["model"]][$row["id"]] = $row;
						
				$final_full_results = [];
						
				foreach($full_results as $key => $rows)
					$final_full_results = array_merge($final_full_results, $rows);
						
				return [
					'models' => $model_objects,
					'rows' => $final_full_results
				];
			}
			
			foreach($search_data["rows"] as $row) //Ajax autocomplete results process
				foreach($row as $field => $value)
					if(in_array($field, $search_data["fields"]))
					{
						$value = strip_tags($value);
						$value = htmlspecialchars_decode($value, ENT_QUOTES);
						$re = "\s\.,:;!\?\"'\+\(\)\[\}\^\$\*";
						preg_match("/[^".$re."]*".$request."[^".$re."]*/ui", $value, $matches);
							
						foreach($matches as $text)
						{
							$text = mb_strtolower($text, "utf-8");
								
							if(!in_array($text, $results))
								$results[] = $text;
									
							if(count($results) >= $limit && !$full_search)
								return $results;
						}
					}
		}
		
		return $results;
	}
	
	private function searchData(array $models, string $request_sql, array $types)
	{
		$rows = $fields = [];

		foreach($models as $model) //Search in all passed models
		{
			$simple_model = (get_parent_class($model) === 'ModelSimple');
			$query = [];
			$fields_sql = ['id'];
			
			foreach($model -> getElements() as $object)
				if(in_array($object -> getType(), $types))
				{
					if($object -> getType() == 'text')
						if($object -> getProperty('display_method') || $object -> getProperty('virtual'))
							continue;
					
					$fields[] = $object -> getName();
					$fields_sql[] = $object -> getName();
					
					//SQL query preparing
					if($simple_model)
						$query[] = "(`key`='".$object -> getName()."' AND `value` LIKE ".$request_sql.")";
					else
						$query[] = "`".$object -> getName()."` LIKE ".$request_sql;
				}
				
			if(!count($query)) //If no fields for search in current model
				continue;
			
			//Search SQL query executiom
			$fields_sql = $simple_model ? '*' : '`'.implode('`, `', $fields_sql).'`';
			$query = "SELECT ".$fields_sql." FROM `".$model -> getTable()."` WHERE ".implode(" OR ", $query);
			$found_rows = $this -> db -> getAll($query);
			
			if(!count($found_rows))			
				continue;
			
			if($simple_model) //Results from simple models search
			{
				$simple_row = [];
				
				foreach($found_rows as $row)
					$simple_row[$row["key"]] = $row["value"];
					
				$simple_row["id"] = 0;
				$simple_row["simple_model"] = true;
				$found_rows = [$simple_row];
			}
			
			foreach($found_rows as $key => $row)
				$found_rows[$key]["model"] = $model -> getModelClass();
				
			$rows = array_merge($rows, $found_rows);
		}			
		
		return [
			'rows' => $rows,
			'fields' => array_unique($fields)
		];
	}
}