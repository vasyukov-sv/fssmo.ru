<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class UsersTable extends External
{
	public $UserId;
	public $ApplicationId;
	public $UserName;
	public $LoweredUserName;
	public $MobileAlias;
	public $IsAnonymous;
	public $LastActivityDate;

	public static function getTableName()
	{
		return 'aspnet_Users';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('UserId', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('ApplicationId', [
				'required' => true
			]),
			new Fields\StringField('UserName', [
				'required' => true
			]),
			new Fields\StringField('LoweredUserName', [
				'required' => true
			]),
			new Fields\StringField('MobileAlias', [
				'default_value' => null
			]),
			new Fields\BooleanField('IsAnonymous', [
				'values' => [0, 1],
				'default_value' => 0
			]),
			new Fields\DatetimeField('LastActivityDate', [
				'default_value' => new DateTime()
			]),
		];
	}
}