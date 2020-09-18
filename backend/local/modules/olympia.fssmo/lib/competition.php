<?php

namespace Olympia\Fssmo;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Db\External\RegistredUsersTable;
use Olympia\Fssmo\Db\External\UserProfilesTable;

class Competition
{
	public static function getExternalId ($code)
	{
		$siteId = self::getExternalSiteId($code);

		/** @var External\CompetitionsTable $external */
		$external = External\CompetitionsTable::query()
			->setSelect(['id'])
			->setFilter(['=SiteId' => $siteId])
			->setCacheTtl(86400)
			->exec()->fetch();

		if (!$external)
			throw new Exception('Competition not found');

		return (int) $external->id;
	}

	public static function getExternalSiteId ($code)
	{
		$competition = Model\CompetitionsTable::query()
			->setSelect(['ID', 'PROPERTY.EXTERNAL_ID'])
			->setFilter(['=ACTIVE' => 'Y'])
			->cacheJoins(true)
			->setCacheTtl(86400);

		if (is_numeric($code))
			$competition->addFilter('=ID', (int) $code);
		else
			$competition->addFilter('=CODE', trim($code));

		$competition = $competition->exec()->fetch();
		/** @var Model\CompetitionsTable $competition */

		if (!$competition)
			throw new Exception('Competition not found');

		return (int) $competition->getProperty('EXTERNAL_ID');
	}

	public static function registration (int $competitionId, int $userId, array $data)
	{
		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $userId])
			->exec()->fetch();

		if (!$user)
			throw new Exception('user not found');

		$siteId = self::getExternalSiteId($competitionId);

		$connection = Application::getConnection(RegistredUsersTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			$result = RegistredUsersTable::add([
				'UserId' => $user['XML_ID'],
				'SiteCompId' => $siteId,
				'RegistrationDate' => new DateTime(),
				'CategoryId' => null,
				'DisciplineId' => null,
			]);

			if (!$result->isSuccess())
				throw new Exception('Произошла ошибка при регистрации');

			$profile = UserProfilesTable::query()
				->setSelect(['ID'])
				->setFilter(['UserId' => $user['XML_ID']])
				->exec()->fetch();

			if (!$profile)
			{
				$result = UserProfilesTable::add([
					'UserId' => $user['XML_ID']
				]);

				if (!$result->isSuccess())
					throw new Exception('Произошла ошибка при регистрации');

				$profile = UserProfilesTable::query()
					->setSelect(['ID'])
					->setFilter(['UserId' => $user['XML_ID']])
					->exec()->fetch();
			}

			$data['club'] = max(1, (int) ($data['club'] ?? 1));
			$data['digit'] = max(1, (int) ($data['digit'] ?? 1));

			$result = UserProfilesTable::update($profile['id'], [
				'ClubId' => $data['club'],
				'DigitId' => $data['digit'],
				'FirstName' => $data['name'] ?? '',
				'LastName' => $data['last_name'] ?? '',
				'MiddleName' => $data['middle_name'] ?? '',
				'Phone' => $data['phone'] ?? '',
				'City' => $data['city'] ?? '',
			]);

			if (!$result->isSuccess())
				throw new Exception('Произошла ошибка при регистрации');

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}
	}
}