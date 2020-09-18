<?php

namespace Olympia\Fssmo\Api\Queries;

use Olympia\Fssmo\Competition;
use Olympia\Fssmo\Db\External;

class CompetitionGroups
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$externalId = Competition::getExternalId($args['competition']);

		$items = External\CompShootersTable::query()
			->setOrder(['GroupNumber' => 'ASC', 'NumberInGroup' => 'ASC'])
			->setSelect([
				'GroupNumber',
				'NumberInGroup',
				'Shooter.FirstName',
				'Shooter.LastName',
				'Shooter.MiddleName',
			])
			->setFilter(['=CompId' => $externalId])
			->exec();

		$tmp = [];

		/** @var External\CompShootersTable $item */
		foreach ($items as $item)
		{
			$tmp[$item->GroupNumber][] = $item;
		}

		foreach ($tmp as $group => $members)
		{
			$row = [
				'number' => $group,
				'participants' => []
			];

			/** @var External\CompShootersTable $member */
			foreach ($members as $member)
			{
				$row['participants'][] = [
					'number' => $member->NumberInGroup,
					'name' => trim($member->Shooter->LastName.' '.$member->Shooter->FirstName.' '.$member->Shooter->MiddleName)
				];
			}

			$result[] = $row;
		}

		return $result;
	}
}