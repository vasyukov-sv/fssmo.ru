<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Rating;

class Ratings
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		$result = [
			'items' => [],
			'pagination' => [
				'total' => 0,
				'limit' => $limit,
				'page' => $page,
			]
		];

		$filter = $args['filter'] ?? [];

		$disciplineId = (int) $filter['discipline'] ?? 1;

		$endDate = DateTime::createFromTimestamp(time());
		$startDate = clone $endDate;

		if ((int) $endDate->format('w') === 0)
			$startDate->add('-1 year -2 days');
		elseif ((int) $endDate->format('w') === 6)
			$startDate->add('-1 year -1 days');
		else
			$startDate->add('-1 year');

		$competitions = [];

		$items = External\CompetitionsTable::query()
			->setOrder(['BeginDate' => 'DESC'])
			->setSelect(['id', 'CompName', 'BeginDate', 'Club.ClubName', 'TargetsCount', 'MaximumResult'])
			->setFilter([
				'=IsRating' => true,
				'=DisciplineId' => $disciplineId,
				'>BeginDate' => $startDate,
				'<=BeginDate' => $endDate,
			])
			->exec();

		/** @var External\CompetitionsTable $item */
		foreach ($items as $item)
		{
			$competitions[$item->id] = $item;
		}

		$cache = Cache::createInstance();

		if ($cache->initCache(3600, 'RATING_ACTUAL|'.$disciplineId.'|', '/'))
			$rating = $cache->getVars();
		else
		{
			$rating = Rating::calculateByCompetitions(array_keys($competitions));

			/** @var External\CompetitionsTable $prevCompetition */
			$prevCompetition = External\CompetitionsTable::query()
				->setOrder(['BeginDate' => 'DESC'])
				->setSelect(['id', 'BeginDate'])
				->setFilter([
					'=IsRating' => true,
					'=DisciplineId' => $disciplineId,
				])
				->setLimit(1)
				->setOffset(1)
				->exec()->fetch();

			if ($prevCompetition)
			{
				$endDate = $prevCompetition->BeginDate;

				$startDate = clone $endDate;

				if ((int) $endDate->format('w') === 0)
					$startDate->add('-1 year -2 days');
				elseif ((int) $endDate->format('w') === 6)
					$startDate->add('-1 year -1 days');
				else
					$startDate->add('-1 year');

				$items = External\CompetitionsTable::query()
					->setSelect(['id'])
					->setFilter([
						'=IsRating' => true,
						'=DisciplineId' => $disciplineId,
						'>BeginDate' => $startDate,
						'<=BeginDate' => $endDate,
					])
					->exec();

				$cIds = [];

				foreach ($items as $item)
					$cIds[] = $item->id;

				$previous = Rating::calculateByCompetitions($cIds);

				foreach ($rating as $place => &$data)
				{
					$data['diff'] = 0;

					if ($data['rating'] > 0)
					{
						foreach ($previous as $pPlace => $pData)
						{
							if ($data['shooter'] === $pData['shooter'])
								$data['diff'] = $pPlace - $place;
						}
					}
				}

				unset($data);
			}

			$cache->startDataCache();
			$cache->endDataCache($rating);
		}

		$filterType = $filter['type'] ?? '';
		$filterName = $filter['name'] ?? '';

		if ($filterType !== '' || $filterName != '')
		{
			$items = External\CompShootersTable::query()
				->setSelect(['ShooterId'])
				->setFilter(['=CompId' => array_keys($competitions)]);

			if ($filterType == 'juniors')
				$items->addFilter('>=Shooter.BirthDay', new DateTime('01.01.'.(date('Y') - 20), 'd.m.Y'));
			elseif ($filterType == 'womens')
				$items->addFilter('=Shooter.GenderId', 2)->addFilter('<Shooter.BirthDay', new DateTime('01.01.'.(date('Y') - 20), 'd.m.Y'));
			elseif ($filterType == 'veterans')
				$items->addFilter('<Shooter.BirthDay', new DateTime('31.12.'.(date('Y') - 56), 'd.m.Y'));

			if ($filterName != '')
			{
				$items->registerRuntimeField(new ExpressionField('FULL_NAME', 'UPPER(%s+\' \'+%s+\' \'+%s)', ['Shooter.LastName', 'Shooter.FirstName', 'Shooter.MiddleName']));
				$items->addFilter('%FULL_NAME', $filterName);
			}

			$items = $items->exec();

			$sIds = [];

			/** @var External\CompShootersTable $item */
			foreach ($items as $item)
				$sIds[] = $item->ShooterId;

			if (!count($sIds))
				$rating = [];
			else
			{
				$rating = array_filter($rating, function ($item) use ($sIds) {
					return in_array($item['shooter'], $sIds);
				});
			}
		}

		$result['pagination']['total'] = count($rating);

		$shootersId = [];

		foreach ($rating as $data)
			$shootersId[] = $data['shooter'];

		$shooters = [];

		/** @var External\ShootersTable $items */
		$items = External\ShootersTable::query()
			->setSelect(['id', 'FirstName', 'LastName', 'MiddleName', 'City', 'Club.ClubName', 'Digit.Digit'])
			->setFilter(['=id' => $shootersId])
			->exec();

		foreach ($items as $item)
		{
			$shooters[$item->id] = $item;
		}

		foreach ($rating as $k => $data)
		{
			$rating[$k]['shooterInfo'] = $shooters[$data['shooter']] ?? null;
		}

		unset($shooters);

		$sort = $args['sort'] ?? false;

		if ($sort && is_array($sort))
		{
			if ($sort['field'] === 'place')
			{
				if ($sort['order'] === 'desc')
					$rating = array_reverse($rating, true);
			}
			elseif ($sort['field'] === 'name')
			{
				uasort($rating, function($a, $b) use ($sort)
				{
					$n1 = trim($a['shooterInfo']->LastName.' '.$a['shooterInfo']->FirstName.' '.$a['shooterInfo']->MiddleName);
					$n2 = trim($b['shooterInfo']->LastName.' '.$b['shooterInfo']->FirstName.' '.$b['shooterInfo']->MiddleName);

					return $sort['order'] === 'asc' ? $n1 > $n2 : $n1 < $n2;
				});
			}
			elseif ($sort['field'] === 'city')
			{
				uasort($rating, function($a, $b) use ($sort) {
					return $sort['order'] === 'asc' ? $a['shooterInfo']->City > $b['shooterInfo']->City : $a['shooterInfo']->City < $b['shooterInfo']->City;
				});
			}
			elseif ($sort['field'] === 'club')
			{
				uasort($rating, function($a, $b) use ($sort) {
					return $sort['order'] === 'asc' ? $a['shooterInfo']->Club->ClubName > $b['shooterInfo']->Club->ClubName : $a['shooterInfo']->Club->ClubName < $b['shooterInfo']->Club->ClubName;
				});
			}
			elseif ($sort['field'] === 'digit')
			{
				uasort($rating, function($a, $b) use ($sort) {
					return $sort['order'] === 'asc' ? $a['shooterInfo']->Digit->Digit > $b['shooterInfo']->Digit->Digit : $a['shooterInfo']->Digit->Digit < $b['shooterInfo']->Digit->Digit;
				});
			}
			elseif ($sort['field'] === 'competitions')
			{
				uasort($rating, function($a, $b) use ($sort) {
					return $sort['order'] === 'asc' ? count($a['or']) > count($b['or']) : count($a['or']) < count($b['or']);
				});
			}
			else
			{
				uasort($rating, function($a, $b) use ($sort) {
					return $sort['order'] === 'asc' ? $a[$sort['field']] > $b[$sort['field']] : $a[$sort['field']] < $b[$sort['field']];
				});
			}
		}

		$rating = array_slice($rating, ($page - 1) * $limit, $limit, true);

		foreach ($rating as $index => $data)
		{
			/** @var External\ShootersTable $shooter */
			$shooter = $data['shooterInfo'];

			$row = [
				'place' => $index + 1,
				'diff' => $data['diff'],
				'name' => $shooter ? trim($shooter->LastName.' '.$shooter->FirstName.' '.$shooter->MiddleName) : '',
				'city' => $shooter ? (string) $shooter->City : '',
				'club' => $shooter ? (string) $shooter->Club->ClubName : '',
				'digit' => $shooter ? (string) $shooter->Digit->Digit : '',
				'targets' => $data['targets'],
				'rating' => $data['rating'],
				'group' => $data['group'],
				'competitions' => []
			];

			foreach ($data['or'] as $compId => $or)
			{
				$competition = &$competitions[$compId];

				$row['competitions'][] = [
					'title' => $competition ? $competition->Club->ClubName : '',
					'date' => $competition ? $competition->BeginDate->format('Y-m-d\TH:i:s') : date('Y-m-d\TH:i:s'),
					'or' => $or,
				];
			}

			$result['items'][] = $row;
		}

		unset($shooter, $competition);

		return $result;
	}
}