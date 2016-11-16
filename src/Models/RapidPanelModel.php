<?php

namespace Rapide\RapidPanel\Models;

class RapidPanelModel
{
	public $id;

	public function __construct()
	{
		$this->id = 0;
	}

	public function setAttributes($params)
	{
		if(is_array($params) == true || is_object($params) == true)
		{
			foreach($params as $name => $value)
			{
				$this->$name = htmlentities($value);
			}
		}
	}
}