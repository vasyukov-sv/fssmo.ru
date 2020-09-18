<?php

namespace Olympia\Fssmo\Api\Queries;

use CFile;
use Olympia\Fssmo\Model\SiteDisciplinesTable;

class SiteDisciplines
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$items = SiteDisciplinesTable::query()
			->setOrder(['SORT' => 'ASC'])
			->setSelect([
				'ID', 'NAME', 'DETAIL_TEXT',
				'PROPERTY.RULES',
				'PROPERTY.PICTURE',
				'PROPERTY.PICTURE_TEXT_1',
				'PROPERTY.PICTURE_TEXT_2',
				'PROPERTY.MEMBERS',
				'PROPERTY.TARGETS',
			])
			->setFilter(['=ACTIVE' => 'Y'])
			->exec();

		/** @var SiteDisciplinesTable $item */
		foreach ($items as $item)
		{
			$row = [
				'id' => (int) $item->ID,
				'title' => (string) $item->NAME,
				'text' => $item->DETAIL_TEXT,
				'rules' => null,
				'members' => null,
				'targets' => null,
				'picture' => null,
			];

			if ($item->getProperty('RULES'))
			{
				$file = CFile::GetFileArray($item->getProperty('RULES'));

				if ($file)
				{
					$row['rules'] = [
						'title' => $file['DESCRIPTION'] != '' ? $file['DESCRIPTION'] : $file['ORIGINAL_NAME'],
						'src' => $file['SRC'],
						'size' => (int) $file['FILE_SIZE'],
						'extension' => GetFileExtension($file['ORIGINAL_NAME']),
					];
				}
			}

			if ($item->getProperty('MEMBERS'))
				$row['members'] = trim($item->getProperty('MEMBERS'));

			if ($item->getProperty('TARGETS'))
				$row['targets'] = trim($item->getProperty('TARGETS'));

			if ($item->getProperty('PICTURE'))
			{
				$file = CFile::ResizeImageGet($item->getProperty('PICTURE'), ['width' => 750, 'height' => 750], BX_RESIZE_IMAGE_PROPORTIONAL);

				if ($file)
				{
					$row['picture'] = [
						'image' => $file['src'],
						'text_1' => $item->getProperty('PICTURE_TEXT_1'),
						'text_2' => $item->getProperty('PICTURE_TEXT_2'),
					];
				}
			}

			$result[] = $row;
		}

		return $result;
	}
}