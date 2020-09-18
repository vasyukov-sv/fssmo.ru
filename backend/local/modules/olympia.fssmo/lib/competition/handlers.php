<?php

namespace Olympia\Fssmo\Competition;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo\Model\DisciplinesTable;

class Handlers
{
	public static function registerHandlers ()
	{
		$eventManager = EventManager::getInstance();

		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', [__CLASS__, 'onBeforeIBlockElementAddHandler']);
		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', [__CLASS__, 'onBeforeIBlockElementUpdateHandler']);
		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementDelete', [__CLASS__, 'onBeforeIBlockElementDeleteHandler']);
	}

	private static function getExternalFields ($propValues)
	{
		$propValues['URL'] = $propValues['URL'] ?? '';
		$propValues['LOCATION'] = $propValues['LOCATION'] ?? '';

		$fields = [
			'BeginDate' => $propValues['DATE_FROM'] ? DateTime::createFromTimestamp(strtotime($propValues['DATE_FROM'])) : null,
			'EndDate' => $propValues['DATE_TO'] ? DateTime::createFromTimestamp(strtotime($propValues['DATE_TO'])) : null,
			'ClubName' => $propValues['LOCATION'],
			'MaxShootersCount' => $propValues['MAX_SHOOTERS'] ?? 100,
			'CompetitionType' => null,
			'SiteDiscipline' => null,
			'Link' => $propValues['URL'] != '' ? $propValues['URL'] : null,
		];

		if (isset($propValues['DISCIPLINE']) && $propValues['DISCIPLINE'] > 0)
		{
			$discipline = DisciplinesTable::query()
				->setSelect(['ID', 'NAME', 'XML_ID'])
				->setFilter(['=ID' => $propValues['DISCIPLINE']])
				->exec()->fetch();

			if ($discipline)
			{
				$externalExist = External\SiteDisciplinesTable::getCount(['id' => $discipline['XML_ID']]) > 0;

				if ($externalExist)
					$fields['SiteDiscipline'] = $discipline['XML_ID'];
			}
		}

		if (isset($propValues['TYPE']) && $propValues['TYPE'] > 0)
		{
			$propertyType = PropertyTable::query()
				->setSelect(['ID'])
				->setFilter(['=IBLOCK_ID' => IBLOCK_COMPETITIONS, '=CODE' => 'TYPE'])
				->exec()->fetch();

			$type = PropertyEnumerationTable::query()
				->setSelect(['ID', 'VALUE', 'XML_ID'])
				->setFilter([
					'=PROPERTY_ID' => $propertyType['ID'],
					'=ID' => $propValues['TYPE']
				])
				->exec()->fetch();

			if ($type)
			{
				$externalExist = External\CompetitionTypesTable::getCount(['id' => $type['XML_ID']]) > 0;

				if ($externalExist)
					$fields['CompetitionType'] = $type['XML_ID'];
			}
		}

		return $fields;
	}

	public static function onBeforeIBlockElementAddHandler (&$arFields)
	{
		if ($arFields['IBLOCK_ID'] != IBLOCK_COMPETITIONS)
			return true;

		if (defined('DISABLE_IBLOCK_HANDLERS'))
			return true;

		$props = [];

		$items = PropertyTable::query()
			->setSelect(['ID', 'CODE'])
			->setFilter(['IBLOCK_ID' => IBLOCK_COMPETITIONS])
			->exec();

		foreach ($items as $item)
			$props[$item['ID']] = $item['CODE'];

		$propValues = [];

		foreach ($arFields['PROPERTY_VALUES'] as $propId => $values)
			$propValues[$props[$propId]] = array_values($values)[0]['VALUE'] ?? false;

		$fields = self::getExternalFields($propValues);
		$fields['CompetitionName'] = $arFields['NAME'];

		$result = External\CompetitionsCalendarTable::add($fields);

		if (!$result->isSuccess())
		{
			global $APPLICATION;
			$APPLICATION->throwException('Произошла ошибка при создании соревнования во внешней БД');
			return false;
		}

		$k = array_keys($props, 'EXTERNAL_ID');

		$arFields['PROPERTY_VALUES'][$k[0]] = ['VALUE' => $result->getId()];

		return true;
	}

	public static function onBeforeIBlockElementUpdateHandler (&$arFields)
	{
		if ($arFields['IBLOCK_ID'] != IBLOCK_COMPETITIONS)
			return true;

		if (defined('DISABLE_IBLOCK_HANDLERS'))
			return true;

		$props = [];

		$items = PropertyTable::query()
			->setSelect(['ID', 'CODE'])
			->setFilter(['IBLOCK_ID' => IBLOCK_COMPETITIONS])
			->exec();

		foreach ($items as $item)
			$props[$item['ID']] = $item['CODE'];

		$propValues = [];

		$items = \CIBlockElement::GetPropertyValues(IBLOCK_COMPETITIONS, ['ID' => $arFields['ID']])->Fetch();

		foreach ($items as $item => $value)
		{
			if (is_numeric($item))
				$propValues[$props[$item]] = $value;
		}

		if ($propValues['EXTERNAL_ID'] != '')
		{
			$fields = self::getExternalFields($propValues);

			if (isset($arFields['NAME']))
				$fields['CompetitionName'] = $arFields['NAME'];

			$result = External\CompetitionsCalendarTable::update((int) $propValues['EXTERNAL_ID'], $fields);

			if (!$result->isSuccess())
			{
				global $APPLICATION;
				$APPLICATION->throwException('Произошла ошибка при создании соревнования во внешней БД');
				return false;
			}
		}

		return true;
	}

	public static function onBeforeIBlockElementDeleteHandler ($id)
	{
		if (defined('DISABLE_IBLOCK_HANDLERS'))
			return true;

		$item = ElementTable::query()
			->setSelect(['ID'])
			->setFilter(['=ID' => (int) $id, '=IBLOCK_ID' => IBLOCK_COMPETITIONS])
			->exec()->fetch();

		if (!$item)
			return true;

		/** @var CompetitionsTable $comp */
		$comp = CompetitionsTable::query()
			->setSelect(['ID', 'PROPERTY.EXTERNAL_ID'])
			->setFilter([
				'=ID' => (int) $id,
				'!PROPERTY.EXTERNAL_ID' => false
			])
			->exec()->fetch();

		if ($comp)
		{
			$isLinked = External\CompetitionsTable::query()
				->setSelect(['id'])
				->setFilter(['=SiteId' => $comp->getProperty('EXTERNAL_ID')])
				->exec()->fetch();

			if ($isLinked)
			{
				global $APPLICATION;
				$APPLICATION->throwException("Соревнование привязано к другому соревнованию и его нельзя удалить");
				return false;
			}

			External\CompetitionsCalendarTable::delete($comp->getProperty('EXTERNAL_ID'));
		}

		return true;
	}
}