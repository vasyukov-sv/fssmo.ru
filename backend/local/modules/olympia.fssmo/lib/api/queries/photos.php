<?php

namespace Olympia\Fssmo\Api\Queries;

use CFile;
use Olympia\Fssmo\Model\CompetitionsTable;

class Photos
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$competition = CompetitionsTable::query()
			->setSelect(['ID', 'NAME', 'PROPERTY.PHOTO']);

		if (is_numeric($args['competition']))
			$competition->addFilter('=ID', (int) $args['competition']);
		else
			$competition->addFilter('=CODE', trim($args['competition']));

		$competition = $competition->exec()->fetch();
		/** @var CompetitionsTable $competition */

		if ($competition)
		{
			$photos = $competition->getProperty('PHOTO');

			foreach ($photos as $photo)
			{
				$file = CFile::GetFileArray($photo);

				if (!$file)
					continue;

				$preview = CFile::ResizeImageGet($file, ['width' => 500, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL);

				$result[] = [
					'title' => $file['DESCRIPTION'] != '' ? $file['DESCRIPTION'] : $competition['NAME'],
					'preview' => $preview['src'],
					'src' => $file['SRC'],
					'ratio' => round($file['WIDTH'] / $file['HEIGHT'], 2),
				];
			}
		}

		return $result;
	}
}