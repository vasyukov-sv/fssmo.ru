<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use CFile;
use Olympia\Fssmo\Model;

class Sponsors
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'SPONSORS|'.LANGUAGE_ID;
		$cachePath = 'graphql/sponsors';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = Model\SponsorTable::query()
				->setOrder(['SORT' => 'ASC'])
				->setSelect(['ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY.URL', 'PROPERTY.TYPE', 'TYPE.XML_ID'])
				->setFilter(['=ACTIVE' => 'Y'])
				->registerRuntimeField((new Reference('TYPE',
					PropertyEnumerationTable::class,
						Join::on('this.PROPERTY.TYPE', 'ref.ID')
					))->configureJoinType('left')
				)
				->exec();

			/** @var Model\SponsorTable $item */
			foreach ($items as $item)
			{
				$image = null;

				if ($item->PREVIEW_PICTURE > 0)
					$image = CFile::ResizeImageGet($item->PREVIEW_PICTURE, ['width' => 300, 'height' => 300], BX_RESIZE_PROPORTIONAL)['src'];

				$result[] = [
					'id' => $item->ID,
					'title' => $item->NAME,
					'image' => $image,
					'url' => $item->getProperty('URL'),
					'type' => $item['TYPE_XML_ID'],
				];
			}

			$tag = Application::getInstance()->getTaggedCache();
			$tag->startTagCache($cachePath);
			$tag->registerTag('iblock_id_'.IBLOCK_SPONSORS);
			$tag->endTagCache();

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}