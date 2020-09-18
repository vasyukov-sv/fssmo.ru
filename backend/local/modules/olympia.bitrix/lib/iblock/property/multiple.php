<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Iblock\Property;

use Bitrix\Main\Entity;
use Bitrix\Main;

abstract class Multiple extends Entity\DataManager
{
	protected static $iblockId;

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_iblock_element_prop_m'.static::$iblockId;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			),
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'IBLOCK_PROPERTY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true
			),
			'VALUE_ENUM' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'VALUE_NUM' => array(
				'data_type' => 'float',
				'required' => true
			),
			new Entity\ReferenceField(
				'PROPERTY',
				'\Bitrix\Iblock\Property',
				array('this.IBLOCK_PROPERTY_ID' => 'ref.ID')
			),
			new Entity\ExpressionField(
				'CODE',
				'%s',
				'PROPERTY.CODE'
			)
		);
	}

	/**
	 * @param $iblockId
	 * @return \Bitrix\Main\Entity\Base
	 * @throws Main\ArgumentException
	 */
	public static function createEntity($iblockId)
	{
		$iblockId = (int) $iblockId;

		if ($iblockId <= 0)
			throw new Main\ArgumentException('$iblockId should be integer');

		$className = 'OlympiaIblockElementMultipleProperty'.$iblockId.'Table';

		if (!preg_match('/^[a-z0-9_]+$/i', $className))
			throw new Main\ArgumentException(sprintf('Invalid entity classname `%s`.', $className));

		$namespace = '';
		$fullClassName = $className;

		if (!empty($parameters['namespace']) && $parameters['namespace'] !== '\\')
		{
			$namespace = $parameters['namespace'];

			if (!preg_match('/^[a-z0-9\\\\]+$/i', $namespace))
				throw new Main\ArgumentException(sprintf('Invalid namespace name `%s`', $namespace));

			$fullClassName = '\\' . $namespace . '\\' . $fullClassName;
		}

		if (!class_exists($fullClassName))
		{
			eval('namespace '.$namespace.'
			{
				class '.$className.' extends '.__NAMESPACE__.'\Multiple 
				{
					static protected $iblockId = '.$iblockId.';
					public static function getFilePath(){return __FILE__;}
				}
			}');
		}

		/** @var \Bitrix\Main\Entity\DataManager $fullClassName */
		/** @var \Bitrix\Main\Entity\Base $entity */
		$entity = $fullClassName::getEntity();

		return $entity;
	}
}