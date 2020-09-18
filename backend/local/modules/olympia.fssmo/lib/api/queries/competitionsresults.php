<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model;

class CompetitionsResults
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		global $competitionFilter;

		$items = Model\CompetitionsTable::query()
			->setOrder(['PROPERTY.DATE_FROM' => 'DESC'])
			->setSelect([
				'ID',
				'NAME',
				'DETAIL_PAGE_URL',
				'PROPERTY.LOCATION',
				'PROPERTY.URL',
				'PROPERTY.DATE_FROM',
				'PROPERTY.EXTERNAL_ID',
				'DISCIPLINE_NAME' => 'DISCIPLINE.NAME'
			])
			->setFilter([
				'=ACTIVE' => 'Y',
				'<PROPERTY.DATE_FROM' => date('Y-m-d 00:00:00'),
			])
			->registerRuntimeField((new Reference('DISCIPLINE',
				Model\DisciplinesTable::class,
					Join::on('this.PROPERTY.DISCIPLINE', 'ref.ID')
				))->configureJoinType('left')
			)
			->setLimit($limit)
			->setOffset(($page - 1) * $limit);

		if (isset($competitionFilter) && is_array($competitionFilter) && count($competitionFilter))
		{
			foreach ($competitionFilter as $k => $v)
				$items->addFilter($k, $v);
		}

		$items = $items->exec();

		$compId = [];

		/** @var Model\CompetitionsTable $item */
		foreach ($items as $item)
			$compId[$item->getProperty('EXTERNAL_ID')] = $item;

		$items = External\CompetitionsTable::query()
			->setOrder(['BeginDate' => 'DESC'])
			->setSelect(['id', 'SiteId', 'TargetsCount'])
			->setFilter(['=SiteId' => array_keys($compId)])
			->exec();

		/** @var External\CompetitionsTable[] $externalItems */
		$externalItems = [];

		foreach ($items as $item)
			$externalItems[$item->SiteId] = $item;

		/** @var Model\CompetitionsTable $item */
		foreach ($compId as $externalId => $item)
		{
			$members = 0;

			if (isset($externalItems[$externalId]))
			{
				$members = External\ResultsTable::getCount([
					'CompId' => $externalItems[$externalId]->id
				]);
			}

			$url = $item->DETAIL_PAGE_URL;

			if ($item->getProperty('URL') != '')
				$url = $item->getProperty('URL');

			$location = trim((string) $item->getProperty('LOCATION'));

			if ($location == '-')
				$location = '';

			$row = [
				'id' => $item->ID,
				'title' => $item->NAME,
				'url' => $url,
				'discipline' => $item['DISCIPLINE']->NAME,
				'location' => $location,
				'members' => $members,
				'date' => date('Y-m-d\TH:i:s', strtotime($item->getProperty('DATE_FROM'))),
				'groups' => 0,
				'targets' => isset($externalItems[$externalId]) ? $externalItems[$externalId]->TargetsCount : 0,
				'winner' => null
			];

			if (isset($externalItems[$externalId]))
			{
				$group = External\CompShootersTable::query()
					->setOrder(['GroupNumber' => 'DESC'])
					->setSelect(['GroupNumber'])
					->setFilter([
						'=CompId' => $externalItems[$externalId]->id,
						'>GroupNumber' => 0
					])
					->setCacheTtl(3600)
					->setLimit(1)
					->exec()->fetch();

				if ($group)
					$row['groups'] = (int) $group['GroupNumber'];

				$winner = External\ResultsTable::query()
					->setOrder(['Summ' => 'DESC'])
					->setSelect(['id', 'Summ', 'Shooter.FirstName', 'Shooter.LastName', 'Shooter.ClubId', 'Shooter.Club.ClubName'])
					->setFilter(['CompId' => $externalItems[$externalId]->id])
					->setLimit(1);

				/** @var Model\WinnersTable $w */
				$w = Model\WinnersTable::query()
					->setSelect(['ID', 'PROPERTY.SHOOTER'])
					->setFilter([
						'=PROPERTY.COMPETITION' => $item->ID,
						'!PROPERTY.SHOOTER' => false
					])
					->exec()->fetch();

				if ($w)
					$winner->addFilter('=ShooterId', $w->getProperty('SHOOTER'));

				$winner = $winner->exec()->fetch();

				/** @var External\ResultsTable $winner */
				if ($winner)
				{
					$w = [
						'id' => $winner->id,
						'summ' => $winner->Summ,
						'name' => '',
						'last_name' => '',
						'club' => '',
						'photo' => null
					];

					if ($winner->Shooter)
					{
						$w['name'] = (string) $winner->Shooter->FirstName;
						$w['last_name'] = (string) $winner->Shooter->LastName;

						if ($winner->Shooter->Club)
							$w['club'] = (string) $winner->Shooter->Club->ClubName;
					}

					$row['winner'] = $w;
				}
			}

			$result[] = $row;
		}

		return $result;
	}
}