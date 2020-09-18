<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\ORM\Model\IblockElement;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;

abstract class MultipleProperty extends DataManager
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
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new Fields\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('IBLOCK_ELEMENT_ID', [
				'required' => true
			]),
			new Fields\IntegerField('IBLOCK_PROPERTY_ID', [
				'required' => true
			]),
			new Fields\StringField('VALUE', [
				'required' => true
			]),
			new Fields\IntegerField('VALUE_ENUM', [
				'required' => true
			]),
			new Fields\FloatField('VALUE_NUM', [
				'required' => true
			]),
			(new Fields\Relations\Reference(
				'PROPERTY',
				PropertyTable::class,
				Join::on('this.IBLOCK_PROPERTY_ID', 'ref.ID')
			))->configureJoinType('inner'),
			new Fields\ExpressionField(
				'CODE',
				'%s',
				'PROPERTY.CODE'
			)
		];
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

		$className = 'OlympiaOrmIblockElementMultipleProperty'.$iblockId.'Table';

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
				class '.$className.' extends '.__NAMESPACE__.'\MultipleProperty 
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