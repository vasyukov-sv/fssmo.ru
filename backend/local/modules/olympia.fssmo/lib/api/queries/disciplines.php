<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Olympia\Fssmo\Model\DisciplinesTable;

class Disciplines
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'DISCIPLINES|'.LANGUAGE_ID;
		$cachePath = 'graphql/disciplines';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = DisciplinesTable::query()
				->setOrder(['SORT' => 'ASC'])
				->setSelect(['ID', 'NAME'])
				->setFilter(['=ACTIVE' => 'Y'])
				->exec();

			/** @var DisciplinesTable $item */
			foreach ($items as $item)
			{
				$result[] = [
					'id' => (int) $item->ID,
					'title' => (string) $item->NAME,
				];
			}

			$tag = Application::getInstance()->getTaggedCache();
			$tag->startTagCache($cachePath);
			$tag->registerTag('iblock_id_'.IBLOCK_DISCIPLINES);
			$tag->endTagCache();

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}