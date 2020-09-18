<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use CFile;
use CSaleUserAccount;
use CSubscription;
use GraphQL\Type\Definition\ResolveInfo;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model\ServicesTable;

class CurrentUser
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context, ResolveInfo $info)
	{
		if (!is_integer($context['user']))
			return null;

		global $USER;

		$selection = array_keys($info->getFieldSelection());

		Loader::includeModule('subscribe');

		$user = UserTable::getRow([
			'select' => [
				'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL',
				'PERSONAL_PHONE', 'PERSONAL_GENDER',
				'PERSONAL_BIRTHDAY', 'PERSONAL_PHOTO',
				'PERSONAL_CITY', 'UF_DIGIT_ID', 'UF_CLUB_ID',
				'UF_AGREEMENT', 'XML_ID'
			],
			'filter' => ['=ID' => $context['user']]
		]);

		$avatar = null;

		if (in_array('avatar', $selection) && $user['PERSONAL_PHOTO'] > 0)
			$avatar = CFile::ResizeImageGet($user['PERSONAL_PHOTO'], ['width' => 250, 'height' => 250])['src'];

		$admin = $USER->IsAuthorized() && $USER->IsAdmin() && ($USER->GetID() == $user['ID']);

		$budget = null;

		if (in_array('budget', $selection))
		{
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
		}

		$subscription = null;

		if (in_array('subscribe', $selection))
			$subscription = CSubscription::GetByEmail($user['EMAIL'])->Fetch();

		$inClub = null;

		if (in_array('in_club', $selection) && $user['XML_ID'] != '')
		{
			/** @var External\ShootersTable $shooter */
			$shooter = External\ShootersTable::query()
				->setSelect(['id', 'IsInFssmo', 'ExpirationDate'])
				->setFilter(['=UserId' => $user['XML_ID'], '=IsInFssmo' => true])
				->exec()->fetch();

			if ($shooter)
			{
				$renew = strtotime($shooter->ExpirationDate) - time() < 30 * 86400;

				$inClub = [
					'status' => true,
					'renew' => $renew,
					'renew_price' => null,
					'until' => date('Y-m-d\TH:i:s', strtotime($shooter->ExpirationDate)),
				];

				if ($renew)
				{
					$item = ServicesTable::query()
						->setSelect(['ID', 'CODE', 'PRICE_PRICE' => 'PRICE.PRICE', 'PRICE_CURRENCY' => 'PRICE.CURRENCY'])
						->setFilter(['=CODE' => ['FSSMO_MEMBERSHIP_FEE']])
						->exec()->fetch();

					$inClub['renew_price'] = [
						'currency' => $item['PRICE_CURRENCY'],
						'value' => (float) $item['PRICE_PRICE'],
					];
				}
			}
		}

		return [
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
			'in_club' => $inClub,
		];
	}
}