<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;
use Olympia\Fssmo\Model\CalendarTable;

class CalendarForm
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [
			'country' => [],
			'districs' => [],
			'targets' => [],
			'status' => [],
			'item' => null,
		];

		Loader::includeModule('sale');

		$locations = LocationTable::query()
			->setOrder(['SORT' => 'ASC', 'NAME.NAME' => 'ASC'])
			->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
			->setFilter(['=TYPE.CODE' => 'COUNTRY', '=NAME.LANGUAGE_ID' => LANGUAGE_ID])
			->exec();

		$russiaId = 0;

		foreach ($locations as $location)
		{
			if ($location['LOCATION_NAME'] == 'Россия')
				$russiaId = $location['ID'];

			$result['country'][] = [
				'id' => (int) $location['ID'],
				'title' => $location['LOCATION_NAME'],
			];
		}

		$targets = PropertyEnumerationTable::query()
			->setOrder(['SORT' => 'ASC', 'VALUE' => 'ASC'])
			->setSelect(['ID', 'VALUE'])
			->setFilter(['=PROPERTY.IBLOCK_ID' => IBLOCK_CALENDAR, '=PROPERTY.CODE' => 'TARGETS'])
			->exec();

		foreach ($targets as $target)
		{
			$result['targets'][] = [
				'id' => (int) $target['ID'],
				'value' => $target['VALUE'],
			];
		}

		$locations = LocationTable::query()
			->setOrder(['SORT' => 'ASC', 'NAME.NAME' => 'ASC'])
			->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
			->setFilter(['=TYPE.CODE' => 'COUNTRY_DISTRICT', '=NAME.LANGUAGE_ID' => LANGUAGE_ID, '=PARENT_ID' => $russiaId])
			->exec();

		foreach ($locations as $location)
		{
			$result['districs'][] = [
				'id' => (int) $location['ID'],
				'value' => $location['LOCATION_NAME'],
			];
		}

		$statuses = PropertyEnumerationTable::query()
			->setOrder(['SORT' => 'ASC', 'VALUE' => 'ASC'])
			->setSelect(['ID', 'VALUE'])
			->setFilter(['=PROPERTY.IBLOCK_ID' => IBLOCK_CALENDAR, '=PROPERTY.CODE' => 'STATUS'])
			->exec();

		foreach ($statuses as $status)
		{
			$result['status'][] = [
				'id' => (int) $status['ID'],
				'value' => $status['VALUE'],
			];
		}

		if (isset($args['id']) && (int) $args['id'] > 0)
		{
			$item = CalendarTable::query()
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
				->setFilter([
					'=ACTIVE' => 'Y', 
					'=ID' => (int) $args['id'],
					'=CREATED_BY' => $context['user'],
				])
				->exec()->fetch();

			if ($item)
			{
				$item['~PROPERTY_CITY'] = $item['PROPERTY_CITY'];

				if ($item['PROPERTY_CITY'] != '')
				{
					$finder = LocationTable::query()
						->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
						->setFilter(['=TYPE.CODE' => 'CITY', '=NAME.NAME' => $item['PROPERTY_CITY'], '=COUNTRY_ID' => $item['PROPERTY_COUNTRY'], '=NAME.LANGUAGE_ID' => LANGUAGE_ID])
						->exec()->fetch();
		
					if ($finder)
						$item['~PROPERTY_CITY'] = (int) $finder['ID'];
				}

				$result['item'] = [
					'id' => (int) $item['ID'],
					'name' => $item['NAME'],
					'active_from' => date("c", strtotime($item['PROPERTY_ACTIVE_FROM'])),
					'active_to' => date("c", strtotime($item['PROPERTY_ACTIVE_TO'])),
					'country' => (int) $item['PROPERTY_COUNTRY'],
					'district' => (int) $item['PROPERTY_DISTRICT'],
					'city' => $item['PROPERTY_CITY'],
					'cityId' => $item['~PROPERTY_CITY'],
					'discipline' => $item['PROPERTY_DISCIPLINE'],
					'status' => $item['PROPERTY_STATUS'],
					'club' => trim($item['PROPERTY_CLUB']),
					'site' => $item['PROPERTY_SITE'],
					'targets' => $item['PROPERTY_TARGETS'],
				];
			}
		}

		return $result;
	}
}