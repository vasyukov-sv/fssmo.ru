<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class DisciplinesTable extends External
{
	public $id;
	public $Discipline;

	public static function getTableName()
	{
		return 'Disciplines';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('Discipline'),
		];
	}
}