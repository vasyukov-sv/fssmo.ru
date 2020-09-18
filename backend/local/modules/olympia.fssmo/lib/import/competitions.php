<?php

namespace Olympia\Fssmo\Import;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model;

class Competitions
{
	public static function importFromExternal ()
	{
		define('DISABLE_IBLOCK_HANDLERS', true);

		ini_set('max_execution_time', 9999);

		$items = External\SiteDisciplinesTable::query()
			->setSelect(['*'])
			->exec();

		/** @var External\SiteDisciplinesTable $item */
		foreach ($items as $item)
		{
			$isExist = Model\DisciplinesTable::query()
				->setSelect(['ID'])
				->setFilter(['XML_ID' => $item->id])
				->exec()->fetch();

			if (!$isExist)
			{
				Model\DisciplinesTable::add([
					'NAME' => $item->DisciplineName,
					'CODE' => \Cutil::translit($item->DisciplineName,'ru', ['replace_space' => '-', 'replace_other' => '-']),
					'XML_ID' => $item->id
				]);
			}
		}

		$items = External\CompetitionTypesTable::query()
			->setSelect(['*'])
			->exec();

		$propertyType = PropertyTable::query()
			->setSelect(['ID'])
			->setFilter(['=IBLOCK_ID' => IBLOCK_COMPETITIONS, '=CODE' => 'TYPE'])
			->exec()->fetch();

		$propertySeason = PropertyTable::query()
			->setSelect(['ID'])
			->setFilter(['=IBLOCK_ID' => IBLOCK_COMPETITIONS, '=CODE' => 'SEASON'])
			->exec()->fetch();

		/** @var External\CompetitionTypesTable $item */
		foreach ($items as $item)
		{
			$isExist = PropertyEnumerationTable::query()
				->setSelect(['ID'])
				->setFilter(['=PROPERTY_ID' => $propertyType['ID'], '=XML_ID' => $item->id])
				->exec()->fetch();

			if (!$isExist)
			{
				PropertyEnumerationTable::add([
					'PROPERTY_ID' => $propertyType['ID'],
					'DEF' => 'N',
					'SORT' => $item->id,
					'VALUE' => $item->TypeName,
					'XML_ID' => $item->id
				]);
			}
		}

		$disciplines = [];
		$types = [];
		$seasons = [];

		$items = External\CompetitionsCalendarTable::query()
			->setSelect(['*', 'Competition.id'])
			//->setFilter(['=id' => 696])
			->exec();

		/** @var External\CompetitionsCalendarTable $item */
		foreach ($items as $item)
		{
			$isExist = CompetitionsTable::query()
				->setSelect(['ID'])
				->setFilter(['=XML_ID' => 'CC_'.$item->id])
				->setLimit(1)
				->exec()->fetch();

			if (!isset($disciplines[$item->SiteDiscipline]))
			{
				$e = Model\DisciplinesTable::query()
					->setSelect(['ID'])
					->setFilter(['=XML_ID' => $item->SiteDiscipline])
					->exec()->fetch();

				if ($e)
					$disciplines[$item->SiteDiscipline] = $e['ID'];
			}

			if (!isset($types[$item->CompetitionType]))
			{
				$e = PropertyEnumerationTable::query()
					->setSelect(['ID'])
					->setFilter(['=PROPERTY_ID' => $propertyType['ID'], '=XML_ID' => $item->CompetitionType])
					->exec()->fetch();

				if ($e)
					$types[$item->CompetitionType] = $e['ID'];
			}

			$year = false;

			if ($item->BeginDate)
			{
				$year = $item->BeginDate->format('Y');

				if (!isset($seasons[$year]))
				{
					$e = PropertyEnumerationTable::query()
						->setSelect(['ID'])
						->setFilter(['=PROPERTY_ID' => $propertySeason['ID'], '=XML_ID' => $year])
						->exec()->fetch();

					if ($e)
						$seasons[$year] = $e['ID'];
					else
					{
						$r = PropertyEnumerationTable::add([
							'PROPERTY_ID' => $propertySeason['ID'],
							'DEF' => 'N',
							'SORT' => $year,
							'VALUE' => $year,
							'XML_ID' => $year
						]);

						if ($r->isSuccess())
							$seasons[$year] = $r->getId();
					}
				}
			}

			$item->Poster = str_replace('src="content/', 'src="http://fssmo.ru/content/', $item->Poster);

			$compId = false;

			if ($isExist)
			{
				$compId = $isExist['ID'];

				CompetitionsTable::update($isExist['ID'], [
					'NAME' => $item->CompetitionName,
					'CODE' => \Cutil::translit($item->CompetitionName.'-'.$item->id,'ru', ['replace_space' => '-', 'replace_other' => '-']),
					'DETAIL_TEXT' => $item->Poster,
					'DETAIL_TEXT_TYPE' => 'html',
					'PROPERTY' => [
						'DATE_FROM' => $item->BeginDate,
						'DATE_TO' => $item->EndDate,
						'LOCATION' => $item->ClubName,
						'EXTERNAL_ID' => $item->id,
						'DISCIPLINE' => $disciplines[$item->SiteDiscipline],
						'TYPE' => $types[$item->CompetitionType],
						'SEASON' => $year ? $seasons[$year] : false,
						'URL' => $item->Link,
						'MAX_SHOOTERS' => $item->MaxShootersCount,
					]
				]);
			}
			else
			{
				$res = CompetitionsTable::add([
					'ACTIVE' => 'Y',
					'NAME' => $item->CompetitionName,
					'CODE' => \Cutil::translit($item->CompetitionName.'-'.$item->id,'ru', ['replace_space' => '-', 'replace_other' => '-']),
					'XML_ID' => 'CC_'.$item->id,
					'DETAIL_TEXT' => $item->Poster,
					'DETAIL_TEXT_TYPE' => 'html',
					'PROPERTY' => [
						'DATE_FROM' => $item->BeginDate,
						'DATE_TO' => $item->EndDate,
						'LOCATION' => $item->ClubName,
						'EXTERNAL_ID' => $item->id,
						'DISCIPLINE' => $disciplines[$item->SiteDiscipline],
						'TYPE' => $types[$item->CompetitionType],
						'SEASON' => $year ? $seasons[$year] : false,
						'URL' => $item->Link,
						'MAX_SHOOTERS' => $item->MaxShootersCount,
					]
				]);

				if ($res)
					$compId = $res;
			}

			if ($compId > 0)
			{
				preg_match_all('/<a href="(.*?)" target="_blank"/i', $item->WinnerHtml, $m);

				$l = [];

				foreach ($m[0] as $image)
				{
					$l[] = strpos($item->WinnerHtml, $image);
				}

				$l[] = strlen($item->WinnerHtml);

				$blocks = [];

				foreach ($l as $i => $lenght)
				{
					if (isset($l[$i + 1]))
						$blocks[] = substr($item->WinnerHtml, $lenght, $l[$i + 1] - $lenght);
				}

				preg_match('/<div.*?>(Победитель.*?)<\/div>/iu', $blocks[0], $match1);

				$winners = [
					'winner' => [],
					'groups' => [],
				];

				if (isset($match1[1]))
				{
					$winners['winner']['desc'] = trim(strip_tags(str_replace('&nbsp;', ' ', $match1[1])));

					preg_match('/([a-zA-Zа-яА-Яё]+)\s([a-zA-Zа-яА-Яё]+)(,|\.)/iu', mb_substr($blocks[0], strpos($blocks[0], $match1[0]) + mb_strlen($match1[0])), $match);

					if (isset($match[1]))
					{
						$winners['winner']['name'] = $match[1];
						$winners['winner']['last_name'] = $match[2];
					}
					else
					{
						preg_match('/([a-zA-Zа-яА-Яё]+)\s([a-zA-Zа-яА-Яё]+)(,|\.)/iu', mb_substr($blocks[0], 0, strpos($blocks[0], $match1[0]) + mb_strlen($match1[0])), $match);

						if (isset($match[1]))
						{
							$winners['winner']['name'] = trim($match[1]);
							$winners['winner']['last_name'] = trim($match[2]);
						}
						else
						{
							preg_match('/([a-zA-Zа-яА-Яё]+)\s([a-zA-Zа-яА-Яё]+)\s([a-zA-Zа-яА-Яё]+)/iu', strip_tags(mb_substr($blocks[0], strpos($blocks[0], $match1[0]) + mb_strlen($match1[0]))), $match);

							if (isset($match[1]))
							{
								$winners['winner']['name'] = trim($match[1]);
								$winners['winner']['last_name'] = trim($match[2]);
							}
						}
					}

					if (isset($winners['winner']['name']))
					{
						$winners['winner']['name'] = str_replace('ё', 'е', $winners['winner']['name']);
						$winners['winner']['last_name'] = str_replace('ё', 'е', $winners['winner']['last_name']);

						/** @var External\CompShootersTable $shooter */
						$shooter = External\CompShootersTable::query()
							->setSelect(['id', 'ShooterId'])
							->setFilter([
								[
									'LOGIC' => 'OR',
									['=Shooter.FirstName' => $winners['winner']['name'], '=Shooter.LastName' => $winners['winner']['last_name']],
									['=Shooter.LastName' => $winners['winner']['name'], '=Shooter.FirstName' => $winners['winner']['last_name']],
								],
								'=CompId' => $item->Competition->id
							])
						->exec()->fetch();

						if ($shooter)
						{
							$winners['winner']['shooter'] = $shooter->ShooterId;
						}
					}

					preg_match('/<a href="(.*?)".*?>/iu', $blocks[0], $match);

					if (isset($match[1]))
						$winners['winner']['image'] = trim($match[1]);
				}

				foreach ($blocks as $i => $block)
				{
					preg_match('/Группа ([a-zA-Zа-яА-я]+)/iu', $block, $match);

					if (isset($match[1]))
					{
						preg_match('/<a href="(.*?)".*?>/iu', $block, $match2);

						if (isset($match2[1]))
							$winners['groups'][trim($match[1])] = trim($match2[1]);
					}
				}

				if (isset($winners['winner']['desc']))
				{
					$find = Model\WinnersTable::query()
						->setSelect(['ID'])
						->setFilter(['PROPERTY.COMPETITION' => $compId])
						->exec()->fetch();

					$fields = [
						'NAME' => $winners['winner']['name'].' '.$winners['winner']['last_name'],
						'ACTIVE' => 'Y',
						'XML_ID' => 'WNR_'.$item->id,
						'PREVIEW_PICTURE' => $winners['winner']['image'] ? \CFile::MakeFileArray($winners['winner']['image']) : false,
						'PREVIEW_TEXT' => $winners['winner']['desc'],
						'PREVIEW_TEXT_TYPE' => 'html',
						'PROPERTY' => [
							'COMPETITION' => $compId,
							'SHOOTER' => $winners['winner']['shooter'],
						]
					];

					if ($find)
					{
						//Model\WinnersTable::update($find['ID'], $fields);
					}
					else
					{
						Model\WinnersTable::add($fields);
					}
				}

				if (isset($winners['groups']) && count($winners['groups']) > 0)
				{
					$groups = [];

					foreach ($winners['groups'] as $g => $img)
					{
						$groups[] = [
							'VALUE' => [
								'GROUP' => trim($g),
								'PHOTO' => \CFile::MakeFileArray($img),
							],
							'DESCRIPTION' => ''
						];
					}

					CompetitionsTable::update($compId, [
						'PROPERTY' => [
							'WINNER_GROUPS' => $groups,
						]
					]);
				}
			}
		}
	}
}