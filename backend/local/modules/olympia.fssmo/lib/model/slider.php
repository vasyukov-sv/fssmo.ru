<?php

namespace Olympia\Fssmo\Model;

use Bitrix\Main\Type\DateTime;
use Olympia\Bitrix\ORM;

class SliderTable extends ORM\Model\IBlockElementTable
{
	public $ID;
	public $NAME;
	public $PREVIEW_TEXT;
	public $PREVIEW_PICTURE;
	/** @var DateTime */
	public $ACTIVE_FROM;
	/** @var DateTime */
	public $ACTIVE_TO;

	protected static function getIblockId ()
	{
		return IBLOCK_SLIDER;
	}
}