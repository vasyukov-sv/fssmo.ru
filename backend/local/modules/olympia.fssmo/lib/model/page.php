<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class PageTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $NAME;
	public $CODE;
	public $IBLOCK_SECTION_ID;
	public $PROPERTY_IBLOCK_ID;
	public $DETAIL_TEXT;

	protected static function getIblockId ()
	{
		return IBLOCK_STRUCTURE;
	}
}