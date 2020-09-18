<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class CalendarTable extends ORM\Model\IBlockElementTable
{
	protected static function getIblockId ()
	{
		return IBLOCK_CALENDAR;
	}
}