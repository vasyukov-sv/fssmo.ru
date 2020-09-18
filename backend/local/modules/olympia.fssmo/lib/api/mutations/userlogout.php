<?php

namespace Olympia\Fssmo\Api\Mutations;

class UserLogout
{
	public static function resolve ()
	{
		global $USER;

		$USER->Logout();

		return true;
	}
}