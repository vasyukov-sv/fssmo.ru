<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
CModule::IncludeModule("iblock");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();
/*
 * this page only for actions and get info
 *
 */
define('B_ADMIN_SUBELEMENTS',1);
define('B_ADMIN_SUBELEMENTS_LIST',true);

global $APPLICATION;

$strSubTMP_ID = 0;
if (array_key_exists('TMP_ID', $_REQUEST))
	$strSubTMP_ID = intval($_REQUEST['TMP_ID']);

$intSubPropValue = 0;
if (array_key_exists('PRODUCT_ID', $_REQUEST))
	$intSubPropValue = intval($_REQUEST['PRODUCT_ID']);

$values = [];
$arPropertyOriginal = [];

if ($intSubPropValue > 0 && $strSubTMP_ID > 0)
{
	$parent = CIBlockElement::GetList([], ['ID' => $TMP_ID], false, false, ['ID', 'IBLOCK_ID'])->Fetch();

	if (isset($parent['ID']))
	{
		$res = CIBlockElement::GetProperty($parent['IBLOCK_ID'], $parent['ID'], "", "", ["ID" => $intSubPropValue]);

		while ($ob = $res->Fetch())
		{
			if ($ob['VALUE'] > 0)
				$values[$ob['PROPERTY_VALUE_ID']] = ['VALUE' => $ob['VALUE'], 'DESCRIPTION' => $ob['DESCRIPTION']];

			if (!count($arPropertyOriginal))
				$arPropertyOriginal = $ob;
		}
	}

	if (!count($arPropertyOriginal))
	{
		$arPropertyOriginal = CIBlockProperty::GetByID($intSubPropValue)->Fetch();
	}
}

$strSubIBlockType = '';
$arSubIBlockType = false;
if (array_key_exists('type', $_REQUEST))
	$strSubIBlockType = strval($_REQUEST['type']);
if ('' != $strSubIBlockType)
{
	$arSubIBlockType = CIBlockType::GetByIDLang($strSubIBlockType, LANGUAGE_ID);
}
if (false === $arSubIBlockType)
	$APPLICATION->AuthForm(GetMessage("IBLOCK_BAD_BLOCK_TYPE_ID"));

$intSubIBlockID = 0;
if (array_key_exists('IBLOCK_ID', $_REQUEST))
	$intSubIBlockID = intval($IBLOCK_ID);

$bBadBlock = true;
if (0 < $intSubIBlockID)
{
	$arSubIBlock = CIBlock::GetArrayByID($intSubIBlockID);
	if ($arSubIBlock)
	{
		$bBadBlock = !CIBlockRights::UserHasRightTo($intSubIBlockID, $intSubIBlockID, "iblock_admin_display");;
	}
}

if ($bBadBlock)
{
	$APPLICATION->SetTitle($arSubIBlockType["NAME"]);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	echo ShowError(GetMessage("IBLOCK_BAD_IBLOCK"));?>
	<a href="/bitrix/admin/iblock_admin.php?lang=<?echo LANGUAGE_ID?>&type=<?echo htmlspecialcharsbx($strSubIBlockType)?>"><?echo GetMessage("IBLOCK_BACK_TO_ADMIN")?></a>
	<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$arSubIBlock["SITE_ID"] = array();
$rsSites = CIBlock::GetSite($intSubIBlockID);
while($arSite = $rsSites->Fetch())
	$arSubIBlock["SITE_ID"][] = $arSite["LID"];

$strSubElementAjaxPath = '/bitrix/admin/olympia_subelement_admin.php?WF=Y&IBLOCK_ID='.$intSubIBlockID.'&type='.urlencode($strSubIBlockType).'&lang='.LANGUAGE_ID.'&PRODUCT_ID='.intval($intSubPropValue).'&TMP_ID='.urlencode($strSubTMP_ID);
require(__DIR__.'/template/subelement_list.php');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>