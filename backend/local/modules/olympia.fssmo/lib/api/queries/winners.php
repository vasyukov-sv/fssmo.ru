<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use CFile;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model;

class Winners
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$items = Model\WinnersTable::query()
			->setOrder(['COMPETITION.PROPERTY.DATE_FROM' => 'DESC'])
			->setSelect([
				'ID', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY.SHOOTER',
				'COMPETITION.PROPERTY.DATE_FROM', 'COMPETITION.PROPERTY.EXTERNAL_ID'
			])
			->setFilter([
				'=ACTIVE' => 'Y',
				'!PROPERTY.SHOOTER' => false,
				'!PROPERTY.COMPETITION' => false,
			])
			->registerRuntimeField((new Reference('COMPETITION',
				Model\CompetitionsTable::class,
					Join::on('this.PROPERTY.COMPETITION', 'ref.ID')
				))->configureJoinType('inner')
			)
			->setLimit(4)
			->exec();

		/** @var Model\WinnersTable $item */
		foreach ($items as $item)
		{
			/** @var External\ResultsTable $res */
			$res = External\ResultsTable::query()
				->setSelect([
					'Summ',
					'Shooter.FirstName', 'Shooter.LastName', 'Shooter.Digit.Digit', 'Shooter.Club.ClubName',
					'Competition.TargetsCount', 'Competition.Discipline.Discipline'
				])
				->setFilter([
					'=Competition.SiteId' => $item->getProperty('EXTERNAL_ID'),
					'=ShooterId' => $item->getProperty('SHOOTER')
				])
				->setLimit(1)
				->exec()->fetch();

			if (!$res)
				continue;

			$image = CFile::ResizeImageGet($item['PREVIEW_PICTURE'], ['width' => 250, 'height' => 250], BX_RESIZE_IMAGE_PROPORTIONAL);

			$row = [
				'id' => (int) $item->ID,
				'date' => date('Y-m-d\TH:i:s', strtotime($item->getProperty('DATE_FROM'))),
				'name' => $res->Shooter->FirstName,
				'last_name' => $res->Shooter->LastName,
				'image' => $image ? $image['src'] : null,
				'discipline' => $res->Competition->Discipline->Discipline,
				'club' => $res->Shooter->Club->ClubName,
				'digit' => $res->Shooter->Digit->Digit,
				'description' => $item['PREVIEW_TEXT'],
				'result' => (int) $res->Summ,
				'result_max' => (int) $res->Competition->TargetsCount,
			];

			$result[] = $row;
		}

		return $result;
	}
}