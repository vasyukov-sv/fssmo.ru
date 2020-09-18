<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class BreakTypesTable extends External
{
	public $id;
	public $BreakName;
	public $Short;

	public static function getTableName()
	{
		return 'BreakTypes';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('BreakName'),
			new Fields\StringField('Short'),
		];
	}
}