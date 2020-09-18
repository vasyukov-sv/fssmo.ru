<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use CFile;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Exception;
use Olympia\Fssmo\Model;

class CompetitionWinners
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$competition = Model\CompetitionsTable::query()
			->setSelect(['ID', 'PROPERTY.EXTERNAL_ID', 'PROPERTY.WINNER_GROUPS'])
			->setFilter(['=ACTIVE' => 'Y']);

		if (is_numeric($args['competition']))
			$competition->addFilter('=ID', (int) $args['competition']);
		else
			$competition->addFilter('=CODE', trim($args['competition']));

		$competition = $competition->exec()->fetch();
		/** @var Model\CompetitionsTable $competition */

		if (!$competition)
			throw new Exception('Competition not found #1');

		/** @var External\CompetitionsTable $external */
		$external = External\CompetitionsTable::query()
			->setSelect(['id', 'RatingType'])
			->setFilter(['=SiteId' => (int) $competition->getProperty('EXTERNAL_ID')])
			->setCacheTtl(86400)
			->exec()->fetch();

		if (!$external)
			throw new Exception('Competition not found #2');

		$result = [
			'winner' => null,
			'groups' => [],
		];

		/** @var Model\WinnersTable $winner */
		$winner = Model\WinnersTable::query()
			->setSelect(['ID', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY.SHOOTER',])
			->setFilter([
				'=ACTIVE' => 'Y',
				'!PROPERTY.SHOOTER' => false,
				'=PROPERTY.COMPETITION' => $competition['ID'],
			])
			->setLimit(1)
			->exec()->fetch();

		if ($winner)
		{
			/** @var External\ResultsTable $res */
			$res = External\ResultsTable::query()
				->setSelect([
					'Summ',
					'Shooter.FirstName', 'Shooter.LastName', 'Shooter.Digit.Digit', 'Shooter.Club.ClubName',
					'Competition.TargetsCount'
				])
				->setFilter([
					'=Competition.id' => $external->id,
					'=ShooterId' => $winner->getProperty('SHOOTER')
				])
				->setLimit(1)
				->exec()->fetch();

			if ($res)
			{
				$image = CFile::ResizeImageGet($winner->PREVIEW_PICTURE, ['width' => 400, 'height' => 500], BX_RESIZE_IMAGE_PROPORTIONAL);

				$result['winner'] = [
					'id' => (int) $winner->ID,
					'name' => $res->Shooter->FirstName,
					'last_name' => $res->Shooter->LastName,
					'image' => $image ? $image['src'] : null,
					'club' => $res->Shooter->Club->ClubName,
					'digit' => $res->Shooter->Digit->Digit,
					'description' => $winner->PREVIEW_TEXT,
					'result' => (int) $res->Summ,
					'result_max' => (int) $res->Competition->TargetsCount,
				];
			}
		}

		$items = External\ResultsTable::query()
			->setOrder([
				'RatingGroups' => 'ASC',
				'Place' => 'ASC',
				'Summ' => 'DESC'
			])
			->setSelect([
				'Place',
				'Shooter.Digit.Digit',
				'Shooter.FirstName',
				'Shooter.LastName',
				'Shooter.MiddleName',
				'Shooter.Club.ClubName',
			])
			->setFilter([
				'=CompId' => $external->id
			])
			->registerRuntimeField((new Reference('CompShooter',
					External\CompShootersTable::class,
					Join::on('this.ShooterId', 'ref.ShooterId')->whereColumn('ref.CompId', 'this.CompId')
				))
				->configureJoinType('inner'));

		if ($external->RatingType === 1)
			$items->addSelect('RatingGroup', 'RatingGroups');
		else
			$items->addSelect('CompShooter.Category.CategoryName', 'RatingGroups');

		$items = $items->exec();

		$groups = [];

		foreach ($competition['PROPERTY_WINNER_GROUPS'] as $group)
		{
			$g = unserialize($group);
			$f = CFile::GetFileArray($g['PHOTO']);

			if (!$f)
				continue;

			$groups[$g['GROUP']] = $f['SRC'];
		}

		foreach ($items as $item)
		{
			if (!isset($result['groups'][$item['RatingGroups']]))
			{
				$result['groups'][$item['RatingGroups']] = [
					'image' => $groups[$item['RatingGroups']] ?? null,
					'members' => []
				];
			}

			if (count($result['groups'][$item['RatingGroups']]['members']) >= 6)
				continue;

			$result['groups'][$item['RatingGroups']]['members'][] = [
				'place' => $item->Place,
				'name' => trim($item->Shooter->LastName.' '.$item->Shooter->FirstName.' '.$item->Shooter->MiddleName),
				'club' => $item->Shooter->Club->ClubName,
				'digit' => $item->Shooter->Digit->Digit,
			];
		}

		return $result;
	}
}