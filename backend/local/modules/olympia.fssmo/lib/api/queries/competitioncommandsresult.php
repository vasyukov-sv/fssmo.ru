<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo;
use Olympia\Fssmo\Db\External;

class CompetitionCommandsResult
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$externalId = Fssmo\Competition::getExternalId($args['competition']);

		$items = External\CommandsTable::query()
			->setSelect([
				'CommandName',
				'Shooter.Digit.Digit',
				'Shooter.FirstName',
				'Shooter.LastName',
				'Shooter.MiddleName',
				'Shooter.Club.ClubName',
				'Result.s1', 'Result.s2', 'Result.s3', 'Result.s4', 'Result.s5', 'Result.s6', 'Result.s7', 'Result.s8', 'Result.Summ', 'Result.Place',
				'CompShooter.Number'
			])
			->setFilter(['=CompId' => $externalId])
			->registerRuntimeField(
				(new Reference('Result',
						External\ResultsTable::class,
						Join::on('this.ShooterId', 'ref.ShooterId')->whereColumn('ref.CompId', 'this.CompId')
					))
					->configureJoinType('inner')
			)
			->registerRuntimeField(
				(new Reference('CompShooter',
						External\CompShootersTable::class,
						Join::on('this.ShooterId', 'ref.ShooterId')->whereColumn('ref.CompId', 'this.CompId')
					))
					->configureJoinType('inner')
			)
			->exec();

		$commands = [];

		/** @var External\CommandsTable $item */
		foreach ($items as $item)
		{
			if (!isset($commands[$item->CommandName]))
				$commands[$item->CommandName] = [];

			$commands[$item->CommandName][] = $item;
		}

		foreach ($commands as $name => $members)
		{
			$row = [
				'command' => $name,
				'summ' => 0,
				'participants' => []
			];

			foreach ($members as $member)
			{
				$r = [
					'number' => $member['CompShooter']->Number,
					'place' => $member->Result->Place,
					'name' => trim($member->Shooter->LastName.' '.$member->Shooter->FirstName.' '.$member->Shooter->MiddleName),
					'summ' => $member->Result->Summ,
					'club' => $member->Shooter->Club->ClubName,
					'digit' => $member->Shooter->Digit->Digit,
					'stands' => []
				];

				for ($i = 1; $i <= 8; $i++)
				{
					if ((int) $member->Result->{'s'.$i} > 0)
						$r['stands'][$i] = $member->Result->{'s'.$i};
				}

				$row['summ'] += $r['summ'];
				$row['participants'][] = $r;
			}

			usort($row['participants'], function ($a, $b) {
				return $a['summ'] < $b['summ'];
			});

			$result[] = $row;
		}

		usort($result, function ($a, $b) {
			return $a['summ'] < $b['summ'];
		});

		return $result;
	}
}