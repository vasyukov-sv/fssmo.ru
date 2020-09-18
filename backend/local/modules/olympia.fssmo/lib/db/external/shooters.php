<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class ShootersTable extends External
{
	const GENDER_MALE = 1;
	const GENDER_FEMALE = 2;

	public $id;
	public $FirstName;
	public $MiddleName;
	public $LastName;
	public $GenderId;
	/** @var DateTime */
	public $BirthDay;
	public $CountryId;
	public $City;
	public $Phone;
	public $Email;
	public $ClubId;
	public $FavNumber;
	public $DisciplineId;
	public $ExeDigitId;
	public $ExeDigitCompetition;
	public $DigitId;
	public $DigitOrder;
	public $IsInFssmo;
	public $RatingGroup;
	public $ToPprint;
	public $UserId;
	public $FssmoRegDate;
	public $FssmoCardNumber;
	public $FssmoCardStatusID;
	public $Passport;
	public $ExpirationDate;

	/** @var ClubsTable */
	public $Club;
	/** @var CountriesTable */
	public $Country;
	/** @var DigitsTable */
	public $Digit;

	public static function getTableName()
	{
		return 'Shooters';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('FirstName'),
			new Fields\StringField('MiddleName'),
			new Fields\StringField('LastName'),
			new Fields\IntegerField('GenderId'),
			new Fields\DateField('BirthDay'),
			new Fields\IntegerField('CountryId'),
			new Fields\StringField('City'),
			new Fields\StringField('Phone'),
			new Fields\StringField('Email'),
			new Fields\IntegerField('ClubId'),
			new Fields\IntegerField('FavNumber'),
			new Fields\IntegerField('DisciplineId'),
			new Fields\IntegerField('ExeDigitId'),
			new Fields\StringField('ExeDigitCompetition'),
			new Fields\IntegerField('DigitId'),
			new Fields\StringField('DigitOrder'),
			new Fields\BooleanField('IsInFssmo', [
				'values' => [0, 1]
			]),
			new Fields\StringField('RatingGroup'),
			new Fields\IntegerField('ToPprint'),
			new Fields\StringField('UserId'),
			new Fields\DatetimeField('FssmoRegDate'),
			new Fields\StringField('FssmoCardNumber'),
			new Fields\IntegerField('FssmoCardStatusID'),
			new Fields\StringField('Passport'),
			new Fields\DatetimeField('ExpirationDate'),

			(new Fields\Relations\Reference(
				'Digit',
				DigitsTable::class,
				Join::on('this.DigitId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Club',
				ClubsTable::class,
				Join::on('this.ClubId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Country',
				CountriesTable::class,
				Join::on('this.CountryId', 'ref.id')
			))->configureJoinType('left'),
		];
	}
}