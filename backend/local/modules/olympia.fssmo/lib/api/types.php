<?php

namespace Olympia\Fssmo\Api;

class Types
{
	/** @var self */
	protected static $instance = null;

	public static function getInstance()
	{
		if (!isset(static::$instance))
			static::$instance = new static();

		return static::$instance;
	}

	private $types = [];

	public function get ($name)
	{
		if (!isset($this->types[$name]))
		{
			$class = __NAMESPACE__.'\\Types\\';

			if (strpos($name, 'Enum') !== false)
				$class .= 'Enum\\';
			elseif (strpos($name, 'Input') !== false)
				$class .= 'Input\\';
			elseif (strpos($name, 'Output') !== false)
				$class .= 'Output\\';

			$class .= $name;

			if (!class_exists($class))
			{
				if (!method_exists($this, $name))
					throw new \Exception('Type `'.$name.'` not found in registry');

				$this->types[$name] = $this->{$name}();
			}
			else
				$this->types[$name] = new $class();
		}

		return $this->types[$name];
	}
}