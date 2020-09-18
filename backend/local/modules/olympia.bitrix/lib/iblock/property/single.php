<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Iblock\Property;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main;
use Olympia\Bitrix\Iblock\Element;

abstract class Single extends Entity\DataManager
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
	 * @throws Main\ArgumentException
	 */
	public static function getMap()
	{
		$metadata = Element::getMetadata(static::$iblockId);

		$map = array(
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			)
		);

		foreach ($metadata['props'] as $prop)
		{
			$prop['IBLOCK_ID'] = static::$iblockId;

			if ($prop['MULTIPLE'] == 'Y')
			{
				$map[$prop['CODE']] = new Entity\StringField(
					$prop['CODE'],
					['column_name' => 'PROPERTY_' . $prop['ID']]
				);

				$map[$prop['CODE']]->addFetchDataModifier(function ($value, /** @noinspection PhpUnusedParameterInspection */$query, $data) use ($prop)
				{
					if ($value != '')
						return unserialize($value);

					$connection = Application::getConnection();

					$rs = $connection->query("SELECT ID, VALUE, DESCRIPTION
						FROM b_iblock_element_prop_m".$prop['IBLOCK_ID']."
						WHERE
							IBLOCK_ELEMENT_ID = ".(int) $data['ID_']."
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

					$connection->query('UPDATE b_iblock_element_prop_s'.$prop['IBLOCK_ID'].' SET '.$update[0].' WHERE IBLOCK_ELEMENT_ID = '.(int) $data['ID_'], $update[1]);

					return $save;
				});
			}
			else
			{
				switch ($prop['PROPERTY_TYPE'])
				{
					case 'N':

						$map[$prop['CODE']] = new Entity\FloatField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						break;

					case 'L':
					case 'E':
					case 'G':

						$map[$prop['CODE']] = new Entity\IntegerField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						break;

					case 'S':
					default:

						$map[$prop['CODE']] = new Entity\StringField(
							$prop['CODE'],
							['column_name' => 'PROPERTY_' . $prop['ID']]
						);

						if (mb_strtoupper($prop['USER_TYPE']) == 'HTML')
						{
							$map[$prop['CODE']]->addFetchDataModifier(function ($value) {
								return unserialize($value);
							});
						}

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

		$className = 'OlympiaIblockElementProperty' . $iblockId . 'Table';

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
				class '.$className.' extends '.__NAMESPACE__.'\Single 
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