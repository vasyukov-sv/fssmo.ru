<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;
use Olympia\Fssmo\Model\CalendarTable;
use Olympia\Fssmo\Db\External\ClubsTable;

class CalendarForm
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		Loader::includeModule('iblock');

		$props = $args['data'];

		foreach ($props as $code => $value)
		{
			if (!is_array($value))
				$props[$code] = trim(htmlspecialchars(addslashes($value)));
		}

		Loader::includeModule('sale');

		$club = ClubsTable::query()
			->setSelect(['id', 'ClubName'])
			->setFilter(['=id' => (int) $props['club']])
			->exec()->fetch();

		$country = LocationTable::query()
			->setSelect(['ID'])
			->setFilter(['=TYPE.CODE' => 'COUNTRY', '=ID' => (int) $props['country']])
			->exec()->fetch();

		$district = LocationTable::query()
			->setSelect(['ID'])
			->setFilter(['=TYPE.CODE' => 'COUNTRY_DISTRICT', '=ID' => (int) $props['district'], '=PARENT_ID' => $country['ID']])
			->exec()->fetch();

		$city = $props['city'] ?? '';

		if (is_numeric($city) && (int) $city > 0)
		{
			$finder = LocationTable::query()
				->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
				->setFilter(['=TYPE.CODE' => 'CITY', '=ID' => (int) $city, '=COUNTRY_ID' => $country['ID'], '=NAME.LANGUAGE_ID' => LANGUAGE_ID])
				->exec()->fetch();

			if ($finder)
				$city = $finder['LOCATION_NAME'];
		}

		$fields = [
			'ACTIVE' => 'Y',
			'NAME' => $props['name'] ?? 'Соревнование',
			'PROPERTY' => [
				'ACTIVE_FROM' => date("d.m.Y H:i:s", strtotime($props['date_start'])),
				'ACTIVE_TO' => date("d.m.Y H:i:s", strtotime($props['date_end'])),
				'DISCIPLINE' => (int) $props['discipline'],
				'STATUS' => (int) $props['status'],
				'TARGETS' => (int) $props['targets'],
				'CLUB' => $club ? $club->ClubName : '',

				'COUNTRY' => $country['ID'] ?? '',
				'DISTRICT' => $district['ID'] ?? '',
				'CITY' => $city,

				'SITE' => $props['url'],
			]
		];

		if ($args['id'] > 0)
		{
			$item = CalendarTable::query()
				->setSelect([
					'ID',
				])
				->setFilter([
					'=ACTIVE' => 'Y', 
					'=ID' => (int) $args['id'],
					'=CREATED_BY' => $context['user'],
				])
				->exec()->fetch();

			if (!$item)
				throw new \Exception('Соревнование не найдено');

			CalendarTable::update($item['ID'], $fields);
		}
		else
		{
			CalendarTable::add($fields);
		}

		return true;
	}
}