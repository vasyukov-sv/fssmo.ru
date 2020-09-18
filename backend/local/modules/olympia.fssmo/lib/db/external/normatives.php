<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External;

class NormativesTable extends External
{
	public $id;
	public $DisciplineId;
	public $TargetsCount;
	public $Final;
	public $Result;
	public $DigitId;
	public $GenderId;
	public $DisnameId;
	public $NormativePeriodId;

	/** @var NormativePeriodsTable */
	public $Period;

	/** @var DigitsTable */
	public $Digit;

	public static function getTableName()
	{
		return 'Normatives';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\IntegerField('DisciplineId'),
			new Fields\IntegerField('TargetsCount'),
			new Fields\IntegerField('Final'),
			new Fields\IntegerField('Result'),
			new Fields\IntegerField('DigitId'),
			new Fields\IntegerField('GenderId'),
			new Fields\IntegerField('DisnameId'),
			new Fields\IntegerField('NormativePeriodId'),

			(new Fields\Relations\Reference(
				'Period',
				NormativePeriodsTable::class,
				Join::on('this.NormativePeriodId', 'ref.id')
			))->configureJoinType('inner'),

			(new Fields\Relations\Reference(
				'Digit',
				DigitsTable::class,
				Join::on('this.DigitId', 'ref.id')
			))->configureJoinType('inner'),
		];
	}
}