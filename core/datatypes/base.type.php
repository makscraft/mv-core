<?php
/**
 * Base abstract datatype, parent for all child types.
 * Contains general basic properties and accessors.
 */
abstract class ModelElement
{
	/**
	 * Name (string title) of field which is shown in left column of form table.
	 * @var string
	 */
	protected string $caption = '';

	/**
	 * All extra parameters for html element (id, class, cols, rows).
	 * @var string
	 */
	protected string $html_params = '';

	/**
	 * Name of element (html form tag parameter and db field name).
	 * @var string
	 */
	protected string $name = '';
	
	/**
	 * Says if this element must be filled in form.
	 * @var bool
	 */
	protected bool $required = false;

	/**
	 * Error collected during validation process.
	 * @var string
	 */
	protected ?string $error = null;

	/**
	 * Help text for form element to show additional information.
	 * @var string
	 */	
	protected string $help_text = '';

	/**
	 * Data type of element (int, char, bool, ...)
	 * @var string
	 */	
	protected string $type = '';

	/**
	 * Says if this field must have unique values in model table.
	 * @var bool
	 */
	protected bool $unique = false;

	/**
	 * Current value of element (field).
	 * @var mixed
	 */	
	protected mixed $value = null;

	/**
	 * Says if this field must have the same value as other field in form.
	 * @var string
	 */	
	protected ?string $must_match = null;

	/**
	 * Minimal length of field value.
	 * @var int
	 */	
	protected ?int $min_length = null;

	/**
	 * Maximal length of field value.
	 * @var int
	 */	
	protected ?int $max_length = null;

	/**
	 * Model name which this element is related to.
	 * @var string
	 */	
	protected ?string $model = null;

	/**
	 * Array of additioanl errors messages.
	 * @var array
	 */	
	protected array $custom_errors = [];
	
	/**
	 * Creates datatype object according to title, type, filed name and extra params.
	 */
	public function __construct(string $caption, string $type, string $name, array $extra_params = [])
	{
		//Sets passed values to object properties
		$this -> name = $name;
		$this -> caption = $caption;
		$this -> type = $type;

		if($type === 'password' && Registry::getInitialVersion() < 3.2)
			$this -> setProperty('salt', '');
		
		$properties = get_object_vars($this); //Gets all existing vars of object

		if(count($extra_params))
			foreach($extra_params as $property => $value)
				if(array_key_exists($property, $properties)) //If this object has this property we set it
					$this -> setProperty($property, $value);
				else
				{
					$message = "Undefined extra parameter '".$property."' in field '".$name."' of model '";
					$message .= ucfirst($extra_params['model'])."'.";
					Debug::displayError($message);
				}
	}
	
	/**
	 * Adds parameters to the element html tag (if params exist).
	 */
	public function addHtmlParams()
	{ 
		return $this -> html_params ? ' '.trim($this -> html_params) : '';
	}
	
	/**
	 * Sets value for datatype object.
	 */
	public function setValue(mixed $value)
	{
		//Deletes spaces from value and prevents XSS
		$this -> value = trim(strval($value));
		
		return $this;
	}
	
	/**
	 * Sets value without cleaning.
	 */
	public function passValue(mixed $value)
	{
		$this -> value = $value;
		return $this;
	}
	
	/**
	 * Returns field value.
	 */
	public function getValue() 
	{
		return $this -> value;
	}
	
	/**
	 * Cleans value, deleles html double html enteties.
	 */
	public function cleanValue()
	{
		if($this -> value)
			$this -> value = Service::cleanHtmlSpecialChars($this -> value);
		
		return $this;
	}

	/**
	 * Cleans value before displaying in html text input.
	 */
	public function cleanTextValueForInput(mixed $value)
	{
		if($value === null || $value === '')
			return '';

		$value = htmlspecialchars(trim($value), ENT_QUOTES);
				
		return Service::cleanHtmlSpecialChars($value);
	}
	
	/* Accessors */

	public function setRequired(bool $value) { $this -> required = $value; return $this; }
	public function setHelpText(string $value) { $this -> help_text = $value; return $this; }
	public function setCaption(string $value) { $this -> caption = $value; return $this; }
	public function setHtmlParams(string $value) { $this -> html_params = $value; return $this; }
	public function setError(?string $error) { $this -> error = $error; return $this; }
	
	public function getName() { return $this -> name; }
	public function getCaption() { return $this -> caption; }
	public function getType() { return $this -> type; }
	public function getError() { return $this -> error; }
	
	/**
	 * Returns property value if it exists.
	 */
	public function getProperty(string $property)
	{
		if(property_exists($this, $property))
			return $this -> $property;
	}
	
	/**
	 * Checks if the object has needed property.
	 */
	public function hasProperty(string $property)
	{
		return array_key_exists($property, get_object_vars($this));
	}
	
	/**
	 * Sets property value if it exists.
	 */
	public function setProperty(string $property, mixed $value)
	{
		$properties = get_object_vars($this);
		
		$int_properties = [
			'max_size', 'max_width', 'max_height', 'min_length', 'max_length', 'length', 'height',
			'form_preview_width', 'form_preview_height'
		];
		
		if($property === 'value')
			return $this -> setValue($value);
		
		if(array_key_exists($property, $properties))
		{
			if($property === 'min_max_length')
			{
				if(preg_match('/^\d+,\s*\d+$/', $value))
				{
					$numbers = explode(",", $value);
					$this -> min_length = abs(intval(trim($numbers[0])));
					$this -> max_length = abs(intval(trim($numbers[1])));
				}
			}
			else if(in_array($property, $int_properties))
				$this -> $property = is_numeric($value) ? abs(intval($value)) : null;
			else if(is_array($value) || is_bool($value))
				$this -> $property = $value;
			else if(is_string($value))
				$this -> $property = trim($value);
			else
				$this -> $property = $value;
		}
		else
		{
			$message = "Undefined extra parameter '".$property."' in field '".$this -> name."' of model '";
			$message .= ucfirst($this -> model)."'.";

			Debug::displayError($message);
		}
		
		return $this;
	}
	
	/**
	 * Returns error chosen between default and overriden error.
	 */
	public function chooseError(string $rule, string $default_text)
	{
		if(isset($this -> custom_errors[$rule]) && $this -> custom_errors[$rule])
			return $this -> custom_errors[$rule];
		else
			return $default_text;
	}
	
	/**
	 * Returns html help text for object.
	 */
	public function addHelpText()
	{
		if($this -> help_text)
		{
			if(preg_match("/^\{.*\}$/", $this -> help_text))
				$this -> help_text = I18n::locale(preg_replace("/^\{(.*)\}$/", "$1", $this -> help_text));
			
			return "<div class=\"help-text\">".trim($this -> help_text)."</div>\n";
		}

		return '';
	}
	
	/**
	 * Checks the element value according to datatype rules.
	 */
	abstract function validate();
	
	/**
	 * Generate html tag code of the element.
	 */
	abstract function displayHtml();

	
	public function prepareValue()
	{
		return null;
	}

	public function checkFilterValue()
	{
		return null;
	}

	public function displayAdminPanelCell()
	{
		return '';
	}

	public function displayAdminFilter(mixed $data)
	{
		return '';
	}

	public function displayFilter()
	{
		return '';
	}

	public function getMessageValue()
	{
		return '';
	}
}