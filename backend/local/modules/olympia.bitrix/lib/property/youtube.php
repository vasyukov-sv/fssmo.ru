<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Property;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\FileInput;

class Youtube
{
	public static function GetUserTypeDescription ()
	{
		return [
			"USER_TYPE" 			=> "youtube",
			"DESCRIPTION" 			=> 'Видеоролик с YouTube',
			"PROPERTY_TYPE" 		=> "S",
			"GetPropertyFieldHtml" 	=> array('Olympia\Bitrix\Property\Youtube', "GetPropertyFieldHtml"),
			"GetPublicViewHTML" 	=> array('Olympia\Bitrix\Property\Youtube', "GetPublicViewHTML"),
			"ConvertToDB" 			=> array('Olympia\Bitrix\Property\Youtube', "ConvertToDB"),
			"GetAdminListViewHTML" 	=> array('Olympia\Bitrix\Property\Youtube', "GetAdminListViewHTML"),
			"ConvertFromDB" 		=> array('Olympia\Bitrix\Property\Youtube', "ConvertFromDB"),
		];
	}

	function GetPropertyFieldHtml ($arProperty, $value, $strHTMLControlName)
	{
		$html = 'Ссылка на видео<br>
		<input type="text" size="70" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[SRC]" value="'.htmlspecialcharsbx($value["VALUE"]['SRC']).'">';

		$html .= '<br><br>Описание видео<br>
		<textarea rows="5" cols="70" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[DESCRIPTION]">'.htmlspecialcharsbx($value["VALUE"]['DESCRIPTION']).'</textarea>';

		$html .= '<br><br>Обложка видео<br>
		<input type="hidden" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'[PHOTO]" value="'.htmlspecialcharsbx($value["VALUE"]['PHOTO']).'">';

		Loader::includeModule('fileman');

		$html .= FileInput::createInstance(array(
						"name" => 'PROP_EXT_'.$arProperty['ID'].'_PHOTO',
						"description" => false,
						"upload" => true,
						"allowUpload" => "F",
						"allowUploadExt" => '',
						"medialib" => true,
						"fileDialog" => true,
						"cloud" => true,
						"delete" => true,
						"maxCount" => 1
					))->show($value["VALUE"]['PHOTO']);

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
		$p = $_POST['PROP_EXT_'.$arProperty['ID'].'_PHOTO'];

		if (is_numeric($p))
		{
			$picture = [
				'name' 		=> '',
				'type' 		=> '',
				'tmp_name' 	=> '',
				'size' 		=> 0,
				'error' 	=> 4,
				'old_id'	=> $p
			];
		}
		else
		{
			$picture = $p;
			$picture['old_id'] = isset($value['VALUE']['PHOTO']) ? (int) $value['VALUE']['PHOTO'] : 0;

			if (isset($value['VALUE']['PHOTO']))
				$picture['del'] = 'Y';
		}

		$pictureId = 0;

		if (isset($_POST['PROP_EXT_'.$arProperty['ID'].'_PHOTO_del']))
			$picture['del'] = 'Y';

		if (is_array($picture))
		{
			if ($picture["del"] && $picture["old_id"])
			{
				\CFile::Delete($picture["old_id"]);
				$picture["old_id"] = false;
			}

			if ($picture["error"])
				$pictureId = $picture["old_id"];
			else
			{
				if ($picture["old_id"])
					\CFile::Delete($picture["old_id"]);

				if (strpos($picture['tmp_name'], $_SERVER['DOCUMENT_ROOT']) === false)
					$picture['tmp_name'] = $_SERVER['DOCUMENT_ROOT'].$picture['tmp_name'];

				$picture["MODULE_ID"] = "main";
				$pictureId = \CFile::SaveFile($picture, "uf");
			}
		}

		if (is_array($value["VALUE"]))
		{
			$value['VALUE']['PHOTO'] = $pictureId;
			$value["VALUE"] = json_encode($value["VALUE"]);
		}

		return $value;
	}

	function ConvertFromDB ($arProperty, $value)
 	{
     	if (is_string($value["VALUE"]) && strlen($value["VALUE"])>0)
     	{
			$value["VALUE"] = json_decode($value["VALUE"], true);

			if (!isset($value["VALUE"]['PHOTO']) || !$value["VALUE"]['PHOTO'])
				$value["VALUE"]['PHOTO'] = '';
	 	}

		return $value;
	}
}