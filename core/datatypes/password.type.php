<?php
/**
 * Password datatype class. Most properties are inherited from Char datatype.
 */
class PasswordModelElement extends CharModelElement
{
	protected $min_length = 6;
	
	protected $letters_required = false;
	
	protected $digits_required = false;

	protected $salt = 'APP_TOKEN';
	
	public function prepareValue()
	{
		parent::prepareValue();
		
		if(Registry::getInitialVersion() < 2.2)
			$this -> value = md5($this -> value);
		else
			$this -> value = $this -> hashPasswordValue();
		
		return $this -> value;
	}

	public function validate()
	{
		$this -> unique = false;
				
		parent::validate();
				
		if(!$this -> error && $this -> value)
			if($this -> letters_required && !preg_match("/\D/iu", $this -> value))
				$this -> error = "{error-letters-required}";
			else if($this -> digits_required && !preg_match("/\d/", $this -> value))
				$this -> error = "{error-digits-required}";
		
		return $this;
	}
	
    public function displayHtml()
    {
        return str_replace("type=\"text\"", "type=\"password\"",  parent::displayHtml());
    }

	public function getPasswordSalt(): string
	{
		if(Registry::getInitialVersion() < 3.2 && $this -> salt === '')
			return '';

		if($this -> salt === null || $this -> salt === '')
			return Registry::get('SecretCode');
		else if($salt = Registry::get($this -> salt))
			return $salt;
		else
			return (string) $this -> salt;
	}

	public function hashPasswordValue(string $value = ''): string
	{
		if(Registry::getInitialVersion() < 3.2 && $this -> salt === '')
			return $this -> value;

		$salt = $this -> getPasswordSalt();
		$value = trim($value) === '' ? $this -> value : $value;

		if(strlen($value) > 40 && preg_match('/^\$2y\$\d+\$/', $value))
			return $value;

		return $value ? Service::makeHash(trim($value).$salt, 12) : '';
	}

	public function comparePasswordHash(string $password, string $hash): bool
	{
		$salt = $this -> getPasswordSalt();
		
		return Service::checkHash($password.$salt, $hash);
	}
}