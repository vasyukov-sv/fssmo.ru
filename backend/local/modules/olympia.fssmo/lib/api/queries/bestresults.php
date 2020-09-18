<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class BestResults
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$results = [];

		$items = External\ResultsTable::query()
			->setOrder(['ShooterId' => 'ASC', 'Summ' => 'DESC'])
			->setSelect(['id', 'Summ', 'ShooterId', 'Competition.CompName', 'Competition.TargetsCount', 'Competition.BeginDate', 'Competition.DisciplineId', 'Shooter.GenderId', 'Shooter.FirstName', 'Shooter.MiddleName', 'Shooter.LastName', 'Competition.Discipline.Discipline'])
			->setFilter([
				'>Summ' => 0,
				'=Shooter.LastName' => trim($args['name']),
				'=Competition.IsRating' => true,
				'>Competition.BeginDate' => DateTime::createFromTimestamp(time() - (3 * 365 * 86400)),
			])
			->exec();

		/** @var External\ResultsTable $item */
		foreach ($items as $item)
		{
			if (!isset($results[$item->ShooterId]))
			{
				$results[$item->ShooterId] = [
					'shooter' => [
						'name' => $item->Shooter->FirstName,
						'second_name' => $item->Shooter->MiddleName,
						'last_name' => $item->Shooter->LastName,
					],
					'competitions' => []
				];
			}

			/** @var External\NormativesTable $normative */
			$normative = External\NormativesTable::query()
				->setOrder(['Result' => 'DESC'])
				->setSelect(['id', 'Digit.Digit'])
				->setFilter([
					'=GenderId' => $item->Shooter->GenderId,
					'=DisciplineId' => $item->Competition->DisciplineId,
					'=TargetsCount' => $item->Competition->TargetsCount,
					'<=Result' => $item->Summ,
					['LOGIC' => 'OR',
						['<=Period.StartDate' => $item->Competition->BeginDate, '>=Period.EndDate' => $item->Competition->BeginDate],
						['=Period.StartDate' => false, '>=Period.EndDate' => $item->Competition->BeginDate],
						['<=Period.StartDate' => $item->Competition->BeginDate, '=Period.EndDate' => false],
					]
				])
				->setLimit(1)
				->exec()->fetch();

			$results[$item->ShooterId]['competitions'][] = [
				'id' => $item->id,
				'title' => $item->Competition->CompName,
				'summ' => $item->Summ,
				'targets' => $item->Competition->TargetsCount,
				'discipline' => $item->Competition->Discipline->Discipline,
				'date' => $item->Competition->BeginDate ? $item->Competition->BeginDate->format('c') : null,
				'digit' => $normative ? $normative->Digit->Digit : ''
			];
		}

		return $results;
	}
}