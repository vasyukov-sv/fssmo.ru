<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class CompShootersTable extends External
{
	public $id;
	public $ShooterId;
	public $CompId;
	public $GroupNumber;
	public $Number;
	public $NumberInGroup;
	public $CategoryId;

	/** @var ShootersTable */
	public $Shooter;
	/** @var CompetitionsTable */
	public $Competition;
	/** @var CategoriesTable */
	public $Cetegory;

	public static function getTableName()
	{
		return 'CompShooters';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('ShooterId'),
			new Fields\IntegerField('CompId'),
			new Fields\IntegerField('GroupNumber'),
			new Fields\IntegerField('Number'),
			new Fields\IntegerField('NumberInGroup'),
			new Fields\IntegerField('CategoryId'),

			(new Fields\Relations\Reference(
				'Shooter',
				ShootersTable::class,
				Join::on('this.ShooterId', 'ref.id')
			))->configureJoinType('inner'),

			(new Fields\Relations\Reference(
				'Competition',
				CompetitionsTable::class,
				Join::on('this.CompId', 'ref.id')
			))->configureJoinType('inner'),

			(new Fields\Relations\Reference(
				'Category',
				CategoriesTable::class,
				Join::on('this.CategoryId', 'ref.id')
			))->configureJoinType('inner'),
		];
	}
}