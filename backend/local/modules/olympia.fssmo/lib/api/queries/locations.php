<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;

class Locations
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		Loader::includeModule('sale');

		$result = [];

		$query = $args['query'] ?? '';
		$type = $args['type'] ?? 'CITY';
		$parent = (int) $args['parent'] ?? 0;

		$locations = LocationTable::query()
			->setOrder(['SORT' => 'ASC', 'NAME.NAME' => 'ASC'])
			->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
			->setFilter(['=TYPE.CODE' => $type, '=NAME.LANGUAGE_ID' => LANGUAGE_ID]);

		if ($parent > 0)
			$locations->addFilter('=PARENT.ID', $parent);

		if ($query != '')
			$locations->addFilter('%NAME.NAME', $query);

		$locations = $locations->exec();

		foreach ($locations as $location)
		{
			$result[] = [
				'id' => (int) $location['ID'],
				'title' => $location['LOCATION_NAME'],
			];
		}

		return $result;
	}
}