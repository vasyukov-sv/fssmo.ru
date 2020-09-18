<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class CategoriesTable extends External
{
	public $id;
	public $CategoryName;
	public $SortOrder;

	public static function getTableName()
	{
		return 'Categories';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('CategoryName'),
			new Fields\StringField('SortOrder'),
		];
	}
}