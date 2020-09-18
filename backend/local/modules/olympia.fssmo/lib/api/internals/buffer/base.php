<?php

namespace Olympia\Fssmo\Api\Internals\Buffer;

abstract class Base
{
	static $ids = [];
	static $items = false;

	public static function add ($id)
	{
		if (is_array($id))
			static::$ids = array_merge(static::$ids, array_map('intval', $id));
		else
			static::$ids[] = (int) $id;
	}

	abstract public static function load ();

	public static function get ($id)
	{
		if (static::$items === false)
			static::load();

		return isset(static::$items[$id]) ? static::$items[$id] : null;
	}
}