<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class CompetitionsCalendarTable extends External
{
	public $id;
	public $CompetitionName;
	/** @var DateTime */
	public $BeginDate;
	/** @var DateTime */
	public $EndDate;
	public $ClubName;
	public $TargetsCount;
	public $Poster;
	public $Protocol;
	public $ShowLink;
	public $Link;
	public $ShowRegistration;
	public $MaxShootersCount;
	public $CompetitionType;
	public $SiteDiscipline;
	public $WinnerHtml;
	public $WinnerText;
	public $WinnerPhotoSmall;
	public $WinnerPhotoBig;
	public $Shedule;
	public $GroupsList;

	/** @var CompetitionsTable */
	public $Competition;

	public static function getTableName()
	{
		return 'CompetitionsCalendar';
	}

	public static function getMap()
	{
		return [
			new Fields\IntegerField('id', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('CompetitionName'),
			new Fields\DateField('BeginDate'),
			new Fields\DateField('EndDate'),
			new Fields\StringField('ClubName'),
			new Fields\IntegerField('TargetsCount'),
			new Fields\StringField('Poster'),
			new Fields\StringField('Protocol'),
			new Fields\BooleanField('ShowLink', [
				'values' => [0, 1]
			]),
			new Fields\StringField('Link'),
			new Fields\BooleanField('ShowRegistration', [
				'values' => [0, 1]
			]),
			new Fields\IntegerField('MaxShootersCount'),
			new Fields\IntegerField('CompetitionType'),
			new Fields\IntegerField('SiteDiscipline'),
			new Fields\StringField('WinnerHtml'),
			new Fields\StringField('WinnerText'),
			new Fields\StringField('WinnerPhotoSmall'),
			new Fields\StringField('WinnerPhotoBig'),
			new Fields\StringField('Shedule'),
			new Fields\StringField('GroupsList'),

			(new Fields\Relations\Reference(
				'Competition',
				CompetitionsTable::class,
				Join::on('this.id', 'ref.SiteId')
			))->configureJoinType('left'),
		];
	}
}