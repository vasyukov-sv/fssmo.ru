<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class ApplicationsTable extends External
{
	public $ApplicationId;
	public $ApplicationName;
	public $LoweredApplicationName;
	public $Description;

	public static function getTableName()
	{
		return 'aspnet_Applications';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('ApplicationId', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('ApplicationName'),
			new Fields\StringField('LoweredApplicationName'),
			new Fields\StringField('Description'),
		];
	}
}