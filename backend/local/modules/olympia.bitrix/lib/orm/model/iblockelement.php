<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\ORM\Model;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Bitrix\Iblock\Property\ElementPropertyTable;
use Olympia\Bitrix\Orm\Model;
use Bitrix\Main;
use Bitrix\Main\Orm\Fields;

/**
 * @property $CODE string
 * @property $DETAIL_PAGE_URL string
 * @property $TIMESTAMP_X
 * @property $MODIFIED_BY
 * @property $DATE_CREATE
 * @property $CREATED_BY
 * @property $IBLOCK_ID
 * @property $IBLOCK_SECTION_ID
 * @property $ACTIVE
 * @property $ACTIVE_FROM
 * @property $ACTIVE_TO
 * @property $SORT
 * @property $NAME
 * @property $PREVIEW_PICTURE
 * @property $PREVIEW_TEXT string
 * @property $PREVIEW_TEXT_TYPE string
 * @property $DETAIL_PICTURE integer
 * @property $DETAIL_TEXT string
 * @property $DETAIL_TEXT_TYPE string
 * @property $SEARCHABLE_CONTENT string
 * @property $XML_ID
 * @property $TAGS
 * @property $TMP_ID
 * @property $SHOW_COUNTER
 * @property $SHOW_COUNTER_START
 * @property $IN_SECTIONS
 */
class IBlockElementTable extends Model
{
	const TYPE_TEXT = 'text';
	const TYPE_HTML = 'html';

	public $ID;

	/** @var array */
	static protected $_metadata;
	/** @var array */
	static protected $propFields = [];
	/** @var array */
	static protected $deltFields = [];

	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	protected static function getIblockId ()
	{
		return false;
	}

	public static function getMap()
	{
		$map = [];

		if (static::getIblockId())
		{
			$meta = static::getMetadata(static::getIblockId());

			$map[] = new Fields\ExpressionField(
				'DETAIL_PAGE_URL',
				"'".$meta['iblock']['DETAIL_PAGE_URL']."'"
			);

			if ($meta['iblock']['VERSION'] == 2)
			{
				$propertyEntity = IblockElement\SingleProperty::createEntity($meta['iblock']['ID']);

				$map[] = (new Fields\Relations\Reference(
					'PROPERTY',
					$propertyEntity->getDataClass(),
					Join::on('ref.IBLOCK_ELEMENT_ID', 'this.ID')
				))->configureJoinType('left');

				foreach ($meta['props'] as $prop)
				{
					if ($prop['MULTIPLE'] == 'Y')
					{
						$entity = IblockElement\MultipleProperty::createEntity($meta['iblock']['ID']);

						$map[] = (new Fields\Relations\Reference(
							'PROPERTY_MULTIPLE_'.$prop['CODE'],
							$entity->getDataClass(),
							[
								'=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
								'ref.IBLOCK_PROPERTY_ID' => ['?i', $prop['ID']]
							]
						))->configureJoinType('left');
					}
				}
			}
			else
			{
				foreach ($meta['props'] as $prop)
				{
					$map[] = (new Fields\Relations\Reference(
						'PROPERTY_FILTER_'.$prop['CODE'],
						ElementPropertyTable::class,
						[
							'ref.IBLOCK_ELEMENT_ID' => 'this.ID',
							'ref.IBLOCK_PROPERTY_ID' => ['?i', $prop['ID']]
						]
					))->configureJoinType('inner');
				}
			}
		}

		return array_merge([
			new Fields\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\DatetimeField('TIMESTAMP_X', [
				'default_value' => function() {
					return new Main\Type\DateTime();
				}
			]),
			new Fields\IntegerField('MODIFIED_BY'),
			new Fields\DatetimeField('DATE_CREATE', [
				'default_value' => function() {
					return new Main\Type\DateTime();
				}
			]),
			new Fields\IntegerField('CREATED_BY'),
			new Fields\IntegerField('IBLOCK_ID', [
				'required' => true
			]),
			new Fields\IntegerField('IBLOCK_SECTION_ID'),
			new Fields\BooleanField('ACTIVE', [
				'values' => ['N', 'Y'],
				'default_value' => 'Y'
			]),
			new Fields\DatetimeField('ACTIVE_FROM'),
			new Fields\DatetimeField('ACTIVE_TO'),
			new Fields\IntegerField('SORT', [
				'default_value' => 500
			]),
			new Fields\StringField('NAME', [
				'required' => true,
				'validation' => [
					new Fields\Validators\LengthValidator(null, 5)
				]
			]),
			new Fields\IntegerField('PREVIEW_PICTURE'),
			new Fields\TextField('PREVIEW_TEXT'),
			new Fields\EnumField('PREVIEW_TEXT_TYPE', [
				'values' => [self::TYPE_TEXT, self::TYPE_HTML],
				'default_value' => self::TYPE_TEXT
			]),
			new Fields\IntegerField('DETAIL_PICTURE'),
			new Fields\TextField('DETAIL_TEXT'),
			new Fields\EnumField('DETAIL_TEXT_TYPE', [
				'values' => [self::TYPE_TEXT, self::TYPE_HTML],
				'default_value' => self::TYPE_TEXT
			]),
			new Fields\TextField('SEARCHABLE_CONTENT'),
			new Fields\BooleanField('IN_SECTIONS', [
				'values' => ['N', 'Y']
			]),
			new Fields\StringField('XML_ID', [
				'validation' => [
					new Fields\Validators\LengthValidator(null, 255)
				]
			]),
			new Fields\StringField('CODE', [
				'validation' => [
					new Fields\Validators\LengthValidator(null, 255)
				]
			]),
			new Fields\StringField('TAGS', [
				'validation' => [
					new Fields\Validators\LengthValidator(null, 255)
				]
			]),
			new Fields\StringField('TMP_ID', [
				'validation' => [
					new Fields\Validators\LengthValidator(null, 40)
				]
			]),
			new Fields\IntegerField('SHOW_COUNTER', [
				'default_value' => 0
			]),
			new Fields\DatetimeField('SHOW_COUNTER_START'),
			(new Fields\Relations\Reference(
				'IBLOCK',
				IblockTable::class,
				Join::on('this.IBLOCK_ID', 'ref.ID')
			))->configureJoinType('left'),
			(new Fields\Relations\Reference(
				'SECTION',
				SectionTable::class,
				Join::on('this.IBLOCK_SECTION_ID', 'ref.ID')
			))->configureJoinType('left')
		], $map);
	}

	public static function getMetadata ($iblockId = null)
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
			$result['iblock'] = IblockTable::getRow([
				'select' => ['ID', 'VERSION', 'DETAIL_PAGE_URL'],
				'filter' => ['=ID' => $iblockId]
			]);
			$result['props'] = [];

			$rs = PropertyTable::getList([
				'select' => ['ID', 'CODE', 'MULTIPLE', 'PROPERTY_TYPE', 'USER_TYPE', 'WITH_DESCRIPTION'],
				'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y']
			]);

			while ($arProp = $rs->fetch())
				$result['props'][$arProp['CODE']] = $arProp;

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		static::$_metadata[$iblockId] = $result;

		return $result;
	}

	public static function normalizeQuery ($parameters)
	{
		static::$propFields = [];
		static::$deltFields = [];

		$iblockId = static::getIblockId();

		$select = [];
		$filter = [];
		$filter['=IBLOCK_ID'] = $iblockId;

		if (isset($parameters['filter']))
		{
			foreach ($parameters['filter'] as $key => $val)
			{
				if (is_object($val))
				{
					$filter[$key] = $val;

					continue;
				}

				if (mb_strpos($key, 'PROPERTY') !== false)
				{
					$code = mb_substr($key, mb_strpos($key, 'PROPERTY') + 9);
					$mods = mb_substr($key, 0, mb_strpos($key, 'PROPERTY'));

					if (static::$_metadata[$iblockId]['iblock']['VERSION'] == 1)
					{
						if ($mods == '' || $mods == '=')
							$filter[$mods.'PROPERTY_FILTER_'.$code.'.VALUE'] = $val;
						else
							$filter[$mods.'PROPERTY_FILTER_'.$code.'.VALUE_NUM'] = $val;
					}
					else
					{
						if (static::$_metadata[$iblockId]['props'][$code]['MULTIPLE'] == 'Y')
							$filter[$mods.'PROPERTY_MULTIPLE_'.$code.'.VALUE'] = $val;
						else
							$filter[$key] = $val;
					}

					continue;
				}

				$filter[$key] = $val;
			}
		}

		if (isset($parameters['select']))
		{
			foreach ($parameters['select'] as $alias => $field)
			{
				if (is_object($field))
				{
					$select[$alias] = $field;

					continue;
				}

				$field = str_replace('.*', '', $field);

				if ($field === 'PROPERTY' || $field === 'SECTION')
				{
					$alias = $field.'_';
				}
				else if (mb_strpos($field, 'PROPERTY.') !== false)
				{
					if (is_numeric($alias))
						$alias = 'PROPERTY_'.mb_substr($field, mb_strpos($field, 'PROPERTY') + 9);

					$code = mb_substr($field, mb_strpos($field, 'PROPERTY') + 9);

					static::$propFields[$code] = [
						'value' => $field,
						'alias' => !is_numeric($alias) ? $alias : ''
					];

					if (static::$_metadata[$iblockId]['iblock']['VERSION'] == 1)
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

				if (is_numeric($alias) && $field !== '*')
					$alias = str_replace('.', '_', $field);

				$select[$alias] = $field;
			}
		}

		$parameters['filter'] = $filter;
		$parameters['select'] = $select;

		return $parameters;
	}

	public static function fetchDataModifier ($entry)
	{
		$result = [];

		foreach ($entry as $key => $value)
		{
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
					if (($prefix == '' || strpos($k, $prefix) !== false) && $k != $key)
						$r[str_replace($prefix, '', $k)] = $v;
				}

				$key = preg_replace('/#(.*?)#/', '', $key);

				$value = \CIBlock::ReplaceDetailUrl($value, $r, true, 'E');
			}

			$result[$key] = $value;
		}

		if (static::$_metadata[static::getIblockId()]['iblock']['VERSION'] == 1)
		{
			if (count(static::$propFields))
			{
				$iblockId = static::getIblockId();

				foreach (static::$propFields as $code => $field)
				{
					if (isset(static::$_metadata[$iblockId]['props'][$code]))
						static::$propFields[$code]['property'] = static::$_metadata[$iblockId]['props'][$code]['ID'];
					else
						unset(static::$propFields[$code]);
				}

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

					if (isset($result[$alias]))
					{
						if (!is_array($result[$alias]))
						{
							$result[$alias] = [$result[$alias]];

							if ($property['WITH_DESCRIPTION'] === 'Y')
								$result[$alias.'_DESCRIPTION'] = [$result[$alias.'_DESCRIPTION']];
						}

						$result[$alias][] = $property['VALUE'];

						if ($property['WITH_DESCRIPTION'] === 'Y')
							$result[$alias.'_DESCRIPTION'][] = $property['DESCRIPTION'];
					}
					else
					{
						$result[$alias] = $property['VALUE'];

						if ($property['WITH_DESCRIPTION'] === 'Y')
							$result[$alias.'_DESCRIPTION'] = $property['DESCRIPTION'];
					}
				}
			}
		}
		else
		{
			foreach (static::$_metadata[static::getIblockId()]['props'] as $key => $data)
			{
				if ($data['MULTIPLE'] !== 'Y')
					continue;

				if (isset(static::$propFields[$key]))
					$alias = trim(static::$propFields[$key]['alias']);
				else
					$alias = 'PROPERTY_'.$key;

				if (!array_key_exists($alias, $result))
					continue;

				if (isset($result[$alias]['VALUE']))
				{
					if (isset(static::$propFields[$key]))
					{
						if ($data['WITH_DESCRIPTION'] === 'Y')
							$result[$alias.'_DESCRIPTION'] = $result[$alias]['DESCRIPTION'];

						$result[$alias] = $result[$alias]['VALUE'];
					}
					else
					{
						if ($data['WITH_DESCRIPTION'] === 'Y')
							$result['PROPERTY_'.$key.'_DESCRIPTION'] = $result[$alias]['DESCRIPTION'];

						$result['PROPERTY_'.$key] = $result[$alias]['VALUE'];
					}
				}
			}
		}

		foreach (static::$deltFields as $key)
			unset($result[$key]);

		return $result;
	}

	public function getProperty ($key)
	{
		if (!isset($this->{'PROPERTY_'.$key}))
			throw new \Exception('property '.$key.' not exist in object');

		return $this->{'PROPERTY_'.$key};
	}

	public function setProperty ($key, $value)
	{
		if (!isset($this->{'PROPERTY_'.$key}))
			throw new \Exception('property '.$key.' not exist in object');

		$this->{'PROPERTY_'.$key} = $value;
	}

	public static function add(array $data)
	{
		if (isset($data['PROPERTY']))
		{
			$data['PROPERTY_VALUES'] = $data['PROPERTY'];
			unset($data['PROPERTY']);
		}

		$data['IBLOCK_ID'] = static::getIblockId();

		$el = new \CIBlockElement;
		$id = $el->Add($data);

		if (!$id)
			throw new \Exception($el->LAST_ERROR);

		return (int) $id;
	}

	public static function update($primary, array $data)
	{
		$property = false;

		if (isset($data['PROPERTY']))
		{
			$property = $data['PROPERTY'];
			unset($data['PROPERTY']);
		}
		elseif (isset($data['PROPERTY_VALUES']))
		{
			$property = $data['PROPERTY_VALUES'];
			unset($data['PROPERTY_VALUES']);
		}

		$el = new \CIBlockElement;

		if (count($data) > 0 && !$el->Update($primary, $data, false, false, false, false))
			throw new \Exception($el->LAST_ERROR);

		if ($property)
			\CIBlockElement::SetPropertyValuesEx($primary, static::getIblockId(), $property);

		return true;
	}

	public static function delete($primary)
	{
		return \CIBlockElement::Delete($primary);
	}
}