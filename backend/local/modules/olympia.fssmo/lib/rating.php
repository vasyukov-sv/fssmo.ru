<?php

namespace Olympia\Fssmo;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class Rating
{
	public static function calculateByCompetitions ($comIds)
	{
		$competitions = [];

		$items = External\CompetitionsTable::query()
			->setOrder(['BeginDate' => 'DESC'])
			->setSelect(['id', 'TargetsCount', 'MaximumResult', 'DisciplineId'])
			->setFilter([
				'=IsRating' => true,
				'=id' => $comIds
			])
			->exec();

		/** @var External\CompetitionsTable $item */
		foreach ($items as $item)
			$competitions[$item->id] = $item;

		$maxResult = [];

		$items = External\ResultsTable::query()
			->setSelect([
				'MAX_RESULT', 'CompId',
			])
			->setFilter([
				'=CompId' => array_keys($competitions)
			])
			->registerRuntimeField('MAX_RESULT', new ExpressionField('MAX_RESULT',
				'MAX (%s)', ['Summ']
			))
			->setGroup(['CompId'])
			->exec();

		foreach ($items as $item)
		{
			$maxResult[$item->CompId] = $competitions[$item->CompId]->MaximumResult > 0 ?
				$competitions[$item->CompId]->MaximumResult : $item['MAX_RESULT'];
		}

		$items = External\ResultsTable::query()
			->setOrder(['Competition.BeginDate' => 'DESC'])
			->setSelect([
				'ShooterId',
				'CompId',
				's1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 'Summ'
			])
			->setFilter([
				'=CompId' => array_keys($competitions)
			])
			->setGroup(['CompId'])
			->exec();

		/**
		 * ОР - процентное отношение результата стрелка к лучшему результату турнира
		 * по итогам основной серии
		 */
		$orList = [];

		/** @var External\ResultsTable $item */
		foreach ($items as $item)
		{
			if (!isset($orList[$item->ShooterId]))
				$orList[$item->ShooterId] = [];

			if ($competitions[$item->CompId]->DisciplineId != 4)
			{
				$shootPlaces = ($competitions[$item->CompId]->TargetsCount ?? 100) / 25;
				$shootNumbers = 0;

				for ($i = 1; $i <= 8; $i++)
				{
					if ((int) $item->{'s'.$i} > 0)
						$shootNumbers++;
				}

				if ($shootPlaces > $shootNumbers)
					continue;
			}

			$orList[$item->ShooterId][$item->CompId] = 100 * ($item->Summ / $maxResult[$item->CompId]);
		}

		$result = [];

		foreach ($orList as $shooterId => $ors)
		{
			/**
			 * Р вычисляется как среднее некоторого количества ОР-ов за прошедший календарный год
			 * ОР турниров из 200 мишеней учитывается c коэффициентом 2, из 150 мишеней - коэффициентом 1,5.
			 */

			$p = 0;
			$co = 0;

			$allTargets = 0;

			foreach ($ors as $competitonId => $or)
			{
				$allTargets += $competitions[$competitonId]->TargetsCount;

				$k = $competitions[$competitonId]->TargetsCount / 100;
				$co += $k;
				$p += $k * $or;
			}

			/**
			 * Рейтинг вычисляется как среднее от ОР, не более чем на 10% меньших среднего значения ОР.
			 */
			if ($co > 0)
				$p = 0.9 * ($p / $co);

			$r = 0;
			$co = 0;

			foreach ($ors as $competitonId => $or)
			{
				if ($or < $p)
					continue;

				$k = $competitions[$competitonId]->TargetsCount / 100.0;

				$co += $k;
				$r += $k * $or;
			}

			if ($co > 0)
				$r = $r / $co;

			if ($allTargets >= 300)
			{
				if ($r >= 90)
					$group = 'A';
				elseif ($r >= 84)
					$group = 'B';
				elseif ($r >= 78)
					$group = 'C';
				else
					$group = 'H';
			}
			else
			{
				if ($r >= 90)
					$group = 'x A';
				elseif ($r >= 84)
					$group = 'x B';
				elseif ($r >= 78)
					$group = 'x C';
				else
					$group = 'x H';

				$r = 0;
			}

			$result[] = [
				'shooter' => $shooterId,
				'rating' => $r,
				'targets' => $allTargets,
				'group' => $group,
				'or' => $ors
			];
		}

		usort($result, function($a, $b)
		{
			if (strcmp($a['group'], $b['group']) === 0)
				return $a['rating'] < $b['rating'];

			return strcmp($a['group'], $b['group']) > 0 && $a['rating'] < $b['rating'];
		});

		return $result;
	}

	public static function getByDisciplineId ($disciplineId)
	{
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
			->setSelect(['id'])
			->setFilter([
				'=IsRating' => true,
				'=DisciplineId' => $disciplineId,
				'>BeginDate' => $startDate,
				'<=BeginDate' => $endDate,
			])
			->exec();

		/** @var External\CompetitionsTable $item */
		foreach ($items as $item)
			$competitions[$item->id] = $item;

		return Rating::calculateByCompetitions(array_keys($competitions));
	}
}
