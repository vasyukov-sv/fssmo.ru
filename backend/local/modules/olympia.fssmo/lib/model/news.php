<?php

namespace Olympia\Fssmo\Model;

use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Bitrix\ORM;
use Bitrix\Main\Orm\Fields;

class NewsTable extends ORM\Model\IBlockElementTable
{
	public $NAME;
	public $DETAIL_PAGE_URL;
	public $PREVIEW_TEXT;
	public $DETAIL_TEXT;
	public $PREVIEW_PICTURE;
	/** @var DateTime */
	public $ACTIVE_FROM;

	/** @var DisciplinesTable */
	public $DISCIPLINE;
	/** @var CompetitionsTable */
	public $COMPETITION;

	protected static function getIblockId ()
	{
		return IBLOCK_NEWS;
	}

	public static function getMap()
	{
		$map = parent::getMap();

		$map[] = (new Fields\Relations\Reference(
			'DISCIPLINE',
			DisciplinesTable::class,
			Join::on('ref.ID', 'this.PROPERTY.DISCIPLINE')
		))->configureJoinType('left');

		$map[] = (new Fields\Relations\Reference(
			'COMPETITION',
			CompetitionsTable::class,
			Join::on('ref.ID', 'this.PROPERTY.COMPETITION')
		))->configureJoinType('left');

		return $map;
	}
}