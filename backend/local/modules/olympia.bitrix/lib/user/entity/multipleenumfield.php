<?php

namespace Olympia\Bitrix\User\Entity;

use Bitrix\Main;

class MultipleEnumFieldTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_utm_user';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'FIELD_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'VALUE' => array(
				'data_type' => 'text',
			),
			'VALUE_INT' => array(
				'data_type' => 'integer',
			),
			'VALUE_DOUBLE' => array(
				'data_type' => 'float',
			),
			'VALUE_DATE' => array(
				'data_type' => 'datetime',
			),
		);
	}
}