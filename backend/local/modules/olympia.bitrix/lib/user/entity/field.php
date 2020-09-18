<?php

namespace Olympia\Bitrix\User\Entity;

use Bitrix\Main;

class FieldTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_user_field';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityId'),
			),
			'FIELD_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateFieldName'),
			),
			'USER_TYPE_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateUserTypeId'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'MULTIPLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'MANDATORY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SHOW_FILTER' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SHOW_IN_LIST' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'EDIT_IN_LIST' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_SEARCHABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SETTINGS' => array(
				'data_type' => 'text',
			),
		);
	}

	public static function validateEntityId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}

	public static function validateFieldName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}

	public static function validateUserTypeId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}