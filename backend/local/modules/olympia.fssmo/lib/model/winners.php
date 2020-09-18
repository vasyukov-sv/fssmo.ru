<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class WinnersTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $NAME;
	public $PREVIEW_TEXT;
	public $PREVIEW_PICTURE;

	protected static function getIblockId ()
	{
		return IBLOCK_WINNERS;
	}
}