<?php

namespace Olympia\Fssmo\Api\Mutations;

use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\User\Bitrix;

class UserUpdatePassword
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args)
	{
		global $USER;

		if ($USER->IsAuthorized())
			throw new Exception('Вы авторизованы');

		$fields = $args['data'];

		$result = Bitrix::changePassword($fields['login'], $fields['checkword'], $fields['password'], $fields['confirm']);

		if ($result['TYPE'] !== 'OK')
			throw new Exception(strip_tags($result['MESSAGE']));

		return true;
	}
}