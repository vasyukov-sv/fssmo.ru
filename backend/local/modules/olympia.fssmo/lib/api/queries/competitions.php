<?php

namespace Olympia\Fssmo\Api\Queries;

use CFile;
use Olympia\Fssmo\Db\External\RegistredUsersTable;
use Olympia\Fssmo\Model\CompetitionsTable;

class Competitions
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		global $competitionFilter;

		$items = CompetitionsTable::query()
			->setOrder(['PROPERTY.DATE_FROM' => 'ASC'])
			->setSelect([
				'ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL',
				'PREVIEW_PICTURE',
				'PROPERTY.LOCATION',
				'PROPERTY.DATE_FROM',
				'PROPERTY.DATE_TO',
				'PROPERTY.URL',
				'PROPERTY.MAX_SHOOTERS',
				'PROPERTY.EXTERNAL_ID',
				'DISCIPLINE.NAME',
			])
			->setFilter(['=ACTIVE' => 'Y', '>=PROPERTY.DATE_FROM' => date('Y-m-d 00:00:00')])
			->setLimit($limit)
			->setOffset(($page - 1) * $limit);

		if (isset($competitionFilter) && is_array($competitionFilter) && count($competitionFilter))
		{
			foreach ($competitionFilter as $k => $v)
				$items->addFilter($k, $v);
		}

		if (isset($args['filter']) && count($args['filter']))
		{
			if (isset($args['filter']['discipline']) && (int) $args['filter']['discipline'] > 0)
				$items->addFilter('PROPERTY.DISCIPLINE', (int) $args['filter']['discipline']);
		}

		$items = $items->exec();

		/** @var CompetitionsTable $item */
		foreach ($items as $item)
		{
			$dateFrom = null;
			$dateTo = null;

			if ($item->getProperty('DATE_FROM'))
				$dateFrom = date('Y-m-d\TH:i:s', strtotime($item->getProperty('DATE_FROM')));

			if ($item->getProperty('DATE_TO'))
				$dateTo = date('Y-m-d\TH:i:s', strtotime($item->getProperty('DATE_TO')));

			$url = $item->DETAIL_PAGE_URL;

			if ($item->getProperty('URL') != '')
				$url = $item->getProperty('URL');

			$location = trim((string) $item->getProperty('LOCATION'));

			if ($location == '-')
				$location = '';

			$image = null;

			if ($item->PREVIEW_PICTURE > 0)
				$image = CFile::ResizeImageGet($item->PREVIEW_PICTURE, ['width' => 450, 'height' => 310], BX_RESIZE_IMAGE_EXACT)['src'];

			$shooters = RegistredUsersTable::getCount([
				'=SiteCompId' => $item->getProperty('EXTERNAL_ID'),
				'=Refused' => false,
				'=Banned' => false,
			], ['ttl' => 300]);

			$registration = true;

			if ($dateFrom && strtotime($item->getProperty('DATE_FROM')) < time())
				$registration = false;

			if ((int) $shooters >= ((int) $item->getProperty('MAX_SHOOTERS') ?? 100))
				$registration = false;

			$row = [
				'id' => $item->ID,
				'code' => $item->CODE,
				'title' => $item->NAME,
				'url' => $url,
				'discipline' => $item->DISCIPLINE ? (string) $item->DISCIPLINE->NAME : '',
				'location' => $location,
				'date_from' => $dateFrom,
				'date_to' => $dateTo,
				'image' => $image,
				'registration' => $registration,
			];

			$result[] = $row;
		}

		return $result;
	}
}