<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix;

use Bitrix\Main\Application;

class Helpers
{
	public static function p ($v, $isDump = false, $skipAuth = false)
	{
		global $USER;

		if ($skipAuth || $USER->IsAdmin())
		{
			echo '<pre>';
			if($isDump === true)
				var_dump($v);
			else
				echo print_r($v, true);

			echo '</pre>';
		}
	}

	public static function isPhone ($string = '')
	{
		return (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/iu', $string) ? false : true);
	}

	public static function getPlural ($n = 0, array $forms = [])
	{
		return $n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2]);
	}

	public static function getPluralIndex ($n = 0)
	{
		return $n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
	}

	public static function getHttpHost ()
	{
		$request = Application::getInstance()->getContext()->getRequest();

		return 'http'.($request->isHttps() ? 's' : '').'://'.$request->getHttpHost();
	}

	public static function getFieldsFromIblock ($id, $iblockId)
	{
		$obj = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'ID' => $id], false, false, ['ID', 'IBLOCK_ID', 'PROPERTY_*'])->GetNextElement();
		$props = $obj->GetProperties();

		$fields = [
			'ID' => $id,
		];

		foreach ($props as $prop)
		{
			if (isset($prop['LINK_IBLOCK_ID']) && $prop['LINK_IBLOCK_ID'] > 0)
			{
				$item = \Bitrix\Iblock\ElementTable::getRow([
					'select' => ['ID', 'NAME'],
					'filter' => ['=IBLOCK_ID' => $prop['LINK_IBLOCK_ID'], '=ID' => $prop['VALUE']]
				]);

				if ($item)
					$prop['VALUE'] = $item['NAME'];
			}

			if (isset($prop['VALUE']['TEXT']))
				$fields[$prop['CODE']] = $prop['VALUE']['TEXT'];
			else if (!is_array($prop['VALUE']))
				$fields[$prop['CODE']] = $prop['VALUE'];
			else
				$fields[$prop['CODE']] = implode(', ', $prop['VALUE']);

			if ($prop['PROPERTY_TYPE'] == 'F' && $prop['VALUE'] > 0)
				$fields[$prop['CODE']] = \CFile::GetFileArray((int) $prop['VALUE'])['SRC'];
		}

		return $fields;
	}
}