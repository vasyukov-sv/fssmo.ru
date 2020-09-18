<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use CFile;
use Olympia\Fssmo\Model;

class Slider
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$cache = Cache::createInstance();
		$cacheId = 'SLIDER|'.LANGUAGE_ID;
		$cachePath = 'graphql/slider';

		if ($cache->initCache(86400, $cacheId, $cachePath))
			$result = $cache->getVars();
		else
		{
			$result = [];

			$items = Model\SliderTable::query()
				->setOrder(['SORT' => 'ASC'])
				->setSelect(['ID', 'NAME', 'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'ACTIVE_FROM', 'ACTIVE_TO', 'PROPERTY.COMPETITION'])
				->setFilter(['=ACTIVE' => 'Y'])
				->exec();

			/** @var Model\SliderTable $item */
			foreach ($items as $item)
			{
				$image = null;

				if ($item->PREVIEW_PICTURE > 0)
				{
					$f = CFile::ResizeImageGet($item->PREVIEW_PICTURE, ['width' => 750, 'height' => 750], BX_RESIZE_IMAGE_PROPORTIONAL);

					if ($f)
						$image = $f['src'];
				}

				$row = [
					'id' => (int) $item->ID,
					'title' => $item->NAME,
					'image' => $image,
					'description' => $item->PREVIEW_TEXT,
					'date_from' => $item->ACTIVE_FROM ? $item->ACTIVE_FROM->format('c') : null,
					'date_to' => $item->ACTIVE_TO ? $item->ACTIVE_TO->format('c') : null,
					'button_text' => null,
					'button_url' => null,
				];

				if ($item->getProperty('COMPETITION') > 0)
				{
					/** @var Model\CompetitionsTable $competition */
					$competition = Model\CompetitionsTable::query()
						->setSelect(['ID', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'PROPERTY.DATE_FROM', 'PROPERTY.DATE_TO'])
						->setFilter(['=ID' => $item->getProperty('COMPETITION')])
						->exec()->fetch();

					if ($competition)
					{
						if (!$row['image'] && $competition->PREVIEW_PICTURE > 0)
						{
							$f = CFile::ResizeImageGet($competition->PREVIEW_PICTURE, ['width' => 750, 'height' => 750], BX_RESIZE_IMAGE_PROPORTIONAL);

							if ($f)
								$row['image'] = $f['src'];
						}

						if (!$row['date_from'])
							$row['date_from'] = $competition->getProperty('DATE_FROM') ? date('Y-m-d\TH:i:s', strtotime($competition->getProperty('DATE_FROM'))) : null;

						if (!$row['date_to'])
							$row['date_to'] = $competition->getProperty('DATE_TO') ? date('Y-m-d\TH:i:s', strtotime($competition->getProperty('DATE_TO'))) : null;

						$row['button_text'] = 'Зарегистрироваться';
						$row['button_url'] = $competition->DETAIL_PAGE_URL;
					}
				}

				$result[] = $row;
			}

			$tag = Application::getInstance()->getTaggedCache();
			$tag->startTagCache($cachePath);
			$tag->registerTag('iblock_id_'.IBLOCK_SLIDER);
			$tag->endTagCache();

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}