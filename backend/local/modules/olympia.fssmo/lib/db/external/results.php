<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;

class ResultsTable extends External
{
	public $id;
	public $CompId;
	public $ShooterId;
	public $s1;
	public $s2;
	public $s3;
	public $s4;
	public $s5;
	public $s6;
	public $s7;
	public $s8;
	public $s9;
	public $s10;
	public $s11;
	public $s12;
	public $s13;
	public $s14;
	public $s15;
	public $Summ;
	public $GunPlay;
	public $Total;
	public $Place;
	public $RatingGroup;
	public $NewDigitId;

	/** @var ShootersTable */
	public $Shooter;

	/** @var CompetitionsTable */
	public $Competition;

	/** @var DigitsTable */
	public $Digit;

	public static function getTableName()
	{
		return 'Results';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('CompId'),
			new Fields\IntegerField('ShooterId'),
			new Fields\IntegerField('s1'),
			new Fields\IntegerField('s2'),
			new Fields\IntegerField('s3'),
			new Fields\IntegerField('s4'),
			new Fields\IntegerField('s5'),
			new Fields\IntegerField('s6'),
			new Fields\IntegerField('s7'),
			new Fields\IntegerField('s8'),
			new Fields\IntegerField('s9'),
			new Fields\IntegerField('s10'),
			new Fields\IntegerField('s11'),
			new Fields\IntegerField('s12'),
			new Fields\IntegerField('s13'),
			new Fields\IntegerField('s14'),
			new Fields\IntegerField('s15'),
			new Fields\IntegerField('Summ'),
			new Fields\IntegerField('GunPlay'),
			new Fields\IntegerField('Total'),
			new Fields\IntegerField('Place'),
			new Fields\StringField('RatingGroup'),
			new Fields\IntegerField('NewDigitId'),

			(new Fields\Relations\Reference(
				'Shooter',
				ShootersTable::class,
				Join::on('this.ShooterId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Competition',
				CompetitionsTable::class,
				Join::on('this.CompId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Digit',
				DigitsTable::class,
				Join::on('this.NewDigitId', 'ref.id')
			))->configureJoinType('left'),
		];
	}
}