<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Iblock\Property;

use Bitrix\Main;

class ElementPropertyTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_element_property';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'IBLOCK_PROPERTY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'VALUE' => array(
				'data_type' => 'text',
				'required' => true,
			),
			'VALUE_TYPE' => array(
				'data_type' => 'enum',
				'values' => array('text', 'html'),
			),
			'VALUE_ENUM' => array(
				'data_type' => 'integer',
			),
			'VALUE_NUM' => array(
				'data_type' => 'float',
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Bitrix\Iblock\IblockElement',
				'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
			),
			'IBLOCK_PROPERTY' => array(
				'data_type' => 'Bitrix\Iblock\IblockProperty',
				'reference' => array('=this.IBLOCK_PROPERTY_ID' => 'ref.ID'),
			),
		);
	}

	public static function validateDescription()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}