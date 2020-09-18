<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class PlacesTable extends External
{
	public $id;
	public $PlaceName;
	public $CompId;

	public static function getTableName()
	{
		return 'Places';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('PlaceName'),
			new Fields\IntegerField('CompId'),
		];
	}
}