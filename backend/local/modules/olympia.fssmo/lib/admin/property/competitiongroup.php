<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Fssmo\Admin\Property;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\FileInput;

class CompetitionGroup
{
	public static function GetUserTypeDescription ()
	{
		return [
			"USER_TYPE" 			=> "competitiongroup",
			"DESCRIPTION" 			=> 'Группа с фотографией',
			"PROPERTY_TYPE" 		=> "S",
			"GetPropertyFieldHtml" 	=> array(__CLASS__, "GetPropertyFieldHtml"),
			"GetPublicViewHTML" 	=> array(__CLASS__, "GetPublicViewHTML"),
			"ConvertToDB" 			=> array(__CLASS__, "ConvertToDB"),
			"GetAdminListViewHTML" 	=> array(__CLASS__, "GetAdminListViewHTML"),
			"ConvertFromDB" 		=> array(__CLASS__, "ConvertFromDB"),
		];
	}

	function GetPropertyFieldHtml ($arProperty, $value, $strHTMLControlName)
	{
		$html = '<table><tr>';

		$html .= '<td><input type="text" size="7" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[GROUP]" value="'.htmlspecialcharsbx($value["VALUE"]['GROUP']).'" placeholder="Группа">&nbsp;&nbsp;</td>';
		$html .= '<td><input type="hidden" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[PHOTO][old_id]" value="'.htmlspecialcharsbx($value["VALUE"]['PHOTO']).'">';

		Loader::includeModule('fileman');

		$html .= \CFileInput::Show(htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[PHOTO]', (isset($value["VALUE"]['PHOTO']) ? $value["VALUE"]['PHOTO'] : ''), array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => ["W" => 200, "H" => 200]
			),
			array(
				'upload' => true,
				'medialib' => true,
				'file_dialog' => true,
				'cloud' => false,
				'del' => ['NAME' => 'PROP_DEL_'.intval($value["VALUE"]['PHOTO']).'_PHOTO'],
				'description' => false
			)
		);

		$html .= '</td></tr></table>';

		return  $html;
	}

	function GetAdminListViewHTML ($arProperty, $value, $strHTMLControlName)
	{
		return '';
	}

	function GetPublicViewHTML ($arProperty, $value, $strHTMLControlName)
	{
		return '';
	}

	function ConvertToDB ($arProperty, $value)
	{
		$pictureId = '';

		if ($value["VALUE"]['GROUP'] == '')
			return false;

		if ($arProperty['ELEMENT_ID'] > 0 && !isset($value["VALUE"]['PHOTO']["old_id"]))
		{
			$props = \CIBlockElement::GetProperty($arProperty['IBLOCK_ID'], $arProperty['ELEMENT_ID'], [], ['ID' => $arProperty['ID'], 'EMPTY' => 'N']);

			while ($prop = $props->Fetch())
			{
				if (is_array($prop['VALUE']) && $prop['VALUE']['PHOTO'] > 0)
					\CFile::Delete($prop['VALUE']['PHOTO']);
			}
		}

		$alias = [
			'А' => 'A',
			'В' => 'B',
			'С' => 'C',
			'Н' => 'H',
		];

		if (isset($alias[$value["VALUE"]['GROUP']]))
			$value["VALUE"]['GROUP'] = $alias[$value["VALUE"]['GROUP']];

		if (is_array($value["VALUE"]['PHOTO']))
		{
			if (isset($_POST['PROP_DEL_'.intval($value["VALUE"]['PHOTO']["old_id"]).'_PHOTO']))
				$value["VALUE"]['PHOTO']["del"] = 'Y';

			if ($value["VALUE"]['PHOTO']["del"] && $value["VALUE"]['PHOTO']["old_id"])
			{
				\CFile::Delete($value["VALUE"]['PHOTO']["old_id"]);
				$value["VALUE"]['PHOTO']["old_id"] = false;
			}

			if ($value["VALUE"]['PHOTO']["error"])
				$pictureId = $value["VALUE"]['PHOTO']["old_id"];
			else
			{
				if ($value["VALUE"]['PHOTO']["old_id"])
					\CFile::Delete($value["VALUE"]['PHOTO']["old_id"]);

				$value["VALUE"]['PHOTO']["MODULE_ID"] = "iblock";

				$pictureId = \CFile::SaveFile($value["VALUE"]['PHOTO'], "winners");
			}
		}

		if (is_array($value["VALUE"]) && $pictureId > 0)
		{
			$value['VALUE']['PHOTO'] = $pictureId;
			$value["VALUE"] = serialize($value["VALUE"]);
		}

		if ($pictureId == '')
			return false;

		return $value;
	}

	function ConvertFromDB ($arProperty, $value)
 	{
     	if (is_string($value["VALUE"]) && strlen($value["VALUE"])>0)
     	{
			$value["VALUE"] = unserialize($value["VALUE"]);

			if (!isset($value["VALUE"]['PHOTO']) || !$value["VALUE"]['PHOTO'])
				$value["VALUE"]['PHOTO'] = '';
	 	}

		return $value;
	}
}