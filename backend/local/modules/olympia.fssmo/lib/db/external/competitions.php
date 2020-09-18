<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class CompetitionsTable extends External
{
	public $id;
	public $CompName;
	public $Organizer;
	public $ClubId;
	public $DisciplineId;
	/** @var DateTime */
	public $BeginDate;
	public $Days;
	public $IsRating;
	public $CompTypeId;
	public $TargetsCount;
	public $guid;
	public $Numbers;
	public $GroupLength;
	public $ImageId1;
	public $ImageId2;
	public $MaximumResult;
	public $SiteId;
	public $RatingType;
	public $OpenForCompRole;
	public $StandsCount;

	/** @var DisciplinesTable */
	public $Discipline;

	/** @var ClubsTable */
	public $Club;

	public static function getTableName()
	{
		return 'Competitions';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('CompName'),
			new Fields\StringField('Organizer'),
			new Fields\IntegerField('ClubId'),
			new Fields\IntegerField('DisciplineId'),
			new Fields\DateField('BeginDate'),
			new Fields\IntegerField('Days'),
			new Fields\IntegerField('IsRating'),
			new Fields\IntegerField('CompTypeId'),
			new Fields\IntegerField('TargetsCount'),
			new Fields\StringField('guid'),
			new Fields\TextField('Numbers'),
			new Fields\IntegerField('GroupLength'),
			new Fields\IntegerField('ImageId1'),
			new Fields\IntegerField('ImageId2'),
			new Fields\IntegerField('MaximumResult'),
			new Fields\IntegerField('SiteId'),
			new Fields\IntegerField('RatingType'),
			new Fields\BooleanField('OpenForCompRole', [
				'values' => [0, 1]
			]),
			new Fields\IntegerField('StandsCount'),

			(new Fields\Relations\Reference(
				'Discipline',
				DisciplinesTable::class,
				Join::on('this.DisciplineId', 'ref.id')
			))->configureJoinType('left'),

			(new Fields\Relations\Reference(
				'Club',
				ClubsTable::class,
				Join::on('this.ClubId', 'ref.id')
			))->configureJoinType('left'),
		];
	}
}