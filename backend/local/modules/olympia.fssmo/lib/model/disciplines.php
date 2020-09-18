<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class DisciplinesTable extends ORM\Model\IBlockElementTable
{
	public $NAME;
	public $DETAIL_TEXT;
	public $XML_ID;

	protected static function getIblockId ()
	{
		return IBLOCK_DISCIPLINES;
	}
}