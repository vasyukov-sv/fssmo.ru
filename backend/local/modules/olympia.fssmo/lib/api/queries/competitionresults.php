<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo;

class CompetitionResults
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$externalId = Fssmo\Competition::getExternalId($args['competition']);

		/** @var External\CompetitionsTable $comp */
		$comp = External\CompetitionsTable::query()
			->setSelect(['RatingType'])
			->setFilter(['=id' => $externalId])
			->exec()->fetch();

		$items = External\ResultsTable::query()
			->setOrder([
				'RatingGroups' => 'ASC',
				'Place' => 'ASC',
				'Summ' => 'DESC'
			])
			->setSelect([
				's1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 'Summ', 'Place',
				'Digit.Digit',
				'Shooter.Digit.Digit',
				'Shooter.FirstName',
				'Shooter.LastName',
				'Shooter.MiddleName',
				'Shooter.Club.ClubName',
				'Shooter.City',
				'Shooter.Country.Country',
				'CompShooter.Number',
			])
			->setFilter([
				'=CompId' => $externalId
			])
			->registerRuntimeField(
				(new Reference('CompShooter',
						External\CompShootersTable::class,
						Join::on('this.ShooterId', 'ref.ShooterId')->whereColumn('ref.CompId', 'this.CompId')
					))
					->configureJoinType('inner')
			);

		if ($comp->RatingType === 1)
			$items->addSelect('RatingGroup', 'RatingGroups');
		else
			$items->addSelect('CompShooter.Category.CategoryName', 'RatingGroups');

		$items = $items->exec();

		/** @var External\ResultsTable $item */
		foreach ($items as $item)
		{
			$row = [
				'place' => $item->Place,
				'group' => $item['RatingGroups'],
				'number' => $item['CompShooter']->Number > 0 ? $item['CompShooter']->Number : null,
				'name' => trim($item->Shooter->LastName.' '.$item->Shooter->FirstName.' '.$item->Shooter->MiddleName),
				'summ' => $item->Summ,
				'country' => $item->Shooter->Country->Country,
				'city' => $item->Shooter->City,
				'club' => $item->Shooter->Club->ClubName,
				'digit' => $item->Shooter->Digit->Digit,
				'digitNew' => $item->Digit->Digit,
				'stands' => []
			];

			for ($i = 1; $i <= 8; $i++)
			{
				if ((int) $item->{'s'.$i} > 0)
					$row['stands'][$i] = $item->{'s'.$i};
			}

			$result[] = $row;
		}

		return $result;
	}
}