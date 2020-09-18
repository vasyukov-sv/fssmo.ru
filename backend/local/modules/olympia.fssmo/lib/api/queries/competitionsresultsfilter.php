<?php

namespace Olympia\Fssmo\Api\Queries;

use Olympia\Fssmo\Filter;

class CompetitionsResultsFilter
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$smartFilter = new Filter();
		$smartFilter->addParams([
			'IBLOCK_ID' => IBLOCK_COMPETITIONS,
			'FILTER_NAME' => 'filter'
		]);

		$smartFilter->executeComponent();
		$smartFilter->setFilter([
			'<PROPERTY_DATE_FROM' => date('Y-m-d 00:00:00'),
		]);
		$smartFilter->loadItems();

		$parseUrl = $smartFilter->convertUrlToCheck($args['filter']);

		if (count($parseUrl))
			$params = $parseUrl;
		else
			parse_str($args['filter'], $params);

		global $competitionFilter;
		$competitionFilter = $smartFilter->parse($params);

		$url = str_replace('f/clear/', '', $smartFilter->makeSmartUrl('/results/f/#SMART_FILTER_PATH#/', true));

		return [
			'items' => $smartFilter->getItems(),
			'url' => $url
		];
	}
}