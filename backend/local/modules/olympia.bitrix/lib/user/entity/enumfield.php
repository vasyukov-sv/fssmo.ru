<?php

namespace Olympia\Bitrix\User\Entity;

use Bitrix\Main;

class EnumFieldTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_user_field_enum';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_FIELD_ID' => array(
				'data_type' => 'integer',
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateValue'),
			),
			'DEF' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			new Main\Entity\ReferenceField(
				'USER_FIELD',
				'\Olympia\Bitrix\User\Entity\Field',
				array('=this.USER_FIELD_ID' => 'ref.ID')
			),
		);
	}

	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}