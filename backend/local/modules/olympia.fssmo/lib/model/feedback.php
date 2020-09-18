<?php

namespace Olympia\Fssmo\Model;

use Olympia\Bitrix\ORM;

class FeedbackTable extends ORM\Model\IBlockElementTable
{
	protected static function getIblockId ()
	{
		return IBLOCK_FORM_FEEDBACK;
	}
}