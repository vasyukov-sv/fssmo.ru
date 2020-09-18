<?php

namespace Olympia\Fssmo\Model;

use Bitrix\Catalog\PriceTable;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Bitrix\ORM;
use Bitrix\Main\Orm\Fields;

class CompetitionsPriceTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $NAME;

	protected static function getIblockId ()
	{
		return IBLOCK_COMPETITIONS_PRICE;
	}

	public static function getMap()
	{
		$map = parent::getMap();

		$map[] = (new Fields\Relations\Reference(
			'PRICE',
			PriceTable::class,
			Join::on('ref.PRODUCT_ID', 'this.ID')
		))->configureJoinType('inner');

		return $map;
	}
}