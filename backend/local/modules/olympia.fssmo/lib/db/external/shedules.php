<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class ShedulesTable extends External
{
	public $id;
	public $CompId;
	public $SheduleXML;
	public $SheduleJSON;

	public static function getTableName()
	{
		return 'Shedules';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('CompId'),
			new Fields\TextField('SheduleXML'),
			(new Fields\ArrayField('SheduleJSON'))->configureSerializationJson(),
		];
	}
}