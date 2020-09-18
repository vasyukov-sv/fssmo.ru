<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Data\Cache;
use Olympia\Fssmo\Db\External\ClubsTable;

class Clubs
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'CLUBS|'.LANGUAGE_ID;
		$cachePath = 'graphql/clubs';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = ClubsTable::query()
				->setOrder(['ClubName' => 'ASC'])
				->setSelect(['id', 'ClubName'])
				->exec();

			/** @var ClubsTable $item */
			foreach ($items as $item)
			{
				$result[] = [
					'id' => (int) $item->id,
					'title' => (string) $item->ClubName,
				];
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}