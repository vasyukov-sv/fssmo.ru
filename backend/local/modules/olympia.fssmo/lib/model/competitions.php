<?php

namespace Olympia\Fssmo\Model;

use Bitrix\Main\ORM\Query\Join;
use Olympia\Bitrix\ORM;
use Bitrix\Main\Orm\Fields;

class CompetitionsTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $CODE;
	public $NAME;
	public $DETAIL_PAGE_URL;
	public $PREVIEW_PICTURE;
	public $DETAIL_TEXT;
	/** @var DisciplinesTable */
	public $DISCIPLINE;

	protected static function getIblockId ()
	{
		return IBLOCK_COMPETITIONS;
	}

	public static function getMap()
	{
		$map = parent::getMap();

		$map[] = (new Fields\Relations\Reference(
			'DISCIPLINE',
			DisciplinesTable::class,
			Join::on('ref.ID', 'this.PROPERTY.DISCIPLINE')
		))->configureJoinType('inner');

		return $map;
	}
}