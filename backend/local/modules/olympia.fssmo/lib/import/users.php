<?php

namespace Olympia\Fssmo\Import;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use CRubric;
use CSubscription;
use CUser;
use Olympia\Fssmo\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\User;

class Users
{
	public static function importFromExternal ()
	{
		define('SKIP_USER_ADD_CALLBACK', true);
		define('SKIP_USER_UPDATE_CALLBACK', true);
		define('SKIP_USER_DELETE_CALLBACK', true);

		Loader::includeModule('subscribe');

		$subscribeRubric = CRubric::GetList([], ['CODE' => 'SUBSCRIBE'])->Fetch();

		$countries = GetCountryArray();

		$users = External\UsersTable::query()
			->setOrder(['LastActivityDate' => 'ASC'])
			->setSelect(['*'])
			->setFilter([])
			->exec();

		/** @var External\UsersTable $user */
		foreach ($users as $user)
		{
			if (in_array($user->UserId, ['DD4817B5-6B30-49F8-8AE0-F4F673102898']))
				continue;

			$isExist = UserTable::query()
				->setSelect(['ID'])
				->setFilter(['=XML_ID' => $user->UserId])
				->exec()->fetch();

			if ($isExist)
				continue;

			/** @var External\MembershipTable $member */
			$member = External\MembershipTable::query()
				->setSelect([
					'UserId',
					'Password',
					'Email',
					'IsApproved',
					'CreateDate',
					'LastLoginDate',
					'UserProfiles.FirstName',
					'UserProfiles.LastName',
					'UserProfiles.MiddleName',
					'UserProfiles.Nick',
					'UserProfiles.City',
					'UserProfiles.Phone',
					'UserProfiles.DigitId',
					'UserProfiles.ClubId',
					'UserProfiles.SendNews',
				])
				->setFilter([
					'UserId' => $user->UserId
				])
				->exec()->fetch();

			if ($member)
			{
				$member->UserProfiles->Phone = str_replace([' ', '-', '(', ')'], '', $member->UserProfiles->Phone);
				$member->Email = trim(str_replace([' ', '\t'], '', $member->Email));

				$fields = [
					'LID' => 's1',
					'LOGIN' => $member->Email,
					'EMAIL' => $member->Email,
					'PASSWORD' => $member->Password,
					'PASSWORD_CONFIRM' => $member->Password,
					'XML_ID' => $member->UserId,
					'LAST_LOGIN' => $member->LastLoginDate,
					'DATE_REGISTER' => $member->CreateDate,
					'ACTIVE' => 'Y',
					'TITLE' => $member->UserProfiles->Nick,
					'NAME' => $member->UserProfiles->FirstName,
					'SECOND_NAME' => $member->UserProfiles->MiddleName,
					'LAST_NAME' => $member->UserProfiles->LastName,
					'PERSONAL_CITY' => $member->UserProfiles->City,
					'PERSONAL_PHONE' => $member->UserProfiles->Phone,
					'UF_DIGIT_ID' => $member->UserProfiles->DigitId,
					'UF_CLUB_ID' => $member->UserProfiles->ClubId,
				];

				/** @var External\ShootersTable $shooter */
				$shooter = External\ShootersTable::query()
					->setSelect(['id', 'GenderId', 'BirthDay', 'Country.*'])
					->setFilter(['UserId' => $user->UserId])
					->exec()->fetch();

				if ($shooter)
				{
					$fields['UF_SHOOTER_ID'] = $shooter->id;

					if ($shooter->GenderId == External\ShootersTable::GENDER_MALE)
						$fields['PERSONAL_GENDER'] = 'M';
					if ($shooter->GenderId == External\ShootersTable::GENDER_FEMALE)
						$fields['PERSONAL_GENDER'] = 'F';

					if ($shooter->Country && $shooter->Country->id > 0)
					{
						$k = array_search('Россия', $countries['reference']);

						if ($k > 0)
							$fields['PERSONAL_COUNTRY'] = $countries['reference_id'][$k];
					}

					if ($shooter->BirthDay)
						$fields['PERSONAL_BIRTHDAY'] = $shooter->BirthDay;
				}

				try
				{
					if (!$isExist)
						$id = User::create($fields);
					else
					{
						$id = $isExist['ID'];

						$user = new CUser;
						$user->Update($id, $fields);
					}

					if ($member->UserProfiles->SendNews)
					{
						$subscription = CSubscription::GetByEmail($member->Email)->Fetch();

						if ($subscription)
						{
							$rubrics = CSubscription::GetRubricArray($subscription['ID']);

							if (!in_array($subscribeRubric['ID'], $rubrics))
							{
								$rubrics[] = $subscribeRubric['ID'];

								$subscribe = new CSubscription;
								$subscribe->Update($subscription['ID'], [
									'RUB_ID' => $rubrics
								]);

								if ($subscribe->LAST_ERROR != '')
									throw new Exception($subscribe->LAST_ERROR);
							}
						}
						else
						{
							$subscribe = new CSubscription;
							$subscribe->Add([
								'ACTIVE' => 'Y',
								'EMAIL' => $member->Email,
								'FORMAT' => 'html',
								'CONFIRMED' => 'Y',
								'USER_ID' => $id,
								'SEND_CONFIRM' => 'N',
								'RUB_ID' => [$subscribeRubric['ID']]
							]);

							if ($subscribe->LAST_ERROR != '')
								throw new Exception($subscribe->LAST_ERROR);
						}
					}
				}
				catch (\Exception $e) {
					echo $e->getMessage();
					p($fields);
				}
			}
		}
	}
}