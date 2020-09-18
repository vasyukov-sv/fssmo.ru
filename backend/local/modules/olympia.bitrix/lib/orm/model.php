<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\Orm;

use Bitrix\Main\ORM;
use Bitrix\Main\Type\Dictionary;

/**
 * @method static Result getList(array $parameters)
 */
class Model extends ORM\Data\DataManager implements \ArrayAccess, \IteratorAggregate
{
	private $_values = [];

	public static function query ()
	{
		return new Query(static::getEntity());
	}

	public static function cloneResultMap (array $result = [])
	{
		$instance = new static();

		foreach ($result as $key => $value)
		{
			if (property_exists($instance, $key))
				$instance->{$key} = $value;
			else
				$instance->_values[$key] = $value;
		}

		if (method_exists($instance, 'afterFetch'))
			$instance->afterFetch();

		return $instance;
	}

	public static function getRowById ($id)
	{
		$result = static::getByPrimary($id);
		$row = $result->fetch();

		return $row ? $row : null;
	}

	public static function getRow (array $parameters)
	{
		$parameters['limit'] = 1;
		$result = static::getList($parameters);
		$row = $result->fetch();

		return $row ? $row : null;
	}

	public function  __isset ($name)
	{
		return isset($this->_values[$name]);
	}

	public function __get ($name)
	{
		if (!array_key_exists($name, $this->_values))
			throw new \InvalidArgumentException('unknown field "'.$name.'" in class '.get_called_class().'');

		return $this->_values[$name];
	}

	public function __set ($name, $value)
	{
		if (!array_key_exists($name, $this->_values))
			throw new \InvalidArgumentException('unknown field "'.$name.'" in class '.get_called_class().'');

		$this->_values[$name] = $value;
	}

	public function offsetExists ($offset)
	{
		return property_exists($this, $offset) || isset($this->_values[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null
	 */
	public function offsetGet ($offset)
	{
		if (property_exists($this, $offset))
			return $this->{$offset};

		return isset($this->_values[$offset]) ? $this->_values[$offset] : null;
	}

	public function offsetSet ($offset, $value)
	{
		if (is_null($offset))
			throw new \InvalidArgumentException('unknown field in class '.get_called_class().'');

		if (property_exists($this, $offset))
			$this->{$offset} = $value;
		else
			$this->_values[$offset] = $value;
	}

	public function offsetUnset ($offset)
	{
		if (property_exists($this, $offset))
			$this->{$offset} = null;
		else
			unset($this->_values[$offset]);
	}

	public function getIterator ()
	{
		return new Dictionary($this->_values);
	}

	public function toArray ()
	{
		return call_user_func('get_object_vars', $this);
	}
}