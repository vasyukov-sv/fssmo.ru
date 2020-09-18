<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class SiteDisciplinesTable extends External
{
	public $id;
	public $DisciplineName;

	public static function getTableName()
	{
		return 'SiteDisciplines';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('DisciplineName'),
		];
	}
}