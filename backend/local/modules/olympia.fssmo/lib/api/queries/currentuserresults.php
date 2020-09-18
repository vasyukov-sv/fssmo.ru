<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;

class CurrentUserResults
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if (!is_integer($context['user']))
			throw new Exception('not login');

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $context['user']])
			->exec()->fetch();

		$items = External\ShootersTable::query()
			->setSelect([
				'id',
				'Competition.id', 'Competition.CompName', 'Competition.BeginDate', 'Competition.Discipline.Discipline',
				'Result.s1', 'Result.s2', 'Result.s3', 'Result.s4', 'Result.s5', 'Result.s6', 'Result.s7', 'Result.s8', 'Result.Summ'
			])
			->setFilter([
				'=UserId' => $user['XML_ID'],
			])
			->registerRuntimeField((new Reference('Competition',
				External\CompetitionsTable::class,
					Join::on('this.Result.CompId', 'ref.id')
				))->configureJoinType('inner')
			)
			->registerRuntimeField((new Reference('Result',
				External\ResultsTable::class,
					Join::on('this.id', 'ref.ShooterId')
				))->configureJoinType('inner')
			)
			->exec();

		$result = [];

		foreach ($items as $item)
		{
			$row = [
				'competition' => $item['Competition']->CompName,
				'discipline' => $item['Competition']->Discipline->Discipline,
				'date' => $item['Competition']->BeginDate ? $item['Competition']->BeginDate->format('c') : null,
				'stands' => [],
				'summ' => $item['Result']->Summ,
				'or' => 0
			];

			for ($i = 1; $i <= 8; $i++)
				$row['stands'][$i] = $item['Result']->{'s'.$i};

			$result[$item['Competition']->id] = $row;
		}

		if (count($result))
		{
			$competitions = [];

			$items = External\CompetitionsTable::query()
				->setOrder(['BeginDate' => 'DESC'])
				->setSelect(['id', 'TargetsCount', 'MaximumResult', 'DisciplineId'])
				->setFilter([
					'=id' => array_keys($result)
				])
				->exec();

			/** @var External\CompetitionsTable $item */
			foreach ($items as $item)
				$competitions[$item->id] = $item;

			$maxResult = [];

			$items = External\ResultsTable::query()
				->setSelect(['MAX_RESULT', 'CompId',])
				->setFilter(['=CompId' => array_keys($competitions)])
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

			foreach ($result as $compId => &$row)
			{
				if ($competitions[$compId]->DisciplineId != 4)
				{
					$shootPlaces = ($competitions[$compId]->TargetsCount ?? 100) / 25;
					$shootNumbers = 0;

					for ($i = 1; $i <= 8; $i++)
					{
						if ((int) $row['stands'][$i] > 0)
							$shootNumbers++;
					}

					if ($shootPlaces > $shootNumbers)
						continue;
				}

				$row['or'] = 100 * ($row['summ'] / $maxResult[$compId]);
			}

			unset($row);
		}

		usort($result, function ($a, $b) {
			return $a['date'] < $b['date'];
		});

		return array_values($result);
	}
}