<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Iblock;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\FileTable;
use Olympia\Bitrix\Iblock\Property\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity;
use Olympia\Bitrix\Iblock\Property\Multiple;
use Olympia\Bitrix\Iblock\Property\Single as SingleProperty;

Main\Loader::includeModule('iblock');

class Element extends ElementTable
{
	/** @var int */
	static protected $iblockId;
	/** @var array */
	static protected $_metadata;
	/** @var array */
	static private $propFields = [];
	static private $deltFields = [];

	public function getIblockId ()
	{
		return 0;
	}

	public static function getMap ()
	{
		$meta = static::getMetadata(static::$iblockId);

		$map = parent::getMap();

		$map['DETAIL_PAGE_URL'] = new Entity\ExpressionField(
			'DETAIL_PAGE_URL',
			"'".$meta['iblock']['DETAIL_PAGE_URL']."'"
		);

		$map['DETAIL_PICTURE_FILE'] = new Entity\ReferenceField(
			'DETAIL_PICTURE_FILE',
			FileTable::getEntity(),
			['=this.DETAIL_PICTURE' => 'ref.ID']
		);
		$map['PREVIEW_PICTURE_FILE'] = new Entity\ReferenceField(
			'PREVIEW_PICTURE_FILE',
			FileTable::getEntity(),
			['=this.PREVIEW_PICTURE' => 'ref.ID']
		);

		if ($meta['iblock']['VERSION'] == 2)
		{
			$propertyEntity = SingleProperty::createEntity($meta['iblock']['ID']);

			$map['PROPERTY'] = new Entity\ReferenceField(
				'PROPERTY', $propertyEntity->getDataClass(),
				array('ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
				array('join_type' => 'INNER')
			);

			foreach ($meta['props'] as $prop)
			{
				if ($prop['MULTIPLE'] == 'Y')
				{
					$entity = Multiple::createEntity($meta['iblock']['ID']);

					$map['PROPERTY_MULTIPLE_'.$prop['CODE']] = new Main\Entity\ReferenceField(
						'PROPERTY_MULTIPLE_'.$prop['CODE'],
						$entity->getDataClass(),
						array(
							'=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
							'ref.IBLOCK_PROPERTY_ID' => array('?i', $prop['ID'])
						),
						array('join_type' => 'LEFT')
					);
				}
			}
		}
		else
		{
			foreach ($meta['props'] as $prop)
			{
				$map['PROPERTY_FILTER_'.$prop['CODE']] = new Entity\ReferenceField(
					'PROPERTY_FILTER_'.$prop['CODE'], ElementPropertyTable::getEntity(),
					['ref.IBLOCK_ELEMENT_ID' => 'this.ID', 'ref.IBLOCK_PROPERTY_ID' => ['?i', $prop['ID']]],
					['join_type' => 'INNER']
				);
			}
		}

		$map['CATALOG'] = new Entity\ReferenceField(
			'CATALOG', '\Bitrix\Catalog\ProductTable',
			['ref.ID' => 'this.ID'],
			['join_type' => 'LEFT']
		);

		$map['PRICE'] = new Entity\ReferenceField(
			'PRICE', '\Bitrix\Catalog\PriceTable',
			['ref.PRODUCT_ID' => 'this.ID'],
			['join_type' => 'LEFT']
		);

		return $map;
	}

	/**
	 * @param int|null $iblockId
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getMetadata($iblockId = null)
	{
		if (empty($iblockId))
			return [];

		if (isset(static::$_metadata[$iblockId]))
			return static::$_metadata[$iblockId];

		$result = [];

		$cache = Cache::createInstance();

		if ($cache->initCache(3600, 'ELEMENT_TABLE|'.$iblockId, '/' . $iblockId))
			$result = $cache->getVars();
		else
		{
			$result['iblock'] = IblockTable::getRowById($iblockId);
			$result['props'] = [];

			$rs = PropertyTable::getList(array(
				'select' => array('ID', 'CODE', 'SORT', 'MULTIPLE', 'ACTIVE', 'PROPERTY_TYPE', 'USER_TYPE'),
				'filter' => array('IBLOCK_ID' => $iblockId)
			));

			while ($arProp = $rs->fetch())
				$result['props'][$arProp['CODE']] = $arProp;

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		static::$_metadata[$iblockId] = $result;

		return $result;
	}

	/**
	 * @param $parameters
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList(array $parameters = [])
	{
		$parameters['filter']['=IBLOCK_ID'] = static::$iblockId;

		static::$propFields = [];
		static::$deltFields = [];

		$select = [];
		$filter = [];

		foreach ($parameters['filter'] as $key => $val)
		{
			if (mb_strpos($key, 'PROPERTY') !== false)
			{
				$key = str_replace('PROPERTY_', 'PROPERTY.', $key);

				$code = mb_substr($key, mb_strpos($key, 'PROPERTY') + 9);
				$mods = mb_substr($key, 0, mb_strpos($key, 'PROPERTY'));

				if (static::$_metadata[static::$iblockId]['iblock']['VERSION'] == 1)
				{
					if ($mods == '' || $mods == '=')
						$filter[$mods.'PROPERTY_FILTER_'.$code.'.VALUE'] = $val;
					else
						$filter[$mods.'PROPERTY_FILTER_'.$code.'.VALUE_NUM'] = $val;
				}
				else
				{
					if (static::$_metadata[static::$iblockId]['props'][$code]['MULTIPLE'] == 'Y')
						$filter[$mods.'PROPERTY_MULTIPLE_'.$code.'.VALUE'] = $val;
					else
					$filter[$key] = $val;
				}

				continue;
			}

			$filter[$key] = $val;
		}

		foreach ($parameters['select'] as $alias => $field)
		{
			$field = str_replace('.*', '', $field);
			$field = str_replace('PROPERTY_', 'PROPERTY.', $field);

			if (mb_strpos($field, 'PRICE_') !== false)
			{
				$s = mb_strpos($field, 'PRICE_');

				$priceId = (int) substr($field, $s - 1);

				if ($priceId)
				{
					$field = substr($field, $s - 7, $s - 2);
					$filter['PRICE.CATALOG_GROUP_ID'] = $priceId;
				}
			}
			elseif (mb_strpos($field, 'CATALOG_GROUP_') !== false)
			{
				$s = mb_strpos($field, 'CATALOG_GROUP_');

				$priceId = (int) substr($field, $s - 1);

				$prefix = substr($field, 0, $s - 16);

				$select[$prefix.'CATALOG_PRICE_ID_'.$priceId] = $prefix.'PRICE.ID';
				$select[$prefix.'CATALOG_PRICE_'.$priceId] = $prefix.'PRICE.PRICE';
				$select[$prefix.'CATALOG_CURRENCY_'.$priceId] = $prefix.'PRICE.CURRENCY';
				$select[$prefix.'CATALOG_GROUP_ID_'.$priceId] = $prefix.'PRICE.CATALOG_GROUP_ID';
				$select[$prefix.'CATALOG_PRICE_ID_'.$priceId] = $prefix.'PRICE.ID';

				$filter['PRICE.CATALOG_GROUP_ID'] = $priceId;

				continue;
			}
			elseif (mb_strpos($field, 'PROPERTY.') !== false)
			{
				if (is_numeric($alias))
					$alias = 'PROPERTY_'.mb_substr($field, mb_strpos($field, 'PROPERTY') + 9).'_VALUE';

				$code = mb_substr($field, mb_strpos($field, 'PROPERTY') + 9);

				static::$propFields[$code] = [
					'value' => $field,
					'alias' => !is_numeric($alias) ? $alias : ''
				];

				if (static::$_metadata[static::$iblockId]['iblock']['VERSION'] == 1)
					continue;
			}
			elseif (mb_strpos($field, 'DETAIL_PAGE_URL') !== false)
			{
				$s = mb_strpos($field, 'DETAIL_PAGE_URL');
				$prefix = substr($field, 0, $s);

				foreach (['CODE', 'IBLOCK_ID'] as $f)
				{
					$a = str_replace('.', '_', $prefix.$f);

					if (!in_array($a, $parameters['select']))
					{
						static::$deltFields[] = $a;
						$select[$a] = $prefix.$f;
					}
				}

				if (!is_numeric($alias))
				{
					$e = explode('.', $field);

					$alias = $alias.'#'.$e[0].'#';
				}
			}

			if (is_numeric($alias))
				$alias = str_replace('.', '_', $field).'_';

			$select[$alias] = $field;
		}

		$parameters['select'] = $select;
		$parameters['filter'] = $filter;

		if (!isset($parameters['select']))
			$parameters['select'] = ['*'];
		elseif (!in_array('ID', $parameters['select']) && !isset($parameters['group']))
			$parameters['select'][] = 'ID';

		$rs = parent::getList($parameters);

		if (in_array('DISTINCT_', $parameters['select']))
			$rs->addReplacedAliases(['DISTINCT' => 'ID']);

		$rs->addFetchDataModifier([static::class, 'fetchDataModifier']);

		// Для инфоблоков 1 версии извлечение свойств вторым запросом
		if (count(static::$propFields))
		{
			foreach (static::$propFields as $code => $field)
			{
				if (isset(static::$_metadata[static::$iblockId]['props'][$code]))
					static::$propFields[$code]['property'] = static::$_metadata[static::$iblockId]['props'][$code]['ID'];
				else
					unset(static::$propFields[$code]);
			}
		}

		return $rs;
	}

	public static function getCount(array $filter = array(), array $cache = array())
	{
		$query = static::query();

		$query->addSelect(new ExpressionField('CNT', 'COUNT(1)'));

		foreach ($filter as $key => $val)
		{
			if (mb_strpos($key, 'PROPERTY') !== false)
			{
				$code = mb_substr($key, mb_strpos($key, 'PROPERTY') + 9);
				$mods = mb_substr($key, 0, mb_strpos($key, 'PROPERTY'));

				if (static::$_metadata[static::$iblockId]['iblock']['VERSION'] == 1)
				{
					if ($mods == '' || $mods == '=')
						$query->addFilter($mods.'PROPERTY_FILTER_'.$code.'.VALUE', $val);
					else
						$query->addFilter($mods.'PROPERTY_FILTER_'.$code.'.VALUE_NUM', $val);
				}
				else
				{
					if (mb_strpos($key, 'PROPERTY_') !== false)
						$key = str_replace('PROPERTY_', 'PROPERTY.', $key);

					if (static::$_metadata[static::$iblockId]['props'][$code]['MULTIPLE'] == 'Y')
						$query->addFilter('PROPERTY_MULTIPLE_'.$code.'.VALUE', $val);
					else
						$query->addFilter($key, $val);
				}
			}
			else
				$query->addFilter($key, $val);
		}

		$result = $query->exec()->fetch();

		return $result['CNT'];
	}

	public static function fetchDataModifier ($entry)
	{
		$result = [];

		foreach ($entry as $key => $value)
		{
			$key = trim($key, '_');

			if (mb_strpos($key, '#') !== false || mb_strpos($key, 'DETAIL_PAGE_URL') !== false)
			{
				preg_match('/#(.*?)#/', $key, $parent);

				$r = [];

				if (isset($parent[1]))
					$prefix = $parent[1];
				else
					$prefix = substr($key, 0, mb_strpos($key, 'DETAIL_PAGE_URL'));

				foreach ($entry as $k => $v)
				{
					$k = trim($k, '_');

					if (($prefix == '' || strpos($k, $prefix) !== false) && $k != $key)
						$r[trim(str_replace($prefix, '', $k), '_')] = $v;
				}

				$key = preg_replace('/#(.*?)#/', '', $key);

				$value = \CIBlock::ReplaceDetailUrl($value, $r, true, 'E');
			}

			$result[$key] = $value;
		}

		if (static::$_metadata[static::$iblockId]['iblock']['VERSION'] == 1)
		{
			if (count(static::$propFields))
			{
				$pId = [];

				foreach (static::$propFields as $code => $f)
					$pId[$f['property']] = $code;

				$properties = ElementPropertyTable::getList([
					'select' => ['ID', 'PROPERTY_ID' => 'IBLOCK_PROPERTY_ID', 'VALUE', 'DESCRIPTION'],
					'filter' => ['IBLOCK_ELEMENT_ID' => $result['ID'], 'IBLOCK_PROPERTY_ID' => array_keys($pId)]
				]);

				while ($property = $properties->fetch())
				{
					if (static::$propFields[$pId[$property['PROPERTY_ID']]]['alias'] != '')
						$alias = static::$propFields[$pId[$property['PROPERTY_ID']]]['alias'];
					else
						$alias = 'PROPERTY_'.$pId[$property['PROPERTY_ID']];

					$suffix = '';

					if (static::$propFields[$pId[$property['PROPERTY_ID']]]['alias'] == '')
						$suffix = '_VALUE';

					if (isset($result[$alias.$suffix]))
					{
						if (!is_array($result[$alias.$suffix]))
						{
							$result[$alias.$suffix] = [$result[$alias.$suffix]];
							$result[$alias.'_DESCRIPTION'] = [$result[$alias.'_DESCRIPTION']];
							$result[$alias.'_ENUM_ID'] = [$result[$alias.'_ENUM_ID']];
						}

						$result[$alias.$suffix][] = $property['VALUE'];
						$result[$alias.'_DESCRIPTION'][] = $property['DESCRIPTION'];
						$result[$alias.'_ENUM_ID'][] = $property['ID'];
					}
					else
					{
						$result[$alias.$suffix] = $property['VALUE'];
						$result[$alias.'_DESCRIPTION'] = $property['DESCRIPTION'];
						$result[$alias.'_ENUM_ID'] = $property['ID'];
					}
				}
			}
		}
		elseif (static::$_metadata[static::$iblockId]['iblock']['VERSION'] == 2)
		{
			foreach (static::$_metadata[static::$iblockId]['props'] as $key => $data)
			{
				if (isset(static::$propFields[$key]))
					$alias = trim(static::$propFields[$key]['alias']);
				else
					$alias = 'PROPERTY_'.$key.'_VALUE';

				if ($data['MULTIPLE'] == 'Y')
				{
					if (array_key_exists($alias, $result))
					{
						if (isset($result[$alias]['VALUE']))
						{
							if (isset(static::$propFields[$key]))
							{
								$result[$alias.'_DESCRIPTION'] = $result[$alias]['DESCRIPTION'];
								$result[$alias.'_ENUM_ID'] = $result[$alias]['ID'];
								$result[$alias] = $result[$alias]['VALUE'];
							}
							else
							{
								$result['PROPERTY_'.$key.'_DESCRIPTION'] = $result[$alias]['DESCRIPTION'];
								$result['PROPERTY_'.$key.'_ENUM_ID'] = $result[$alias]['ID'];
								$result['PROPERTY_'.$key.'_VALUE'] = $result[$alias]['VALUE'];
							}
						}
					}
				}
			}
		}

		foreach (static::$deltFields as $key)
			unset($result[$key]);

		return $result;
	}

	public static function add(array $data)
	{
		throw new \Exception('Method not supported');
	}

	public static function update($primary, array $data)
	{
		throw new \Exception('Method not supported');
	}

	public static function delete($primary)
	{
		throw new \Exception('Method not supported');
	}

	/**
	 * @param int $iblockId
	 * @param array $parameters
	 * @return \Bitrix\Main\Entity\Base
	 * @throws Main\ArgumentException
	 */
	public static function createEntity ($iblockId, $parameters = [])
	{
		$iblockId = (int) $iblockId;

		if ($iblockId <= 0)
			throw new Main\ArgumentException('$iblockId should be integer');

		$className = 'OlympiaIblockElement'.$iblockId.'Table';

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
				class '.$className.' extends '.__NAMESPACE__.'\Element 
				{
					static protected $iblockId = '.$iblockId.';
					static protected $propFields = [];
					static protected $deltFields = [];
					public static function getFilePath(){return __FILE__;}
				}
			}');
		}

		/** @var \Bitrix\Main\Entity\DataManager $fullClassName */
		/** @var \Bitrix\Main\Entity\Base $entity */
		$entity = $fullClassName::getEntity();

		return $entity;
	}

	/**
	 * @param $iblockId
	 * @return Entity\DataManager
	 * @throws \Exception
	 */
	public static function getDataClass ($iblockId)
	{
		if (!is_numeric($iblockId))
			throw new \Exception('iblockId must be integer');

		$entity = Element::createEntity($iblockId);

		return $entity->getDataClass();
	}
}