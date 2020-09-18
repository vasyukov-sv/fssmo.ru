<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Property;

class Color
{
	public static function GetUserTypeDescription()
	{
		return [
			"USER_TYPE_ID" 	=> "color",
			"CLASS_NAME" 	=> self::class,
			"DESCRIPTION" 	=> 'Выбор цвета',
			"BASE_TYPE" 	=> "string",
		];
	}

	public static function GetDBColumnType()
	{
		global $DB;

		switch(strtolower($DB->type))
		{
			case "mysql":
				return "varchar(20)";
			case "oracle":
				return "varchar2(20 char)";
			case "mssql":
				return "varchar(20)";
		}

		return '';
	}

	public static function PrepareSettings($arUserField)
	{
		return ["DEFAULT_VALUE" => $arUserField["SETTINGS"]["DEFAULT_VALUE"],];
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		if($bVarsFromForm)
			$value = htmlspecialcharsbx($GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"]);
		elseif(is_array($arUserField))
			$value = htmlspecialcharsbx($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
		else
			$value = "";
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_STRING_DEFAULT_VALUE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" size="20"  maxlength="225" value="'.$value.'">
			</td>
		</tr>';

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if ($arUserField["ENTITY_VALUE_ID"]<1 && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0)
			$arHtmlControl["VALUE"] = htmlspecialcharsbx($arUserField["SETTINGS"]["DEFAULT_VALUE"]);

		$arHtmlControl["VALIGN"] = "middle";

		return '<input type="color" name="'.$arHtmlControl["NAME"].'" value="'.$arHtmlControl["VALUE"].'" '.($arUserField["EDIT_IN_LIST"]!="Y"? 'disabled="disabled" ': '').'>';
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return '<input type="color" name="'.$arHtmlControl["NAME"].'" value="'.$arHtmlControl["VALUE"].'">';
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if (strlen($arHtmlControl["VALUE"]) > 0)
			return '<span style="background-color:'.$arHtmlControl["VALUE"].';width:30px;height:15px;display:inline-block;"></span>';
		else
			return '&nbsp;';
	}

	public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '<input type="color" name="'.$arHtmlControl["NAME"].'" value="'.$arHtmlControl["VALUE"].'" >';
	}

	public static function CheckFields($arUserField, $value)
	{
		return [];
	}

	public static function OnSearchIndex($arUserField)
	{
		if (is_array($arUserField["VALUE"]))
			return implode("\r\n", $arUserField["VALUE"]);
		else
			return $arUserField["VALUE"];
	}
}

?>