<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class SeriesTable extends External
{
	public $id;
	public $CompId;
	public $PlaceId;
	public $DisciplineId;
	public $TargetsCount;
	public $Number;
	public $CategoryId;
	public $Day;

	public static function getTableName()
	{
		return 'Series';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('CompId'),
			new Fields\IntegerField('PlaceId'),
			new Fields\IntegerField('DisciplineId'),
			new Fields\IntegerField('TargetsCount'),
			new Fields\IntegerField('Number'),
			new Fields\IntegerField('CategoryId'),
			new Fields\IntegerField('Day'),
		];
	}
}