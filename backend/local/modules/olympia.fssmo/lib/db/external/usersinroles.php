<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class UsersInRolesTable extends External
{
	public $UserId;
	public $RoleId;

	public static function getTableName()
	{
		return 'aspnet_UsersInRoles';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('UserId', [
				'primary' => true,
			]),
			new Fields\StringField('RoleId', [
				'required' => true
			]),
		];
	}
}