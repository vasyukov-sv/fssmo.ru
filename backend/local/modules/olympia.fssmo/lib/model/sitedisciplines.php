<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class SiteDisciplinesTable extends ORM\Model\IBlockElementTable
{
	public $NAME;
	public $DETAIL_TEXT;

	protected static function getIblockId ()
	{
		return IBLOCK_SITE_DISCIPLINES;
	}
}