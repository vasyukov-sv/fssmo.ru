<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Data\Cache;
use Olympia\Fssmo\Db\External\DigitsTable;

class Digits
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'DIGITS|'.LANGUAGE_ID;
		$cachePath = 'graphql/digits';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = DigitsTable::query()
				->setSelect(['id', 'Digit'])
				->exec();

			/** @var DigitsTable $item */
			foreach ($items as $item)
			{
				$result[] = [
					'id' => (int) $item->id,
					'title' => (string) $item->Digit,
				];
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}