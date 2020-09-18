<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Fssmo\Admin\Property;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\FileInput;
use CUser;
use CUtil;
use Olympia\Fssmo\Db\External\ShootersTable;

include_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/tools/prop_userid.php');

class Shooter extends \CIBlockPropertyUserID
{
	const USER_TYPE = 'Shooter';

	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => self::USER_TYPE,
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => "Привязка к стрелку",
			"BASE_TYPE" => "string",
		);
	}

	public static function GetIBlockPropertyDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => self::USER_TYPE,
			"DESCRIPTION" => 'Привязка к стрелку',
			"GetAdminListViewHTML" => array(__CLASS__, "GetAdminListViewHTML"),
			"GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
			"ConvertToDB" => array(__CLASS__, "ConvertToDB"),
			"ConvertFromDB" => array(__CLASS__, "ConvertFromDB"),
			"GetSettingsHTML" => array(__CLASS__, "GetSettingsHTML"),
			"AddFilterFields" => array(__CLASS__,'AddFilterFields'),
			"GetAdminFilterHTML" => array(__CLASS__, "GetAdminFilterHTML")
		);
	}

	public static function GetAdminListViewHTML($arProperty, $value)
	{
		return $value['VALUE'];
	}

	function GetDBColumnType($arUserField)
	{
		return 'varchar(100)';
	}

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$form_name = '';

		if ($arUserField['ENTITY_ID'] == 'USER')
			$form_name = 'user_edit_form';

		return self::GetPropertyFieldHtml($arUserField, ['VALUE' => $arHtmlControl['VALUE']], ['FORM_NAME' => $form_name, 'VALUE' => $arHtmlControl['NAME']]);
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$res = "";

		if ((int) $value["VALUE"] > 0)
		{
			/** @var ShootersTable $shooter */
			$shooter = ShootersTable::query()
				->setSelect(['id', 'FirstName', 'LastName'])
				->setFilter(['=id' => (int) $value["VALUE"]])
				->exec()->fetch();

			if ($shooter)
				$res = htmlspecialcharsbx($shooter->FirstName)." ".htmlspecialcharsbx($shooter->LastName);
			else
				$res = "&nbsp;".GetMessage("MAIN_NOT_FOUND");
		}

		if (strLen(trim($strHTMLControlName["FORM_NAME"])) <= 0)
			$strHTMLControlName["FORM_NAME"] = "form_element";

		ob_start();

		echo FindShooterId(htmlspecialcharsbx($strHTMLControlName["VALUE"]), $value["VALUE"], $res, htmlspecialcharsEx($strHTMLControlName["FORM_NAME"]), "SU", "3", "", "...", "typeinput", "tablebodybutton", "/bitrix/admin/fssmo_shooters_list.php");

		$return = ob_get_contents();
		ob_end_clean();

		return  $return;
	}
}

function FindShooterId($tag_name, $tag_value, $user_name="", $form_name = "form1", $select="none", $tag_size = "3", $tag_maxlength="", $button_value = "...", $tag_class="typeinput", $button_class="tablebodybutton", $search_page="/bitrix/admin/user_search.php")
{
	global $APPLICATION;
	$tag_name_x = preg_replace("/([^a-z0-9])/is", "x", $tag_name);
	$tag_name_escaped = CUtil::JSEscape($tag_name);

	if($APPLICATION->GetGroupRight("main") >= "R")
	{
		$strReturn = "
<input type=\"text\" name=\"".$tag_name."\" id=\"".$tag_name."\" value=\"".($select=="none"?"":$tag_value)."\" size=\"".$tag_size."\" maxlength=\"".$tag_maxlength."\" class=\"".$tag_class."\">
<IFRAME style=\"width:0px; height:0px; border: 0px\" src=\"javascript:void(0)\" name=\"hiddenframe".$tag_name."\" id=\"hiddenframe".$tag_name."\"></IFRAME>
<input class=\"".$button_class."\" type=\"button\" name=\"FindUser".$tag_name_x."\" id=\"FindUser".$tag_name_x."\" OnClick=\"window.open('".$search_page."?lang=".LANGUAGE_ID."&FN=".$form_name."&FC=".$tag_name_escaped."', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"".$button_value."\" ".($select=="none"?"disabled":"").">
<span id=\"div_".$tag_name."\">".$user_name."</span>
<script>
";
		if($user_name=="")
			$strReturn.= "var tv".$tag_name_x."='';\n";
		else
			$strReturn.= "var tv".$tag_name_x."='".CUtil::JSEscape($tag_value)."';\n";

		$strReturn.= "
function Ch".$tag_name_x."()
{
	var DV_".$tag_name_x.";
	DV_".$tag_name_x." = document.getElementById(\"div_".$tag_name_escaped."\");
	if (!!DV_".$tag_name_x.")
	{
		if (
			document.".$form_name."
			&& document.".$form_name."['".$tag_name_escaped."']
			&& typeof tv".$tag_name_x." != 'undefined'
			&& tv".$tag_name_x." != document.".$form_name."['".$tag_name_escaped."'].value
		)
		{
			tv".$tag_name_x."=document.".$form_name."['".$tag_name_escaped."'].value;
			if (tv".$tag_name_x."!='')
			{
				DV_".$tag_name_x.".innerHTML = '<i>".GetMessage("MAIN_WAIT")."</i>';

				document.getElementById(\"hiddenframe".$tag_name_escaped."\").src='/bitrix/admin/fssmo_get_shooter.php?ID=' + tv".$tag_name_x."+'&strName=".$tag_name_escaped."&lang=".LANG.(defined("ADMIN_SECTION") && ADMIN_SECTION===true?"&admin_section=Y":"")."';
			}
			else
			{
				DV_".$tag_name_x.".innerHTML = '';
			}
		}
		else if (
			DV_".$tag_name_x."
			&& DV_".$tag_name_x.".innerHTML.length > 0
			&& document.".$form_name."
			&& document.".$form_name."['".$tag_name_escaped."']
			&& document.".$form_name."['".$tag_name_escaped."'].value == ''
		)
		{
			document.getElementById('div_".$tag_name."').innerHTML = '';
		}
	}
	setTimeout(function(){Ch".$tag_name_x."()},1000);
}
Ch".$tag_name_x."();
//-->
</script>
";
	}
	else
	{
		$strReturn = "
			<input type=\"text\" name=\"$tag_name\" id=\"$tag_name\" value=\"$tag_value\" size=\"$tag_size\" maxlength=\"strMaxLenght\">
			<input type=\"button\" name=\"FindUser".$tag_name_x."\" id=\"FindUser".$tag_name_x."\" OnClick=\"window.open('".$search_page."?lang=".LANGUAGE_ID."&FN=$form_name&FC=$tag_name_escaped', '', 'scrollbars=yes,resizable=yes,width=760,height=560,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"$button_value\">
			$user_name
			";
	}
	return $strReturn;
}