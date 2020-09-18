<?php

namespace Olympia\Fssmo;

use Bitrix\Main\Loader;

class Helpers
{
	public static function isGUID ($value)
	{
		return preg_match("/^^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/i", $value) !== 0;
	}

	public static function getNeighboringItems ($elementId, $sort = [], $filter = [])
	{
		Loader::includeModule('iblock');

		$arrows = [];

		$elements = \CIBlockElement::GetList($sort, $filter, false, ['nElementID' => $elementId, 'nPageSize' => 1], ['ID', 'NAME', 'DETAIL_PAGE_URL']);

		while ($element = $elements->GetNext())
			$arrows[] = $element;

		$result = ['LEFT' => [], 'RIGHT' => []];

		if (count($arrows) == 3)
		{
			$result['LEFT'] = $arrows[0];
			$result['RIGHT'] = $arrows[2];
		}
		elseif (count($arrows) == 2)
		{
			if ($arrows[0]['ID'] != $elementId)
				$result['LEFT'] = $arrows[0];
			if ($arrows[1]['ID'] != $elementId)
				$result['RIGHT'] = $arrows[1];
		}

		return $result;
	}

	public static function isValidUuid ($uuid)
	{
		if (!is_string($uuid) || (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid) !== 1))
			return false;

		return true;
	}
}