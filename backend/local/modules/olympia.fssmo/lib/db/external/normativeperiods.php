<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Olympia\Fssmo\Db\External;

class NormativePeriodsTable extends External
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

	public static function getTableName()
	{
		return 'NormativePeriods';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\DatetimeField('StartDate'),
			new Fields\DatetimeField('EndDate'),
		];
	}
}