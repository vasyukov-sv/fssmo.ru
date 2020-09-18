<?php

namespace Olympia\Fssmo;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use CUser;
use Olympia\Fssmo\Db\External;

class User
{
	public static function create ($data, $sendNotify = false)
	{
		$arDefaultGroup = [];
		$defaultGroup = Option::get('main', 'new_user_registration_def_group', '');

		if ($defaultGroup != '')
		{
			$arDefaultGroup = explode(",", $defaultGroup);
			$arPolicy = CUser::GetGroupPolicy($arDefaultGroup);
		}
		else
			$arPolicy = CUser::GetGroupPolicy(array());

		$passwordMinLength = (int) $arPolicy["PASSWORD_LENGTH"];

		if ($passwordMinLength <= 0)
			$passwordMinLength = 6;

		$passwordChars = ['abcdefghijklnmopqrstuvwxyz', 'ABCDEFGHIJKLNMOPQRSTUVWXYZ', '0123456789'];

		if ($arPolicy['PASSWORD_PUNCTUATION'] === 'Y')
			$passwordChars[] = ",.<>/?;:'\"[]{}\|`~!@#\$%^&*()-_+=";

		$autoPassword = randString($passwordMinLength + 2, $passwordChars);

		$fields = [
			'LOGIN' => $data['EMAIL'],
			'PASSWORD' => $autoPassword,
			'PASSWORD_CONFIRM' => $autoPassword,
			'GROUP_ID' => $arDefaultGroup,
			'LID' => SITE_ID,
			'LANGUAGE_ID' => LANGUAGE_ID
		];

		foreach ($data as $key => $value)
			$fields[$key] = $value;

		$user = new CUser;
		$userId = $user->Add($fields);

		if (intval($userId) <= 0)
			throw new Exception($user->LAST_ERROR);

		$fields['ID'] = (int) $userId;

		if ($sendNotify)
		{
			Event::send([
				'EVENT_NAME' => 'USER_CREATE',
				'C_FIELDS' => $fields,
				'LID' => $fields['LID'],
				'DUPLICATE' => "N",
				'LANGUAGE_ID' => LANGUAGE_ID
			]);
		}

		return $fields['ID'];
	}

	public static function isUserPassword ($userId, $password)
	{
		$userData = CUser::GetByID($userId)->Fetch();

		$salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));

		$realPassword = substr($userData['PASSWORD'], -32);
		$password = md5($salt.$password);

		return ($password == $realPassword);
	}

	public static function deleteExternalUser ($guid)
	{
		if (!Helpers::isGUID($guid))
			throw new Exception('Invalid GUID');

		$connection = Application::getConnection(External\ApplicationsTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			External\UsersInRolesTable::delete($guid);

			$profiles = External\UserProfilesTable::query()
				->setSelect(['id'])
				->setFilter(['UserId' => $guid])
				->exec();

			/** @var External\UserProfilesTable $profile */
			foreach ($profiles as $profile)
				External\UserProfilesTable::delete($profile->id);

			External\MembershipTable::delete($guid);
			External\UsersTable::delete($guid);

			$shooters = External\ShootersTable::query()
				->setSelect(['id'])
				->setFilter(['UserId' => $guid])
				->exec();

			/** @var External\ShootersTable $shooter */
			foreach ($shooters as $shooter)
				External\ShootersTable::update($shooter->id, ['UserId' => null]);

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}
	}

	public static function updateExternalUser ($guid, $fields)
	{
		if (!Helpers::isGUID($guid))
			throw new Exception('Invalid GUID');

		$connection = Application::getConnection(External\ApplicationsTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			/** @var External\UserProfilesTable $userProfile */
			$userProfile = External\UserProfilesTable::query()
				->setSelect(['id'])
				->setFilter(['UserId' => $guid])
				->exec()->fetch();

			if ($userProfile)
			{
				External\UserProfilesTable::update($userProfile->id, [
					'FirstName' => $fields['NAME'] ?: null,
					'LastName' => $fields['LAST_NAME'] ?: null,
					'Phone' => $fields['PERSONAL_PHONE'] ?: null,
					'City' => $fields['PERSONAL_CITY'] ?: null,
					'DigitId' => $fields['UF_DIGIT_ID'] ?: null,
					'ClubId' => $fields['UF_CLUB_ID'] ?: null,
				]);
			}

			if (isset($fields['EMAIL']) && filter_var($fields['EMAIL'], FILTER_VALIDATE_EMAIL))
			{
				External\MembershipTable::update($guid, [
					'Email' => $fields['EMAIL'],
					'LoweredEmail' => mb_strtolower($fields['EMAIL']),
				]);

				External\UsersTable::update($guid, [
					'UserName' => $fields['EMAIL'],
					'LoweredUserName' => mb_strtolower($fields['EMAIL']),
				]);
			}

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}
	}

	public static function createExternalUser ($fields)
	{
		if (!isset($fields['ID']) || !(int) $fields['ID'])
			throw new Exception('Invalid internal id');

		$userData = UserTable::query()
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'PERSONAL_PHONE',
				'PERSONAL_CITY',
			])
			->setFilter(['ID' => $fields['ID']])
			->exec()->fetch();

		if (!$userData)
			throw new Exception('Internal user not found');

		/** @var External\ApplicationsTable $application */
		$application = External\ApplicationsTable::query()
			->setSelect(['ApplicationId'])
			->exec()->fetch();

		$connection = Application::getConnection(External\ApplicationsTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			$result = External\UsersTable::add([
				'ApplicationId' => $application->ApplicationId,
				'UserName' => $fields['EMAIL'],
				'LoweredUserName' => mb_strtolower($fields['EMAIL']),
				'LastActivityDate' => DateTime::createFromTimestamp(time())
			]);

			$userGuid = false;

			if ($result->isSuccess())
			{
				/** @var External\UsersTable $r */
				$r = External\UsersTable::query()
					->setSelect(['UserId'])
					->setFilter(['UserName' => $fields['EMAIL']])
					->exec()->fetch();

				if ($r)
					$userGuid = $r->UserId;
			}

			if (!$userGuid)
				return;

			$result = External\MembershipTable::add([
				'ApplicationId' => $application->ApplicationId,
				'UserId' => $userGuid,
				'Email' => $fields['EMAIL'],
				'LoweredEmail' => mb_strtolower($fields['EMAIL']),
				'Password' => $fields['PASSWORD'],
				'CreateDate' => DateTime::createFromTimestamp(time()),
				'LastLoginDate' => DateTime::createFromTimestamp(time()),
			]);

			if ($result->isSuccess())
			{
				/** @var External\RolesTable $role */
				$role = External\RolesTable::query()
					->setSelect(['RoleId'])
					->setFilter(['ApplicationId' => $application->ApplicationId, 'RoleName' => 'Users'])
					->exec()->fetch();

				External\UsersInRolesTable::add([
					'UserId' => $userGuid,
					'RoleId' => $role->RoleId,
				]);

				External\UserProfilesTable::add([
					'UserId' => $userGuid,
					'FirstName' => $userData['NAME'] ?: null,
					'LastName' => $userData['LAST_NAME'] ?: null,
					'MiddleName' => $userData['SECOND_NAME'] ?: null,
					'Phone' => $userData['PERSONAL_PHONE'] ?: null,
					'City' => $userData['PERSONAL_CITY'] ?: null,
					'DigitId' => 1,
					'ClubId' => 1,
				]);
			}

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}

		define('SKIP_USER_UPDATE_CALLBACK', true);

		$cUser = new CUser();
		$cUser->Update($fields['ID'], [
			'XML_ID' => $userGuid,
		]);
	}

	public static function createOrGetShooter (string $guid): ?int
	{
		if (!Helpers::isGUID($guid))
			throw new Exception('Invalid GUID');

		/** @var External\ShootersTable $shooter */
		$shooter = External\ShootersTable::query()
			->setSelect(['id'])
			->setFilter(['=UserId' => $guid])
			->exec()->fetch();

		if ($shooter)
			return (int) $shooter->id;

		$result = null;

		$connection = Application::getConnection(External\ApplicationsTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			/** @var External\UserProfilesTable $userProfile */
			$userProfile = External\UserProfilesTable::query()
				->setSelect(['*'])
				->setFilter(['=UserId' => $guid])
				->exec()->fetch();

			if ($userProfile)
			{
				/** @var External\ShootersTable $findShooter */
				$findShooter = External\ShootersTable::query()
					->setSelect(['id'])
					->setFilter([
						'=FirstName' => $userProfile->FirstName,
						'=MiddleName' => $userProfile->MiddleName,
						'=LastName' => $userProfile->LastName,
						'=City' => $userProfile->City,
						'=UserId' => false,
					])
					->exec()->fetch();

				if ($findShooter)
				{
					$r = External\ShootersTable::update($findShooter['id'], [
						'UserId' => $guid,
					]);
				}
				else
				{
					$r = External\ShootersTable::add([
						'FirstName' => $userProfile->FirstName,
						'MiddleName' => $userProfile->MiddleName,
						'LastName' => $userProfile->LastName,
						'City' => $userProfile->City,
						'Phone' => $userProfile->Phone,
						'DigitId' => $userProfile->DigitId ?? 1,
						'ClubId' => $userProfile->ClubId ?? 1,
						'UserId' => $guid,
					]);
				}

				if ($r->isSuccess())
					$result = (int) $r->getId();
				else
					throw new Exception(implode(', ', $r->getErrorMessages()));
			}

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}

		return $result;
	}
}