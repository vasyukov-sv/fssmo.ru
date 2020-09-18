<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class RolesTable extends External
{
	public $RoleId;
	public $ApplicationId;
	public $RoleName;
	public $LoweredRoleName;
	public $Description;

	public static function getTableName()
	{
		return 'aspnet_Roles';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('RoleId', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('ApplicationId'),
			new Fields\StringField('RoleName'),
			new Fields\StringField('LoweredRoleName'),
			new Fields\StringField('Description'),
		];
	}
}