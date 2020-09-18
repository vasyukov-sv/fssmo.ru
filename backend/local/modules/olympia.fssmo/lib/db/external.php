<?php

namespace Olympia\Fssmo\Db;

use Olympia\Bitrix\Orm\Model;

abstract class External extends Model
{
	public static function getConnectionName()
	{
		return 'mssql';
	}
}