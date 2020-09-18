<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\ORM\Model\IblockElement;

use Bitrix\Main\Application;
use Bitrix\Main;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Orm\Fields;
use Olympia\Bitrix\ORM\Model\IBlockElementTable;

abstract class SingleProperty extends DataManager
{
	protected static $iblockId;

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_iblock_element_prop_s' . static::$iblockId;
	}

	/**
	 * @return array
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		$metadata = IBlockElementTable::getMetadata(static::$iblockId);

		$map = [
			new Fields\IntegerField('IBLOCK_ELEMENT_ID', [
				'primary' => true
			])
		];

		foreach ($metadata['props'] as $prop)
		{
			$prop['IBLOCK_ID'] = static::$iblockId;

			if ($prop['MULTIPLE'] == 'Y')
			{
				$map[] = (new Fields\ArrayField(
					$prop['CODE'],
					['column_name' => 'PROPERTY_' . $prop['ID']]
				))
				->configureSerializationPhp()
				->addFetchDataModifier(function ($value, /** @noinspection PhpUnusedParameterInspection */$query, $data) use ($prop)
				{
					if (is_array($value) && isset($value['VALUE']))
						return $value;

					$connection = Application::getConnection();

					$rs = $connection->query("SELECT ID, VALUE, DESCRIPTION
						FROM b_iblock_element_prop_m".$prop['IBLOCK_ID']."
						WHERE
							IBLOCK_ELEMENT_ID = ".(int) $data['ID']."
							AND IBLOCK_PROPERTY_ID = ".(int) $prop['ID']."
						ORDER BY ID"
					);

					$save = [
						'ID' => [],
						'VALUE' => [],
						'DESCRIPTION' => [],
					];

					while ($ar = $rs->Fetch())
					{
						$save['ID'][] = (int) $ar['ID'];
						$save['VALUE'][] = trim($ar['VALUE']);
						$save['DESCRIPTION'][] = trim($ar['DESCRIPTION']);
					}

					$update = $connection->getSqlHelper()->prepareUpdate(
						'b_iblock_element_prop_s'.$prop['IBLOCK_ID'],
						['PROPERTY_'.(int) $prop['ID'] => serialize($save)]
					);

					$connection->query('UPDATE b_iblock_element_prop_s'.$prop['IBLOCK_ID'].' SET '.$update[0].' WHERE IBLOCK_ELEMENT_ID = '.(int) $data['ID'], $update[1]);

					return $save;
				});
			}
			else
			{
				switch ($prop['PROPERTY_TYPE'])
				{
					case 'N':

						$map[] = new Fields\FloatField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						break;

					case 'L':
					case 'E':
					case 'G':

						$map[] = new Fields\IntegerField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						break;

					case 'S':
					default:

						$field = new Fields\StringField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						if (mb_strtoupper($prop['USER_TYPE']) == 'HTML')
						{
							$field->addFetchDataModifier(function ($value) {
								return unserialize($value);
							});
						}

						$map[] = $field;

						break;
				}
			}
		}

		return $map;
	}

	/**
	 * @param $iblockId
	 * @param array $parameters
	 * @return \Bitrix\Main\Entity\Base
	 * @throws Main\ArgumentException
	 */
	public static function createEntity($iblockId, $parameters = array())
	{
		$iblockId = (int) $iblockId;

		if ($iblockId <= 0)
			throw new Main\ArgumentException('$iblockId should be integer');

		$className = 'OlympiaOrmIblockElementProperty' . $iblockId . 'Table';

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
				class '.$className.' extends '.__NAMESPACE__.'\SingleProperty 
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