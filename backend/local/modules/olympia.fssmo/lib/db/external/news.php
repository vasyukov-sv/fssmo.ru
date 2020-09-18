<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class NewsTable extends External
{
	public $id;
	public $Title;
	public $Body;
	public $Teaser;
	/** @var DateTime */
	public $Date;

	public static function getTableName()
	{
		return 'News';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('Title'),
			new Fields\StringField('Body'),
			new Fields\StringField('Teaser'),
			new Fields\DateField('Date'),
		];
	}
}