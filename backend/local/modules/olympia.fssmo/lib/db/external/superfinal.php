<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class SuperFinalTable extends External
{
	public $id;
	public $Created;
	public $DataTableXML;

	public static function getTableName()
	{
		return 'SuperFinal';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\DatetimeField('Created'),
			new Fields\TextField('DataTableXML'),
		];
	}
}