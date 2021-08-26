<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\ExpressionField;
use CFile;
use Olympia\Fssmo\Model\CompetitionsTable;

class PhotoAlbums
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		$competitions = CompetitionsTable::query()
			->setOrder(['PROPERTY.DATE_FROM' => 'DESC'])
			->setSelect(['ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY.PHOTO', 'PROPERTY.DATE_FROM', 'PROPERTY.LOCATION', 'PROPERTY.LOCATION_CITY'])
			->setFilter([
				'=ACTIVE' => 'Y',
				'<PROPERTY.DATE_FROM' => date('Y-m-d 00:00:00')
			])
			->where(new ExpressionField('PHOTO', 'LENGTH(%s)', 'PROPERTY.PHOTO'), '>', 75)
			->setLimit($limit)
			->setOffset(($page - 1) * $limit);

		if (isset($args['filter']) && count($args['filter']))
		{
			if (isset($args['filter']['discipline']) && (int) $args['filter']['discipline'] > 0)
				$competitions->addFilter('PROPERTY.DISCIPLINE', (int) $args['filter']['discipline']);
		}

		$competitions = $competitions->exec();

		/** @var CompetitionsTable $competition */
		foreach ($competitions as $competition)
		{
			$date = null;

			if ($competition->getProperty('DATE_FROM') != '')
				$date = date('Y-m-d\TH:i:s', strtotime($competition->getProperty('DATE_FROM')));

			$location = (string) $competition->getProperty('LOCATION');

			if ($location == '-')
				$location = '';

			if ($competition->getProperty('LOCATION_CITY')) {
				$location .= (!empty($location) ? ', ' : '') . $competition->getProperty('LOCATION_CITY');
			}

			$row = [
				'id' => $competition->ID,
				'title' => $competition->NAME,
				'date' => $date,
				'location' => $location,
				'url' => $competition->DETAIL_PAGE_URL != '' ? $competition->DETAIL_PAGE_URL.'photo/' : '',
				'photos' => []
			];

			$photos = $competition->getProperty('PHOTO');

			foreach ($photos as $i => $photo)
			{
				if ($i >= 4)
					break;

				$file = CFile::GetFileArray($photo);

				if (!$file)
					continue;

				$preview = CFile::ResizeImageGet($file, ['width' => 500, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL);

				$row['photos'][] = [
					'title' => $file['DESCRIPTION'] != '' ? $file['DESCRIPTION'] : $competition['NAME'],
					'preview' => $preview['src'],
					'src' => $file['SRC'],
					'ratio' => round($file['WIDTH'] / $file['HEIGHT'], 2),
				];
			}

			$result[] = $row;
		}

		return $result;
	}
}