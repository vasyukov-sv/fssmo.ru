<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;

class ClubsTable extends External
{
	public $id;
	public $ClubName;
	public $Address;
	public $TownId;
	public $CountryId;

	/** @var CountriesTable */
	public $Country;

	/** @var TownTable */
	public $Town;

	public static function getTableName()
	{
		return 'Clubs';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('ClubName'),
			new Fields\StringField('Address'),
			new Fields\IntegerField('TownId'),
			new Fields\IntegerField('CountryId'),

			(new Fields\Relations\Reference(
				'Country',
				CountriesTable::class,
				Join::on('this.CountryId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Town',
				TownTable::class,
				Join::on('this.TownId', 'ref.id')
			))->configureJoinType('left'),
		];
	}
}