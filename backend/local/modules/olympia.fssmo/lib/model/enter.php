<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class EnterTable extends ORM\Model\IBlockElementTable
{
	protected static function getIblockId ()
	{
		return IBLOCK_FORM_ENTER;
	}
}