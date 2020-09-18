<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use CFile;
use CRubric;
use CSubscription;
use GraphQL\Deferred;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Api\Internals\Buffer;
use Olympia\Fssmo\User;

class UserUpdate
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args)
	{
		global $USER;

		if (!$USER->IsAuthorized())
			throw new Exception('Необходима авторизация');

		$data = $args['user'];

		$oldFields = UserTable::query()
			->setSelect(['ID', 'UF_CLUB_ID'])
			->setFilter(['=ID' => (int) $USER->GetID()])
			->exec()->fetch();

		$fields = [];

		if (isset($data['name']) && $data['name'] != '')
			$fields['NAME'] = trim($data['name']);

		if (isset($data['last_name']) && $data['last_name'] != '')
			$fields['LAST_NAME'] = trim($data['last_name']);

		if (isset($data['email']) && $data['email'] != '')
			$fields['EMAIL'] = $fields['LOGIN'] = trim($data['email']);

		if (isset($data['phone']) && $data['phone'] != '')
			$fields['PERSONAL_PHONE'] = trim($data['phone']);

		if (isset($data['city']) && $data['city'] != '')
			$fields['PERSONAL_CITY'] = trim($data['city']);

		if (isset($data['club']) && $data['club'] != '' && (int) $oldFields['UF_CLUB_ID'] <= 1)
			$fields['UF_CLUB_ID'] = (int) $data['club'];

		if (isset($data['digit']) && $data['digit'] != '')
			$fields['UF_DIGIT_ID'] = (int) $data['digit'];

		if (isset($data['agreement']) && is_bool($data['agreement']))
			$fields['UF_AGREEMENT'] = $data['agreement'] === true ? 1 : 0;

		if (isset($data['birthday']) && $data['birthday'] != '')
		{
			$birthday = new \DateTime($data['birthday']);

			if ($birthday)
				$fields['PERSONAL_BIRTHDAY'] = $birthday->format('d.m.Y');
		}

		if (isset($data['password_old']) && $data['password_old'] != '')
		{
			if ($data['password'] == '' || $data['password_confirm'] == '')
				throw new Exception('Введите новый пароль');

			$isValid = User::isUserPassword($USER->GetID(), $data['password_old']);

			if (!$isValid)
				throw new Exception('Введен неправильный пароль');

			$fields['PASSWORD'] = $data['password'];
			$fields['CONFIRM_PASSWORD'] = $data['password_confirm'];
		}

		if (isset($data['avatar']) && $data['avatar'] != '')
		{
			$photoData = $data['avatar'];
			$photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
			$photoData = str_replace(' ', '+', $photoData);

			if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/upload/avatars/'))
				mkdir($_SERVER['DOCUMENT_ROOT'].'/upload/avatars/');

			$filePath = '/upload/avatars/'.$USER->GetID().'_'.uniqid().'.jpg';

			$data = base64_decode($photoData);

			$move = file_put_contents($_SERVER['DOCUMENT_ROOT'].$filePath, $data);

			if (!$move)
				throw new Exception('Не удалось загрузить изображение');

			$fields['PERSONAL_PHOTO'] = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].$filePath);
		}

		if (count($fields))
		{
			$result = $USER->Update($USER->GetID(), $fields);

			if (!$result)
				throw new Exception($USER->LAST_ERROR);

			if (isset($data['subscribe']) && is_bool($data['subscribe']))
			{
				Loader::includeModule('subscribe');

				$subscription = CSubscription::GetByEmail($fields['EMAIL'])->Fetch();

				if (!$data['subscribe'] && $subscription)
				{
					$subscribe = new CSubscription;
					$subscribe->Update($subscription['ID'], ['ACTIVE' => 'N']);
				}
				elseif ($data['subscribe'] && $subscription && $subscription['ACTIVE'] === 'N')
				{
					$subscribe = new CSubscription;
					$subscribe->Update($subscription['ID'], ['ACTIVE' => 'Y']);
				}
				elseif ($data['subscribe'] && !$subscription)
				{
					$subscribeRubric = CRubric::GetList([], ['CODE' => 'SUBSCRIBE'])->Fetch();

					$subscribe = new CSubscription;
					$subscribe->Add([
						'ACTIVE' => 'Y',
						'EMAIL' => $fields['EMAIL'],
						'FORMAT' => 'html',
						'CONFIRMED' => 'Y',
						'USER_ID' => $USER->GetID(),
						'SEND_CONFIRM' => 'N',
						'RUB_ID' => [$subscribeRubric['ID']]
					]);
				}
			}
		}

		Buffer\User::add($USER->GetID());

		return new Deferred(function () use ($USER)
		{
			return Buffer\User::get($USER->GetID());
		});
	}
}