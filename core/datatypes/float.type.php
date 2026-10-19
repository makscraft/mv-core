<?php
/**
 * Float datatype class. Most properties are inherited from Int datatype.
 */
 class FloatModelElement extends IntModelElement
{
	protected string $format = '/^-?(0\.|[1-9]\d*\.|[1-9])\d*$/';
	
	protected ?string $decimal = null;

	public function prepareValue()
	{
		$this -> value = floatval($this -> value);
		
		return $this -> value;
	}

	public function formatValue(mixed $value)
	{
		$decimals = $this -> decimal ? intval(explode(',', $this -> decimal)[1]) : 2;
		
		return I18n::formatFloatNumber($value, $decimals);
	}	
}