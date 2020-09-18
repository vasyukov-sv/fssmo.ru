<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;
use Olympia\Fssmo\Db\External\ClubsTable;
use Olympia\Fssmo\Model\CalendarTable;
use Olympia\Fssmo\Model\DisciplinesTable;

class CalendarList
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, $limit);

		$result = [
			'items' => [],
			'pagination' => [
				'total' => 0,
				'limit' => $limit,
				'page' => $page,
			]
		];

		$list = PropertyEnumerationTable::query()
			->setSelect(['ID', 'VALUE'])
			->setFilter(['=PROPERTY.IBLOCK_ID' => IBLOCK_CALENDAR])
			->exec();

		$enums = [];

		foreach ($list as $enum)
			$enums[$enum['ID']] = $enum['VALUE'];

		$disciplines = [];

		$items = DisciplinesTable::query()
			->setSelect(['ID', 'NAME'])
			->setFilter(['=ACTIVE' => 'Y'])
			->exec();

		/** @var DisciplinesTable $item */
		foreach ($items as $item)
			$disciplines[(int) $item->ID] = (string) $item->NAME;

		$filter = $args['filter'] ?? [];

		$items = CalendarTable::query()
			->setOrder(['PROPERTY.ACTIVE_FROM' => 'ASC'])
			->setSelect([
				'ID',
				'NAME',
				'CREATED_BY',
				'PROPERTY.ACTIVE_FROM',
				'PROPERTY.ACTIVE_TO',
				'PROPERTY.COUNTRY',
				'PROPERTY.DISTRICT',
				'PROPERTY.CITY',
				'PROPERTY.DISCIPLINE',
				'PROPERTY.STATUS',
				'PROPERTY.CLUB',
				'PROPERTY.SITE',
				'PROPERTY.TARGETS',
			])
			->setLimit($limit)
			->setOffset(($page - 1) * $limit)
			->countTotal(true)
			->setFilter(['=ACTIVE' => 'Y']);

		if ($filter['country'] && (int) $filter['country'] > 0)
			$items->addFilter('PROPERTY.COUNTRY', (int) $filter['country']);

		if ($filter['district'] && (int) $filter['district'] > 0)
			$items->addFilter('PROPERTY.DISTRICT', (int) $filter['district']);

		if ($filter['city'] && $filter['city'] != '')
			$items->addFilter('%PROPERTY.CITY', $filter['city']);

		if ($filter['discipline'] && (int) $filter['discipline'] > 0)
			$items->addFilter('PROPERTY.DISCIPLINE', (int) $filter['discipline']);

		if ($filter['status'] && (int) $filter['status'] > 0)
			$items->addFilter('PROPERTY.STATUS', (int) $filter['status']);

		if ($filter['club'] && (int) $filter['club'] > 0)
		{
			$club = ClubsTable::query()
				->setSelect(['id', 'ClubName'])
				->setFilter(['=id' => (int) $filter['club']])
				->exec()->fetch();

			if ($club)
				$items->addFilter('PROPERTY.CLUB', $club->ClubName);
		}

		if (isset($filter['user']))
		{
			$items->addFilter('CREATED_BY', (int) $context['user']);
			$items->addFilter('>PROPERTY.ACTIVE_FROM', date("Y-m-d 00:00:00"));
		}
		else
		{
			$year = date('Y');

			if ($filter['year'] && (int) $filter['year'] > 0)
				$year = (int) $filter['year'];

			$items->addFilter('><PROPERTY.ACTIVE_FROM', [
				date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $year)),
				date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $year + 1))
			]);
		}

		$items = $items->exec();

		$result['pagination']['total'] = $items->getCount();

		$locationsId = [];

		$disciplinesReplace = [
			'Спортинг' => 'СП',
			'Спортинг-компакт' => 'СПК',
			'Спортинг-дуплеты' => 'СПД',
			'Cпортрап' => 'СТ',
			'Скит' => 'СК',
			'Трап' => 'ТР',
			'Дабл-трап' => 'ДТР',
			'Скит, Трап, Дабл-трап' => 'СК, ТР, ДТР',
		];

		foreach ($items as $item)
		{
			$disc = $disciplines[$item['PROPERTY_DISCIPLINE']] ?? '';

			$result['items'][] = [
				'id' => (int) $item['ID'],
				'name' => $item['NAME'],
				'active_from' => date("c", strtotime($item['PROPERTY_ACTIVE_FROM'])),
				'active_to' => date("c", strtotime($item['PROPERTY_ACTIVE_TO'])),
				'country' => $item['PROPERTY_COUNTRY'],
				'district' => $item['PROPERTY_DISTRICT'],
				'city' => $item['PROPERTY_CITY'],
				'discipline' => $disciplinesReplace[$disc] ?? $disc,
				'status' => $enums[$item['PROPERTY_STATUS']] ?? '',
				'club' => $item['PROPERTY_CLUB'],
				'site' => $item['PROPERTY_SITE'],
				'targets' => $enums[$item['PROPERTY_TARGETS']] ?? '',
				'edit' => $context['user'] && $context['user'] == $item['CREATED_BY'] && strtotime($item['PROPERTY_ACTIVE_FROM']) > time(),
			];

			if (!in_array($item['PROPERTY_COUNTRY'], $locationsId))
				$locationsId[] = $item['PROPERTY_COUNTRY'];

			if (!in_array($item['PROPERTY_DISTRICT'], $locationsId))
				$locationsId[] = $item['PROPERTY_DISTRICT'];
		}

		if (count($locationsId))
		{
			$locations = [];

			Loader::includeModule('sale');

			$list = LocationTable::query()
				->setSelect(['ID', 'TYPE_ID', 'LOCATION_SHORT_NAME' => 'NAME.SHORT_NAME', 'LOCATION_NAME' => 'NAME.NAME'])
				->setFilter(['=ID' => $locationsId, '=NAME.LANGUAGE_ID' => LANGUAGE_ID])
				->exec();

			foreach ($list as $item)
				$locations[$item['ID']] = $item['TYPE_ID'] == 2 ? $item['LOCATION_SHORT_NAME'] : $item['LOCATION_NAME'];

			foreach ($result['items'] as $i => $item)
			{
				$result['items'][$i]['country'] = $locations[$item['country']] ?? '';
				$result['items'][$i]['district'] = $locations[$item['district']] ?? '';
			}
		}

		return $result;
	}
}