<?php

namespace Olympia\Fssmo\Import;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model;

class News
{
	public static function importFromExternal ()
	{
		$items = External\NewsTable::query()
			->setSelect(['*'])
			->exec();

		/** @var External\NewsTable $item */
		foreach ($items as $item)
		{
			$isExist = Model\NewsTable::query()
				->setSelect(['ID'])
				->setFilter(['=XML_ID' => 'N_'.$item->id])
				->setLimit(1)
				->exec()->fetch();

			$body = $item->Body;
			$body = preg_replace('/ style="(.*?)"/i', '', $body);

			if ($isExist)
			{
				Model\NewsTable::update($isExist['ID'], [
					'NAME' => trim(trim($item->Title), '.'),
					'ACTIVE_FROM' => $item->Date->format('d.m.Y'),
					'CODE' => \Cutil::translit(trim($item->Title).'-'.$item->id,'ru', ['replace_space' => '-', 'replace_other' => '-']),
					'PREVIEW_TEXT' => strip_tags($item->Teaser),
					'PREVIEW_TEXT_TYPE' => 'html',
					'DETAIL_TEXT' => $body,
					'DETAIL_TEXT_TYPE' => 'html',
				]);
			}
			else
			{
				Model\NewsTable::add([
					'ACTIVE' => 'Y',
					'ACTIVE_FROM' => $item->Date->format('d.m.Y'),
					'NAME' => trim(trim($item->Title), '.'),
					'CODE' => \Cutil::translit(trim($item->Title).'-'.$item->id,'ru', ['replace_space' => '-', 'replace_other' => '-']),
					'XML_ID' => 'N_'.$item->id,
					'PREVIEW_TEXT' => strip_tags($item->Teaser),
					'PREVIEW_TEXT_TYPE' => 'html',
					'DETAIL_TEXT' => $body,
					'DETAIL_TEXT_TYPE' => 'html',
				]);
			}
		}
	}
}