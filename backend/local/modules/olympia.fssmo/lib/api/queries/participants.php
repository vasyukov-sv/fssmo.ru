<?php

namespace Olympia\Fssmo\Api\Queries;

use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model;

class Participants
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [];

		$competition = Model\CompetitionsTable::query()
			->setOrder(['PROPERTY.DATE_FROM' => 'ASC'])
			->setSelect(['ID', 'PROPERTY.EXTERNAL_ID'])
			->setFilter(['=ACTIVE' => 'Y']);

		if (is_numeric($args['competition']))
			$competition->addFilter('=ID', (int) $args['competition']);
		else
			$competition->addFilter('=CODE', trim($args['competition']));

		$competition = $competition->exec()->fetch();
		/** @var Model\CompetitionsTable $competition */

		if (!$competition)
			throw new Exception('competition not found');

		$items = External\RegistredUsersTable::query()
			->setOrder(['RegistrationDate' => 'ASC'])
			->setSelect([
				'id',
				'UserProfile.FirstName',
				'UserProfile.LastName',
				'UserProfile.MiddleName',
				'UserProfile.City',
				'UserProfile.Club.ClubName',
				'UserProfile.Digit.Digit',
			])
			->setFilter([
				'=SiteCompId' => $competition->getProperty('EXTERNAL_ID'),
				'=Refused' => false,
				'=Banned' => false,
			])
			->exec();

		/** @var External\RegistredUsersTable $item */
		foreach ($items as $item)
		{
			$result[] = [
				'name' => trim($item->UserProfile->LastName.' '.$item->UserProfile->FirstName.' '.$item->UserProfile->MiddleName),
				'city' => $item->UserProfile->City,
				'club' => $item->UserProfile->Club->ClubName,
				'digit' => $item->UserProfile->Digit->Digit,
			];
		}

		return $result;
	}
}