<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use CFile;
use Olympia\Fssmo\Model\NewsTable;

class News
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$pagination = $args['pagination'] ?? [];
		$discipline = (int) $args['discipline'] ?? 0;

		$page = $pagination['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $pagination['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		$cache = Cache::createInstance();
		$cacheId = 'NEWS|'.$page.'|'.$limit.'|'.$discipline.'|'.LANGUAGE_ID;
		$cachePath = 'graphql/news';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = NewsTable::query()
				->setOrder(['ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'])
				->setSelect([
					'ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_TEXT', 'PREVIEW_PICTURE',
					'DETAIL_TEXT', 'ACTIVE_FROM', 'DISCIPLINE.ID', 'DISCIPLINE.NAME',
					'COMPETITION.ID', 'COMPETITION.DETAIL_PAGE_URL', 'COMPETITION.PROPERTY.REGISTRATION'
				])
				->setFilter(['ACTIVE' => 'Y'])
				->setLimit($limit)
				->setOffset(($page - 1) * $limit);

			if ($discipline > 0)
				$items->addFilter('PROPERTY.DISCIPLINE', $discipline);

			$items = $items->exec();

			/** @var NewsTable $item */
			foreach ($items as $item)
			{
				$image = CFile::ResizeImageGet($item->PREVIEW_PICTURE, ['width' => 700, 'height' => 700], BX_RESIZE_IMAGE_PROPORTIONAL);

				$row = [
					'id' => (int) $item->ID,
					'title' => $item->NAME,
					'url' => $item->DETAIL_PAGE_URL,
					'preview' => $item->PREVIEW_TEXT,
					'text' => $item->DETAIL_TEXT,
					'date' => $item->ACTIVE_FROM ? $item->ACTIVE_FROM->format('c') : null,
					'image' => $image ? $image['src'] : null,
					'discipline' => $item->DISCIPLINE->ID ? $item->DISCIPLINE->NAME : null,
					'competition' => null
				];

				if ($item->COMPETITION->ID)
				{
					$row['competition'] = [
						'id' => (int) $item->COMPETITION->ID,
						'url' => $item->COMPETITION->DETAIL_PAGE_URL,
						'registration' => $item->getProperty('REGISTRATION') > 0
					];
				}

				$result[] = $row;
			}

			$tag = Application::getInstance()->getTaggedCache();
			$tag->startTagCache($cachePath);
			$tag->registerTag('iblock_id_'.IBLOCK_NEWS);
			$tag->endTagCache();

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}