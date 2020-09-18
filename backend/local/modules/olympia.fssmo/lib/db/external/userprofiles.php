<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;

class UserProfilesTable extends External
{
	public $id;
	public $UserId;
	public $FirstName;
	public $LastName;
	public $MiddleName;
	public $ActiveCompId;
	public $Nick;
	public $SendNews;
	public $City;
	public $Phone;
	public $DigitId;
	public $ClubId;

	/** @var ClubsTable */
	public $Club;
	/** @var DigitsTable */
	public $Digit;

	public static function getTableName()
	{
		return 'UserProfiles';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('UserId'),
			new Fields\StringField('FirstName', [
				'default_value' => null
			]),
			new Fields\StringField('LastName', [
				'default_value' => null
			]),
			new Fields\StringField('MiddleName', [
				'default_value' => null
			]),
			new Fields\IntegerField('ActiveCompId', [
				'default_value' => null
			]),
			new Fields\StringField('Nick', [
				'default_value' => null
			]),
			new Fields\BooleanField('SendNews', [
				'values' => [0, 1],
				'default_value' => 0
			]),
			new Fields\StringField('City', [
				'default_value' => null
			]),
			new Fields\StringField('Phone', [
				'default_value' => null
			]),
			new Fields\IntegerField('DigitId', [
				'default_value' => null
			]),
			new Fields\IntegerField('ClubId', [
				'default_value' => null
			]),

			(new Fields\Relations\Reference(
				'Club',
				ClubsTable::class,
				Join::on('this.ClubId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Digit',
				DigitsTable::class,
				Join::on('this.DigitId', 'ref.id')
			))->configureJoinType('left'),
		];
	}
}