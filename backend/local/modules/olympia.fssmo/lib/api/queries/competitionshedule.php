<?php

namespace Olympia\Fssmo\Api\Queries;

use Olympia\Fssmo\Db\External;
use Olympia\Fssmo;

class CompetitionShedule
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$externalId = Fssmo\Competition::getExternalId($args['competition']);

		/** @var External\ShedulesTable $shedule */
		$shedule = External\ShedulesTable::query()
			->setSelect(['id', 'SheduleJSON'])
			->setFilter(['=CompId' => $externalId])
			->exec()->fetch();

		if (!$shedule)
			return null;

		$result = [
			'places' => [],
			'items' => [],
		];

		$places = External\PlacesTable::query()
			->setOrder(['PlaceName' => 'ASC'])
			->setSelect(['PlaceName'])
			->setFilter(['=CompId' => $externalId])
			->exec();

		/** @var External\PlacesTable $place */
		foreach ($places as $place)
		{
			$result['places'][] = $place->PlaceName;
		}

		$result['view'] = '';

		if (is_array($shedule->SheduleJSON))
		{
			if (isset($shedule->SheduleJSON['startTimeSnake']))
			{
				$result['view'] = 'snake';
				$result['items'] = self::getSnakeView($externalId, $shedule->SheduleJSON, $result['places']);
			}
			else
			{
				$result['view'] = 'shedule';
				$result['items'] = self::getSheduleView($shedule->SheduleJSON, $result['places']);
			}
		}

		return $result;
	}

	private static function getSheduleView ($items, $places)
	{
		$breakTypes = [];

		$breaks = External\BreakTypesTable::query()
			->setSelect(['*'])
			->exec();

		/** @var External\BreakTypesTable $item */
		foreach ($breaks as $item)
			$breakTypes[$item->Short] = $item->BreakName;

		$result = [];

		foreach ($items as $item)
		{
			if (!isset($item['time']))
				continue;

			$row = [
				'day' => (int) $item['day'],
				'time' => date('Y-m-d\TH:i:s', $item['time'] / 1000),
				'break' => null,
				'places' => []
			];

			for ($i = 0; $i < count($places); $i++)
			{
				if (isset($item['pl'.$i]) && $item['pl'.$i] !== '' && !is_numeric($item['pl'.$i]) && isset($breakTypes[$item['pl'.$i]]))
					$item['pl'.$i] = $row['break'] = $breakTypes[$item['pl'.$i]];

				$row['places'][] = $item['pl'.$i] ?? '';
			}

			$result[] = $row;
		}

		return $result;
	}

	private static function getSnakeView ($competitionId, $params, $places)
	{
		$items = External\CompShootersTable::query()
			->setOrder(['GroupNumber' => 'ASC', 'NumberInGroup' => 'ASC'])
			->setSelect([
				'GroupNumber',
				'NumberInGroup',
				'Shooter.FirstName',
				'Shooter.LastName',
				'Shooter.MiddleName',
			])
			->setFilter(['=CompId' => $competitionId])
			->exec();

		$tmp = [];

		/** @var External\CompShootersTable $item */
		foreach ($items as $item)
		{
			$tmp[$item->GroupNumber][] = $item;
		}

		$maxInGroup = 0;

		foreach ($tmp as $members)
			$maxInGroup = max($maxInGroup, count($members));

		$cntPlaces = count($places);

		$result = [];

		foreach ($tmp as $group => $members)
		{
			foreach ($members as $member)
			{
				$row = [
					'group' => $group,
					'shooter' => trim($member->Shooter->LastName.' '.$member->Shooter->FirstName.' '.$member->Shooter->MiddleName),
					'number' => $member->NumberInGroup,
					'places' => [],
				];

				for ($i = 0; $i < $cntPlaces; $i++)
				{
					$index = $i + $group * 1 - 1;
					if ($index > $cntPlaces - 1)
						$index = $index - $cntPlaces;

					$time = $params['startTimeSnake']
						+ (($maxInGroup - 1) * $params['snakeShooterInterval'] + $params['snakeSeriesInterval']) * $i
						+ ($member['NumberInGroup'] - 1) * $params['snakeShooterInterval'];
					$time /= 1000;

					$row['places']['pl'.$index] = date('Y-m-d\TH:i:s', $time);
				}

				ksort($row['places']);

				$result[] = $row;
			}
		}

		return $result;
	}
}