<?php

namespace Olympia\Fssmo\Model;

use Bitrix\Main\Type\DateTime;
use Olympia\Bitrix\ORM;

class JudgesTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $NAME;
	public $PREVIEW_TEXT;
	public $PREVIEW_PICTURE;

	protected static function getIblockId ()
	{
		return IBLOCK_JUDGES;
	}
}