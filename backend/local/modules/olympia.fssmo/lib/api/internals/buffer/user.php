<?php

namespace Olympia\Fssmo\Api\Internals\Buffer;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use CSaleUserAccount;
use CSubscription;

class User extends Base
{
	static $ids = [];
	static $items = false;

	public static function load ()
	{
		if (self::$items === false && !count(self::$ids))
			self::$items = [];

		if (self::$items === false)
		{
			global $USER;

			Loader::includeModule('subscribe');

			$users = UserTable::getList([
				'select' => [
					'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL',
					'PERSONAL_PHONE', 'PERSONAL_GENDER',
					'PERSONAL_BIRTHDAY', 'PERSONAL_PHOTO',
					'PERSONAL_CITY', 'UF_DIGIT_ID', 'UF_CLUB_ID',
					'UF_AGREEMENT'
				],
				'filter' => ['=ID' => self::$ids]
			]);

			foreach ($users as $user)
			{
				$avatar = null;

				if ($user['PERSONAL_PHOTO'] > 0)
					$avatar = \CFile::ResizeImageGet($user['PERSONAL_PHOTO'], ['width' => 250, 'height' => 250])['src'];

				$subscription = CSubscription::GetByEmail($user['EMAIL'])->Fetch();

				$admin = $USER->IsAuthorized() && $USER->IsAdmin() && ($USER->GetID() == $user['ID']);

				$budget = null;

				$userAccount = CSaleUserAccount::GetByUserID($user['ID'], CurrencyManager::getBaseCurrency());

				if ($userAccount)
				{
					$budget = [
						'id' => (int) $userAccount['ID'],
						'value' => (float) $userAccount['CURRENT_BUDGET'],
						'currency' => $userAccount['CURRENCY'],
						'locked' => $userAccount['LOCKED'] == 'Y'
					];
				}

				self::$items[$user['ID']] = [
					'id' => $user['ID'],
					'name' => (string) $user['NAME'],
					'last_name' => (string) $user['LAST_NAME'],
					'middle_name' => (string) $user['SECOND_NAME'],
					'phone' => (string) $user['PERSONAL_PHONE'],
					'email' => (string) $user['EMAIL'],
					'digit' => (int) $user['UF_DIGIT_ID'],
					'club' => (int) $user['UF_CLUB_ID'],
					'city' => (string) $user['PERSONAL_CITY'],
					'birthday' => $user['PERSONAL_BIRTHDAY'] instanceof Date ? $user['PERSONAL_BIRTHDAY']->format('c') : null,
					'sex' => (string) $user['PERSONAL_GENDER'],
					'avatar' => $avatar,
					'subscribe' => $subscription && $subscription['ACTIVE'] === 'Y' ? true : false,
					'agreement' => $user['UF_AGREEMENT'] ? true : false,
					'admin' => $admin,
					'budget' => $budget,
				];
			}
		}
	}
}