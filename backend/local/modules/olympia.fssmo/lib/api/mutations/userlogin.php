<?php

namespace Olympia\Fssmo\Api\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Api\Queries\CurrentUser;

class UserLogin
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context, ResolveInfo $info)
	{
		global $USER;

		if ($USER->IsAuthorized())
			throw new Exception('Вы уже авторизованы');

		$remember = isset($args['remember']) && $args['remember'] ? 'Y' : 'N';

		$auth = $USER->Login($args['login'], $args['password'], $remember);

		if ($auth !== true)
			throw new Exception(strip_tags($auth['MESSAGE']));

		$context['user'] = (int) $USER->GetID();

		return CurrentUser::resolve($value, [], $context, $info);
	}
}