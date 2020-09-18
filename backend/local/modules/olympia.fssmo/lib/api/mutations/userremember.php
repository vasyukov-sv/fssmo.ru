<?php

namespace Olympia\Fssmo\Api\Mutations;

use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\User\Bitrix;

class UserRemember
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args)
	{
		$result = Bitrix::sendPassword($args['login'], '');

		if ($result['TYPE'] !== 'OK')
			throw new Exception(strip_tags($result['MESSAGE']));

		return strip_tags($result['MESSAGE']);
	}
}