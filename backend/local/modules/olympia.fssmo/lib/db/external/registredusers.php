<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class RegistredUsersTable extends External
{
	public $id;
	public $UserId;
	public $SiteCompId;
	/** @var DateTime */
	public $RegistrationDate;
	public $Refused;
	public $Banned;
	public $CategoryId;
	public $DisciplineId;

	/** @var UserProfilesTable */
	public $UserProfile;

	public static function getTableName()
	{
		return 'RegistredUsers';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('UserId'),
			new Fields\IntegerField('SiteCompId'),
			new Fields\DatetimeField('RegistrationDate'),
			new Fields\BooleanField('Refused', [
				'values' => [0, 1],
				'default_value' => null
			]),
			new Fields\BooleanField('Banned', [
				'values' => [0, 1],
				'default_value' => null
			]),
			new Fields\IntegerField('CategoryId'),
			new Fields\IntegerField('DisciplineId'),

			(new Fields\Relations\Reference(
				'UserProfile',
				UserProfilesTable::class,
				Join::on('this.UserId', 'ref.UserId')
			))->configureJoinType('inner'),

			(new Fields\Relations\Reference(
				'Competition',
				CompetitionsCalendarTable::class,
				Join::on('this.SiteCompId', 'ref.id')
			))->configureJoinType('inner'),
		];
	}
}