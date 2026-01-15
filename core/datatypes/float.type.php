<?php
/**
 * Float datatype class. Most properties are inherited from Int datatype.
 */
 class FloatModelElement extends IntModelElement
{
	protected $format = "/^-?(0\.|[1-9]\d*\.|[1-9])\d*$/";
	
	protected $decimal;

	public function prepareValue()
	{
		$this -> value = floatval($this -> value);
		
		return $this -> value;
	}
} 
