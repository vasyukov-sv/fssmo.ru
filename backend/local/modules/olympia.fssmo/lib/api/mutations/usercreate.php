<?php

namespace Olympia\Fssmo\Api\Mutations;

use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\User;

class UserCreate
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args)
	{
		global $USER;

		if ($USER->IsAuthorized())
			throw new Exception('Вы уже авторизованы');

		$fields = $args['user'];

		$userId = User::create([
			'LOGIN' => $fields['email'],
			'EMAIL' => $fields['email'],
			'NAME' => $fields['name'] ?? '',
			'LAST_NAME' => $fields['last_name'] ?? '',
			'PERSONAL_PHONE' => $fields['phone'] ?? '',
			'PASSWORD' => $fields['password'],
			'PASSWORD_CONFIRM' => $fields['password_confirm'],
			'UF_CLUB_ID' => 1,
			'UF_DIGIT_ID' => 1,
		], true);

		$USER->Authorize($userId);

		return [
			'status' => true,
			'user' => (int) $USER->GetID()
		];
	}
}