<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class DigitsTable extends External
{
	public $id;
	public $Digit;
	public $Weight;

	public static function getTableName()
	{
		return 'Digits';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('Digit'),
			new Fields\IntegerField('Weight'),
		];
	}
}