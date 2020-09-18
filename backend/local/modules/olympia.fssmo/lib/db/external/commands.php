<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class CommandsTable extends External
{
	public $id;
	public $CommandName;
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
	public $Summ;
	public $Total;
	public $Place;
	public $CommandCategoryId;

	/** @var ShootersTable */
	public $Shooter;

	/** @var CompetitionsTable */
	public $Competition;

	public static function getTableName()
	{
		return 'Commands';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('CommandName'),
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
			new Fields\IntegerField('Summ'),
			new Fields\IntegerField('Total'),
			new Fields\IntegerField('Place'),
			new Fields\IntegerField('CommandCategoryId'),

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
		];
	}
}