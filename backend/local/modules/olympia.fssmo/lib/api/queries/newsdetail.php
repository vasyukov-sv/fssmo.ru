<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Helpers;
use Olympia\Fssmo\Model;

class NewsDetail
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'NEWS_DETAIL|'.$args['id'].'|'.LANGUAGE_ID;
		$cachePath = 'graphql/news_detail';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$news = Model\NewsTable::query()
				->setSelect([
					'ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'ACTIVE_FROM',
					'DISCIPLINE.ID', 'DISCIPLINE.NAME', 'COMPETITION.ID', 'COMPETITION.DETAIL_PAGE_URL',
					'COMPETITION.PROPERTY.REGISTRATION'
				])
				->setFilter(['=ACTIVE' => 'Y']);

			if (is_numeric($args['id']))
				$news->addFilter('=ID', (int) $args['id']);
			else
				$news->addFilter('=CODE', trim($args['id']));

			$news = $news->exec()->fetch();
			/** @var Model\NewsTable $news */

			if (!$news)
			{
				$cache->abortDataCache();

				throw new Exception('Новость не найдена');
			}

			$result = [
				'title' => $news->NAME,
				'url' => $news->DETAIL_PAGE_URL,
				'preview' => $news->PREVIEW_TEXT,
				'text' => $news->DETAIL_TEXT,
				'date' => $news->ACTIVE_FROM ? $news->ACTIVE_FROM->format('c') : null,
				'discipline' => $news->DISCIPLINE->ID ? $news->DISCIPLINE->NAME : null,
				'competition' => null,
				'arrows' => [
					'prev' => null,
					'next' => null,
				],
				'similar' => []
			];

			if ($news->COMPETITION->ID)
			{
				$result['competition'] = [
					'id' => (int) $news->COMPETITION->ID,
					'url' => $news->COMPETITION->DETAIL_PAGE_URL,
					'registration' => $news->getProperty('REGISTRATION') > 0
				];
			}

			$arrows = Helpers::getNeighboringItems($news->ID,
				['ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'],
				['IBLOCK_ID' => IBLOCK_NEWS, 'ACTIVE' => 'Y']
			);

			if (isset($arrows['LEFT']['ID']))
				$result['arrows']['prev'] = $arrows['LEFT']['DETAIL_PAGE_URL'];

			if (isset($arrows['RIGHT']['ID']))
				$result['arrows']['next'] = $arrows['RIGHT']['DETAIL_PAGE_URL'];

			$items = Model\NewsTable::query()
				->setOrder(['ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'])
				->setSelect(['ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'ACTIVE_FROM'])
				->setFilter(['ACTIVE' => 'Y', '!ID' => $news->ID])
				->setLimit(3)
				->exec();

			/** @var Model\NewsTable $item */
			foreach ($items as $item)
			{
				$result['similar'][] = [
					'title' => $item->NAME,
					'url' => $item->DETAIL_PAGE_URL,
					'preview' => $item->PREVIEW_TEXT,
					'text' => $item->DETAIL_TEXT,
					'date' => $item->ACTIVE_FROM ? $item->ACTIVE_FROM->format('c') : null,
				];
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