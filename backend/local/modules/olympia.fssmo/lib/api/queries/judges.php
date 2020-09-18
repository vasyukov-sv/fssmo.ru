<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use CFile;
use Olympia\Fssmo\Model\JudgesTable;
use Olympia\Fssmo\Model\NewsTable;

class Judges
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$items = JudgesTable::query()
			->setOrder(['SORT' => 'ASC', 'NAME' => 'ASC'])
			->setSelect(['ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY.POSITION'])
			->setFilter(['=ACTIVE' => 'Y'])
			->exec();

		/** @var JudgesTable $item */
		foreach ($items as $item)
		{
			$image = null;

			if ($item->PREVIEW_PICTURE > 0)
				$image = CFile::ResizeImageGet($item->PREVIEW_PICTURE, ['width' => 500, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL);

			$row = [
				'id' => (int) $item->ID,
				'title' => $item->NAME,
				'text' => $item->PREVIEW_TEXT,
				'image' => $image ? $image['src'] : $image,
				'position' => (string) $item->getProperty('POSITION'),
			];

			$result[] = $row;
		}

		return $result;
	}
}