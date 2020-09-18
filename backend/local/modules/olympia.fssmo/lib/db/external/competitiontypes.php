<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class CompetitionTypesTable extends External
{
	public $id;
	public $TypeName;

	public static function getTableName()
	{
		return 'CompetitionTypes';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('TypeName'),
		];
	}
}