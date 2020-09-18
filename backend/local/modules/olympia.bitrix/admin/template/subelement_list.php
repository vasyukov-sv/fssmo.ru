<?
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @global CMain $APPLICATION */
/** @global string $strSubIBlockType */
/** @global int $intSubPropValue */
/** @global int $strSubTMP_ID */
/** @global array $arCatalog */
/** @global array $arSubIBlock */
/** @global string $by */
/** @global string $order */
/** @global array $FIELDS_del */
use Bitrix\Main,
	Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

/*
* B_ADMIN_SUBELEMENTS
* if defined and equal 1 - working, another die
* B_ADMIN_SUBELEMENTS_LIST - true/false
* if not defined - die
* if equal true - get list mode
* 	include prolog and epilog
* other - get simple html
*
* need variables
* 		$strSubElementAjaxPath - path for ajax
* 		$strSubIBlockType - iblock type
* 		$arSubIBlockType - iblock type array
* 		$intSubIBlockID - iblock ID
* 		$arSubIBlock	- array with info about iblock
*		$intSubPropValue - ID for filter
*		$strSubTMP_ID - string identifier for link with new product ($intSubPropValue = 0, in edit form send -1)
*
*
*created variables
*		$arSubElements - array subelements for product with ID = 0
*/

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/iblock/admin/iblock_element_admin.php");
IncludeModuleLangFile(__FILE__);

$strSubElementAjaxPath = trim($strSubElementAjaxPath);
$strSubIBlockType = trim($strSubIBlockType);
$intSubIBlockID = (int)$intSubIBlockID;
if ($intSubIBlockID <= 0)
	return;
$boolSubSearch = false;
$subuniq_id = 0;

define("MODULE_ID", "iblock");
define("ENTITY", "CIBlockDocument");
define("DOCUMENT_TYPE", "iblock_".$intSubIBlockID);

if (isset($_REQUEST['mode']) && ($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame'))
	CFile::DisableJSFunction(true);

$intSubPropValue = (int)$intSubPropValue;

$strSubTMP_ID = (int)$strSubTMP_ID;

if ($strSubTMP_ID == 0)
{
	echo ShowError('Нужно создать элемент');

	?>
	<style>
		#tr_PROPERTY_<?=$arPropertyOriginal['ID'] ?> > td:first-child {display: none}
		#tr_PROPERTY_<?=$arPropertyOriginal['ID'] ?> .adm-list-table-cell {padding:11px 16px 10px 16px;}
	</style>
	<?

	return;
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/subelement.php');

$listImageSize = Main\Config\Option::get('iblock', 'list_image_size');
$minImageSize = array("W" => 1, "H"=>1);
$maxImageSize = array(
	"W" => $listImageSize,
	"H" => $listImageSize,
);
unset($listImageSize);

$dbrFProps = CIBlockProperty::GetList(
	array(
		"SORT" => "ASC",
		"NAME" => "ASC"
	),
	array(
		"IBLOCK_ID" => $intSubIBlockID,
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "N"
	)
);

$arProps = array();
while($arProp = $dbrFProps->GetNext())
{
	$arProp["PROPERTY_USER_TYPE"] = ('' != $arProp["USER_TYPE"] ? CIBlockProperty::GetUserType($arProp["USER_TYPE"]) : array());
	$arProps[] = $arProp;
}

$sTableID = "tbl_iblock_sub_element_".md5($strSubIBlockType.".".$intSubIBlockID.".".$intSubPropValue);

$lAdmin = new CAdminSubList($sTableID,false,$strSubElementAjaxPath);

$arOrder = (strtoupper($by) === "ID"? array($by => $order): array($by => $order, "ID" => "ASC"));

// only sku property filter
$arFilterFields = array();

$find_section_section = -1;

$section_id = intval($find_section_section);
$lAdmin->InitFilter($arFilterFields);
$find_section_section = $section_id;
$sThisSectionUrl = '';

// simple filter
$arFilter = array(
	"IBLOCK_ID" => $intSubIBlockID,
);

$arFilter["CHECK_PERMISSIONS"] = "Y";
$arFilter["MIN_PERMISSION"] = "R";

if (isset($values) && count($values))
{
	$arFilter['ID'] = [];

	foreach ($values as $v)
		$arFilter['ID'][] = $v['VALUE'];

	$ids = CIBlockElement::GetList([], ['IBLOCK_ID' => $intSubIBlockID, 'ID' => $arFilter['ID']], false, false, ['ID']);

	$values = [];

	while ($id = $ids->Fetch())
	{
		$values[] = ['VALUE' => $id['ID']];
	}
}
else
	$arFilter['ID'] = 0;

if (defined('B_ADMIN_SUBELEMENTS_LIST') && true === B_ADMIN_SUBELEMENTS_LIST)
{
	if ($lAdmin->EditAction())
	{
		if (is_array($_FILES['FIELDS']))
			CAllFile::ConvertFilesToPost($_FILES['FIELDS'], $_POST['FIELDS']);
		if (is_array($FIELDS_del))
			CAllFile::ConvertFilesToPost($FIELDS_del, $_POST['FIELDS'], "del");

		foreach ($_POST['FIELDS'] as $subID => $arFields)
		{
			if (!$lAdmin->IsUpdated($subID))
				continue;
			$subID = (int)$subID;
			if ($subID <= 0)
				continue;

			$arRes = CIBlockElement::GetByID($subID);
			$arRes = $arRes->Fetch();
			if (!$arRes)
				continue;

			$WF_ID = $subID;

			if (!CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
			{
				$lAdmin->AddUpdateError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				continue;
			}

			if (!is_array($arFields["PROPERTY_VALUES"]))
				$arFields["PROPERTY_VALUES"] = Array();
			$bFieldProps = array();
			foreach ($arFields as $k=>$v)
			{
				if (
					$k != "PROPERTY_VALUES"
					&& strncmp($k, "PROPERTY_", 9) == 0
				)
				{
					$prop_id = substr($k, 9);
					$arFields["PROPERTY_VALUES"][$prop_id] = $v;
					unset($arFields[$k]);
					$bFieldProps[$prop_id]=true;
				}
			}
			if (!empty($bFieldProps))
			{
				//We have to read properties from database in order not to delete its values
				if (!$boolSubWorkFlow)
				{
					$dbPropV = CIBlockElement::GetProperty($intSubIBlockID, $subID, "sort", "asc", Array("ACTIVE"=>"Y"));
					while ($arPropV = $dbPropV->Fetch())
					{
						if (!array_key_exists($arPropV["ID"], $bFieldProps) && $arPropV["PROPERTY_TYPE"] != "F")
						{
							if (!array_key_exists($arPropV["ID"], $arFields["PROPERTY_VALUES"]))
								$arFields["PROPERTY_VALUES"][$arPropV["ID"]] = array();

							$arFields["PROPERTY_VALUES"][$arPropV["ID"]][$arPropV["PROPERTY_VALUE_ID"]] = array(
								"VALUE" => $arPropV["VALUE"],
								"DESCRIPTION" => $arPropV["DESCRIPTION"],
							);
						}
					}
				}
			}
			else
			{
				//We will not update property values
				unset($arFields["PROPERTY_VALUES"]);
			}

			//All not displayed required fields from DB
			foreach ($arSubIBlock["FIELDS"] as $FIELD_ID => $field)
			{
				if (
					$field["IS_REQUIRED"] === "Y"
					&& !array_key_exists($FIELD_ID, $arFields)
					&& $FIELD_ID !== "DETAIL_PICTURE"
					&& $FIELD_ID !== "PREVIEW_PICTURE"
				)
					$arFields[$FIELD_ID] = $arRes[$FIELD_ID];
			}
			if ($arRes["IN_SECTIONS"] == "Y")
			{
				$arFields["IBLOCK_SECTION"] = array();
				$rsSections = CIBlockElement::GetElementGroups($arRes["ID"], true, array('ID', 'IBLOCK_ELEMENT_ID'));
				while ($arSection = $rsSections->Fetch())
					$arFields["IBLOCK_SECTION"][] = $arSection["ID"];
			}

			$arFields["MODIFIED_BY"] = $USER->GetID();
			$ib = new CIBlockElement();
			$DB->StartTransaction();

			if (!$ib->Update($subID, $arFields, true, true, true))
			{
				$lAdmin->AddUpdateError(GetMessage("IBEL_A_SAVE_ERROR", array("#ID#"=>$subID, "#ERROR_TEXT#"=>$ib->LAST_ERROR)), $subID);
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
	}

	if (($arID = $lAdmin->GroupAction()))
	{
		if ($_REQUEST['action_target']=='selected')
		{
			$rsData = CIBlockElement::GetList($arOrder, $arFilter, false, false, array('ID'));
			while($arRes = $rsData->Fetch())
				$arID[] = $arRes['ID'];
		}

		foreach ($arID as $subID)
		{
			$subID = (int)$subID;
			if ($subID <= 0)
				continue;

			$arRes = CIBlockElement::GetByID($subID);
			$arRes = $arRes->Fetch();
			if (!$arRes)
				continue;

			$WF_ID = $subID;

			$bPermissions = true;

			if (!$bPermissions)
			{
				$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
				continue;
			}

			switch($_REQUEST['action'])
			{
				case "delete":
					if (CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_delete"))
					{
						@set_time_limit(0);
						$DB->StartTransaction();
						$APPLICATION->ResetException();
						if (!CIBlockElement::Delete($subID))
						{
							$DB->Rollback();
							if ($ex = $APPLICATION->GetException())
								$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR")." [".$ex->GetString()."]", $subID);
							else
								$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR"), $subID);
						}
						else
						{
							$DB->Commit();

							if ($PRODUCT_ID > 0 && $TMP_ID > 0)
							{
								$parent = CIBlockElement::GetList([], ['ID' => $TMP_ID], false, false, ['ID', 'IBLOCK_ID'])->Fetch();

								if (isset($parent['ID']))
								{
									$VALUES = array();

									$res = CIBlockElement::GetProperty($parent['IBLOCK_ID'], $parent['ID'], "", "", ["ID" => $PRODUCT_ID]);

									while ($ob = $res->Fetch())
									{
										$VALUES[] = $ob['VALUE'];
									}

									$VALUES = array_unique($VALUES);

									$n = array_search($subID, $VALUES);
									if ($n !== false)
									{
										unset($VALUES[$n]);
									}

									$n = array_search($subID, $arFilter['ID']);
									if ($n !== false)
									{
										unset($arFilter['ID'][$n]);
									}

									CIBlockElement::SetPropertyValuesEx($parent['ID'], $parent['IBLOCK_ID'], [$PRODUCT_ID => $VALUES]);
								}
							}
						}
					}
					else
					{
						$lAdmin->AddGroupError(GetMessage("IBLOCK_DELETE_ERROR")." [".$subID."]", $subID);
					}
					break;
				case "activate":
				case "deactivate":
					if (CIBlockElementRights::UserHasRightTo($intSubIBlockID, $subID, "element_edit"))
					{
						$ob = new CIBlockElement();
						$arFields = array("ACTIVE"=>($_REQUEST['action']=="activate"?"Y":"N"));
						if (!$ob->Update($subID, $arFields, true))
							$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR").$ob->LAST_ERROR, $subID);
					}
					else
					{
						$lAdmin->AddGroupError(GetMessage("IBEL_A_UPDERR3")." (ID:".$subID.")", $subID);
					}
					break;
				case 'unpick':

					if ($PRODUCT_ID > 0 && $TMP_ID > 0)
					{
						$parent = CIBlockElement::GetList([], ['ID' => $TMP_ID], false, false, ['ID', 'IBLOCK_ID'])->Fetch();

						if (isset($parent['ID']))
						{
							$VALUES = array();

							$res = CIBlockElement::GetProperty($parent['IBLOCK_ID'], $parent['ID'], "", "", ["ID" => $PRODUCT_ID]);

							while ($ob = $res->Fetch())
							{
								if ($ob['VALUE'] > 0)
									$VALUES[] = $ob['VALUE'];
							}

							$VALUES = array_unique($VALUES);

							$n = array_search($subID, $VALUES);
							if ($n !== false)
							{
								unset($VALUES[$n]);
							}

							if (!count($VALUES))
								$VALUES = '';

							CIBlockElement::SetPropertyValuesEx($parent['ID'], $parent['IBLOCK_ID'], [$PRODUCT_ID => $VALUES]);

							$n = array_search($subID, $arFilter['ID']);
							if ($n !== false)
							{
								unset($arFilter['ID'][$n]);
							}
						}
					}

					break;
			}
		}
	}

	if (isset($_REQUEST['pick']))
	{
		if ($PRODUCT_ID > 0 && $TMP_ID > 0)
		{
			$parent = CIBlockElement::GetList([], ['ID' => $TMP_ID], false, false, ['ID', 'IBLOCK_ID'])->Fetch();

			if (isset($parent['ID']))
			{
				$check = CIBlockElement::GetList([], ['ID' => intval($_REQUEST['pick'])], false, false, ['ID'])->Fetch();

				if (isset($check['ID']))
				{
					$VALUES = array();

					$res = CIBlockElement::GetProperty($parent['IBLOCK_ID'], $parent['ID'], "", "", ["ID" => $PRODUCT_ID]);

					while ($ob = $res->Fetch())
					{
						if ($ob['VALUE'] > 0)
							$VALUES[] = $ob['VALUE'];
					}

					$VALUES[] = $check['ID'];
					$VALUES = array_unique($VALUES);

					$arFilter['ID'][] = $check['ID'];

					CIBlockElement::SetPropertyValuesEx($parent['ID'], $parent['IBLOCK_ID'], [$PRODUCT_ID => $VALUES]);
				}
			}
		}
	}
}

CJSCore::Init(array('translit'));

if (true == B_ADMIN_SUBELEMENTS_LIST)
	CJSCore::Init(array('date'));

$arHeader = array();

$arHeader[] = array("id"=>"NAME", "content"=>GetMessage("IBLOCK_FIELD_NAME"), "sort"=>"name", "default"=>true);

$arHeader[] = array("id"=>"ACTIVE", "content"=>GetMessage("IBLOCK_FIELD_ACTIVE"), "sort"=>"active", "default"=>true, "align"=>"center");
$arHeader[] = array("id"=>"DATE_ACTIVE_FROM", "content"=>GetMessage("IBLOCK_FIELD_DATE_ACTIVE_FROM"), "sort"=>"date_active_from", "align"=>"center");
$arHeader[] = array("id"=>"DATE_ACTIVE_TO", "content"=>GetMessage("IBLOCK_FIELD_DATE_ACTIVE_TO"), "sort"=>"date_active_to", "align"=>"center");
$arHeader[] = array("id"=>"SORT", "content"=>GetMessage("IBLOCK_FIELD_SORT"), "sort"=>"sort", "default"=>true, "align"=>"right");
$arHeader[] = array("id"=>"TIMESTAMP_X", "content"=>GetMessage("IBLOCK_FIELD_TIMESTAMP_X"), "sort"=>"timestamp_x");
$arHeader[] = array("id"=>"USER_NAME", "content"=>GetMessage("IBLOCK_FIELD_USER_NAME"), "sort"=>"modified_by");
$arHeader[] = array("id"=>"DATE_CREATE", "content"=>GetMessage("IBLOCK_EL_ADMIN_DCREATE"), "sort"=>"created");
$arHeader[] = array("id"=>"CREATED_USER_NAME", "content"=>GetMessage("IBLOCK_EL_ADMIN_WCREATE2"), "sort"=>"created_by");

$arHeader[] = array("id"=>"CODE", "content"=>GetMessage("IBEL_A_CODE"), "sort"=>"code");
$arHeader[] = array("id"=>"EXTERNAL_ID", "content"=>GetMessage("IBEL_A_EXTERNAL_ID"), "sort"=>"external_id");
$arHeader[] = array("id"=>"TAGS", "content"=>GetMessage("IBEL_A_TAGS"), "sort"=>"tags");

$arHeader[] = array("id"=>"ID", "content"=>'ID', "sort"=>"id", "default"=>true, "align"=>"right");
$arHeader[] = array("id"=>"SHOW_COUNTER", "content"=>GetMessage("IBEL_A_EXTERNAL_SHOWS"), "sort"=>"show_counter", "align"=>"right");
$arHeader[] = array("id"=>"SHOW_COUNTER_START", "content"=>GetMessage("IBEL_A_EXTERNAL_SHOW_F"), "sort"=>"show_counter_start", "align"=>"right");
$arHeader[] = array("id"=>"PREVIEW_PICTURE", "content"=>GetMessage("IBEL_A_EXTERNAL_PREV_PIC"), "sort" => "has_preview_picture");
$arHeader[] = array("id"=>"PREVIEW_TEXT", "content"=>GetMessage("IBEL_A_EXTERNAL_PREV_TEXT"));
$arHeader[] = array("id"=>"DETAIL_PICTURE", "content"=>GetMessage("IBEL_A_EXTERNAL_DET_PIC"), "sort" => "has_detail_picture");
$arHeader[] = array("id"=>"DETAIL_TEXT", "content"=>GetMessage("IBEL_A_EXTERNAL_DET_TEXT"));

foreach ($arProps as &$arFProps)
{
	$arHeader[] = array("id"=>"PROPERTY_".$arFProps['ID'], "content"=>$arFProps['NAME'], "align"=>($arFProps["PROPERTY_TYPE"]=='N'?"right":"left"), "sort" => ($arFProps["MULTIPLE"]!='Y'? "PROPERTY_".$arFProps['ID'] : ""));
}

if (isset($arFProps))
	unset($arFProps);

$arWFStatus = array();

$lAdmin->AddHeaders($arHeader);

$arSelectedFields = $lAdmin->GetVisibleHeaderColumns();

$arSelectedProps = array();
$arSelect = array();
foreach ($arProps as $i => $arProperty)
{
	$k = array_search("PROPERTY_".$arProperty['ID'], $arSelectedFields);
	if ($k !== false)
	{
		$arSelectedProps[] = $arProperty;
		if ($arProperty["PROPERTY_TYPE"] == "L")
		{
			$arSelect[$arProperty['ID']] = array();
			$rs = CIBlockProperty::GetPropertyEnum($arProperty['ID']);
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = $ar["VALUE"];
		}
		elseif ($arProperty["PROPERTY_TYPE"] == "G")
		{
			$arSelect[$arProperty['ID']] = array();
			$rs = CIBlockSection::GetTreeList(array("IBLOCK_ID"=>$arProperty["LINK_IBLOCK_ID"]), array("ID", "NAME", "DEPTH_LEVEL"));
			while($ar = $rs->GetNext())
				$arSelect[$arProperty['ID']][$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"];
		}
		unset($arSelectedFields[$k]);
	}
}

if (!in_array("ID", $arSelectedFields))
	$arSelectedFields[] = "ID";
if (!in_array("CREATED_BY", $arSelectedFields))
	$arSelectedFields[] = "CREATED_BY";

$arSelectedFields[] = "LANG_DIR";
$arSelectedFields[] = "LID";
$arSelectedFields[] = "WF_PARENT_ELEMENT_ID";

if (in_array("LOCKED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "WF_LOCKED_BY";
if (in_array("USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "MODIFIED_BY";
if (in_array("CREATED_USER_NAME", $arSelectedFields))
	$arSelectedFields[] = "CREATED_BY";
if (in_array("PREVIEW_TEXT", $arSelectedFields))
	$arSelectedFields[] = "PREVIEW_TEXT_TYPE";
if (in_array("DETAIL_TEXT", $arSelectedFields))
	$arSelectedFields[] = "DETAIL_TEXT_TYPE";

$arSelectedFields[] = "LOCK_STATUS";
$arSelectedFields[] = "WF_NEW";
$arSelectedFields[] = "WF_STATUS_ID";
$arSelectedFields[] = "DETAIL_PAGE_URL";
$arSelectedFields[] = "SITE_ID";
$arSelectedFields[] = "CODE";
$arSelectedFields[] = "EXTERNAL_ID";

$arSelectedFieldsMap = array();
foreach ($arSelectedFields as $field)
	$arSelectedFieldsMap[$field] = true;

if (!(false == B_ADMIN_SUBELEMENTS_LIST && $bCopy))
{
	$wf_status_id = "";

	if(isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "excel")
		$arNavParams = false;
	else
		$arNavParams = array("nPageSize"=>CAdminSubResult::GetNavSize($sTableID, 20, $lAdmin->GetListUrl(true)));

	$rsData = CIBlockElement::GetList(
		$arOrder,
		$arFilter,
		false,
		$arNavParams,
		$arSelectedFields
	);
	$rsData = new CAdminSubResult($rsData, $sTableID, $lAdmin->GetListUrl(true));
	$wf_status_id = false;

	$rsData->NavStart();
	$lAdmin->NavText($rsData->GetNavPrint(htmlspecialcharsbx($arSubIBlock["ELEMENTS_NAME"])));

	$arRows = array();
	$arProductGroupIDs = array();

	$boolSubSearch = Loader::includeModule('search');
	
	while ($arRes = $rsData->NavNext(true, "f_"))
	{
		$arRes_orig = $arRes;

		$lockStatus = "";

		$arRes['lockStatus'] = $lockStatus;
		$arRes["orig"] = $arRes_orig;

		$edit_url = str_replace('iblock_', 'olympia_', CIBlock::GetAdminSubElementEditLink(
			$intSubIBlockID,
			$intSubPropValue,
			$arRes_orig['ID'],
			array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID),
			$sThisSectionUrl,
			defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1
		));

		$arRows[$f_ID] = $row = $lAdmin->AddRow($f_ID, $arRes, $edit_url, GetMessage("IB_SE_L_EDIT_ELEMENT"), true);

		$boolEditPrice = false;
		$boolEditPrice = CIBlockElementRights::UserHasRightTo($intSubIBlockID, $f_ID, "element_edit_price");

		$row->AddViewField("ID", $f_ID);

		if ($f_LOCKED_USER_NAME)
			$row->AddViewField("LOCKED_USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_WF_LOCKED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_LOCKED_USER_NAME.'</a>');
		if ($f_USER_NAME)
			$row->AddViewField("USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_MODIFIED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_USER_NAME.'</a>');
		$row->AddViewField("CREATED_USER_NAME", '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$f_CREATED_BY.'" title="'.GetMessage("IBEL_A_USERINFO").'">'.$f_CREATED_USER_NAME.'</a>');

		$row->arRes['props'] = array();
		$arProperties = array();
		if (count($arSelectedProps) > 0)
		{
			$rsProperties = CIBlockElement::GetProperty($intSubIBlockID, $arRes["ID"]);
			while($ar = $rsProperties->GetNext())
			{
				if (!array_key_exists($ar["ID"], $arProperties))
					$arProperties[$ar["ID"]] = array();
				$arProperties[$ar["ID"]][$ar["PROPERTY_VALUE_ID"]] = $ar;
			}
		}

		foreach ($arSelectedProps as $aProp)
		{
			$arViewHTML = array();
			$arEditHTML = array();
			if (strlen($aProp["USER_TYPE"])>0)
				$arUserType = CIBlockProperty::GetUserType($aProp["USER_TYPE"]);
			else
				$arUserType = array();
			$max_file_size_show=100000;

			$last_property_id = false;
			foreach ($arProperties[$aProp["ID"]] as $prop_id => $prop)
			{
				$prop['PROPERTY_VALUE_ID'] = intval($prop['PROPERTY_VALUE_ID']);
				$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].'][DESCRIPTION]';
				//View part
				if (array_key_exists("GetAdminListViewHTML", $arUserType))
				{
					$arViewHTML[] = call_user_func_array($arUserType["GetAdminListViewHTML"],
						array(
							$prop,
							array(
								"VALUE" => $prop["~VALUE"],
								"DESCRIPTION" => $prop["~DESCRIPTION"]
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif ($prop['PROPERTY_TYPE']=='N')
					$arViewHTML[] = $prop["VALUE"];
				elseif ($prop['PROPERTY_TYPE']=='S')
					$arViewHTML[] = $prop["VALUE"];
				elseif ($prop['PROPERTY_TYPE']=='L')
					$arViewHTML[] = $prop["VALUE_ENUM"];
				elseif ($prop['PROPERTY_TYPE']=='F')
				{
					$arViewHTML[] = CFileInput::Show('NO_FIELDS['.$prop['PROPERTY_VALUE_ID'].']', $prop["VALUE"], array(
						"IMAGE" => "Y",
						"PATH" => "Y",
						"FILE_SIZE" => "Y",
						"DIMENSIONS" => "Y",
						"IMAGE_POPUP" => "Y",
						"MAX_SIZE" => $maxImageSize,
						"MIN_SIZE" => $minImageSize,
						), array(
							'upload' => false,
							'medialib' => false,
							'file_dialog' => false,
							'cloud' => false,
							'del' => false,
							'description' => false,
						)
					);
				}
				elseif ($prop['PROPERTY_TYPE']=='G')
				{
					if (intval($prop["VALUE"])>0)
					{
						$rsSection = CIBlockSection::GetList(
							array(),
							array("ID" => $prop["VALUE"]),
							false,
							array('ID', 'NAME', 'IBLOCK_ID')
						);
						if ($arSection = $rsSection->GetNext())
						{
							$arViewHTML[] = $arSection['NAME'].
							' [<a href="'.
							htmlspecialcharsbx(CIBlock::GetAdminSectionEditLink($arSection['IBLOCK_ID'], $arSection['ID'])).
							'" title="'.GetMessage("IBEL_A_SEC_EDIT").'">'.$arSection['ID'].'</a>]';
						}
					}
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					if ($t = GetElementName($prop["VALUE"]))
					{
						$arViewHTML[] = $t['NAME'].
						' [<a href="'.htmlspecialcharsbx(CIBlock::GetAdminElementEditLink($t['IBLOCK_ID'], $t['ID'], array(
						'WF' => 'Y'
						))).'" title="'.GetMessage("IBEL_A_EL_EDIT").'">'.$t['ID'].'</a>]';
					}
				}
				//Edit Part
				$bUserMultiple = $prop["MULTIPLE"] == "Y" && array_key_exists("GetPropertyFieldHtmlMulty", $arUserType);
				if ($bUserMultiple)
				{
					if ($last_property_id != $prop["ID"])
					{
						$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']';
						$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtmlMulty"], array(
							$prop,
							$arProperties[$prop["ID"]],
							array(
								"VALUE" => $VALUE_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							)
						));
					}
				}
				elseif (array_key_exists("GetPropertyFieldHtml", $arUserType))
				{
					$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtml"],
						array(
							$prop,
							array(
								"VALUE" => $prop["VALUE"],
								"DESCRIPTION" => $prop["DESCRIPTION"],
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif ($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if ($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'">'.$prop["VALUE"].'</textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="'.$prop["COL_COUNT"].'">';
					if ($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC").'">'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC_1").
							'<input type="text" name="'.$DESCR_NAME.'" value="'.$prop["DESCRIPTION"].'" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='L' && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach ($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if ($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					if ($prop['LIST_TYPE']=='C')
					{
						if ($prop['MULTIPLE'] == "Y" || count($arSelect[$prop['ID']]) == 1)
						{
							$html = '<input type="hidden" name="'.$VALUE_NAME.'" value="">';
							foreach ($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="checkbox" name="'.$VALUE_NAME.'" id="subid'.$subuniq_id.'" value="'.$value.'"';
								if (array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="subid'.$subuniq_id.'">'.$display.'</label><br>';
								$subuniq_id++;
							}
						}
						else
						{
							$html = '<input type="radio" name="'.$VALUE_NAME.'" id="subid'.$subuniq_id.'" value=""';
							if (count($arValues) < 1)
								$html .= ' checked';
							$html .= '>&nbsp;<label for="subid'.$subuniq_id.'">'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</label><br>';
							$subuniq_id++;
							foreach ($arSelect[$prop['ID']] as $value => $display)
							{
								$html .= '<input type="radio" name="'.$VALUE_NAME.'" id="subid'.$subuniq_id.'" value="'.$value.'"';
								if (array_key_exists($value, $arValues))
									$html .= ' checked';
								$html .= '>&nbsp;<label for="subid'.$subuniq_id.'">'.$display.'</label><br>';
								$subuniq_id++;
							}
						}
					}
					else
					{
						$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
						$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
						foreach ($arSelect[$prop['ID']] as $value => $display)
						{
							$html .= '<option value="'.$value.'"';
							if (array_key_exists($value, $arValues))
								$html .= ' selected';
							$html .= '>'.$display.'</option>'."\n";
						}
						$html .= "</select>\n";
					}
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='F' && ($last_property_id!=$prop["ID"]))
				{
					if($prop['MULTIPLE'] == "Y")
					{
						$arOneFileControl = array();
						foreach($arProperties[$prop["ID"]] as $g_prop)
						{
							$arOneFileControl[] = CFileInput::Show(
								'NO_FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$g_prop['PROPERTY_VALUE_ID'].']',
								$g_prop["VALUE"],
								array(
									"IMAGE" => "Y",
									"PATH" => "Y",
									"FILE_SIZE" => "Y",
									"DIMENSIONS" => "Y",
									"IMAGE_POPUP" => "Y",
									"MAX_SIZE" => $maxImageSize,
									"MIN_SIZE" => $minImageSize,
								),
								false,
								array(
									'upload' => false,
									'medialib' => false,
									'file_dialog' => false,
									'cloud' => false,
									'del' => false,
									'description' => false,
								)
							);
						}
						if (!empty($arOneFileControl))
						{
							$arEditHTML[] = implode('<br>', $arOneFileControl);
						}
					}
					else
					{
						$arEditHTML[] = CFileInput::Show(
							$VALUE_NAME,
							$prop["VALUE"],
							array(
								"IMAGE" => "Y",
								"PATH" => "Y",
								"FILE_SIZE" => "Y",
								"DIMENSIONS" => "Y",
								"IMAGE_POPUP" => "Y",
								"MAX_SIZE" => $maxImageSize,
								"MIN_SIZE" => $minImageSize,
								),
							array(
								'upload' => false,
								'medialib' => false,
								'file_dialog' => false,
								'cloud' => false,
								'del' => false,
								'description' => false,
							)
						);
					}
				}
				elseif (($prop['PROPERTY_TYPE']=='G') && ($last_property_id!=$prop["ID"]))
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][]';
					$arValues = array();
					foreach ($arProperties[$prop["ID"]] as $g_prop)
					{
						$g_prop = intval($g_prop["VALUE"]);
						if ($g_prop > 0)
							$arValues[$g_prop] = $g_prop;
					}
					$html = '<select name="'.$VALUE_NAME.'" size="'.$prop["MULTIPLE_CNT"].'" '.($prop["MULTIPLE"]=="Y"?"multiple":"").'>';
					$html .= '<option value=""'.(count($arValues) < 1? ' selected': '').'>'.GetMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
					foreach ($arSelect[$prop['ID']] as $value => $display)
					{
						$html .= '<option value="'.$value.'"';
						if (array_key_exists($value, $arValues))
							$html .= ' selected';
						$html .= '>'.$display.'</option>'."\n";
					}
					$html .= "</select>\n";
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].']['.$prop['PROPERTY_VALUE_ID'].']';
					if ($t = GetElementName($prop["VALUE"]))
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="'.$prop["VALUE"].'" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" >'.$t['NAME'].'</span>';
					}
					else
					{
						$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
					}
				}
				$last_property_id = $prop['ID'];
			}
			$table_id = md5($f_ID.':'.$aProp['ID']);
			if ($aProp["MULTIPLE"] == "Y")
			{
				$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][VALUE]';
				$DESCR_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0][DESCRIPTION]';
				if (array_key_exists("GetPropertyFieldHtmlMulty", $arUserType))
				{
				}
				elseif (('F' != $prop['PROPERTY_TYPE']) && array_key_exists("GetPropertyFieldHtml", $arUserType))
				{
					$arEditHTML[] = call_user_func_array($arUserType["GetPropertyFieldHtml"],
						array(
							$prop,
							array(
								"VALUE" => "",
								"DESCRIPTION" => "",
							),
							array(
								"VALUE" => $VALUE_NAME,
								"DESCRIPTION" => $DESCR_NAME,
								"MODE"=>"iblock_element_admin",
								"FORM_NAME"=>"form_".$sTableID,
							),
						));
				}
				elseif ($prop['PROPERTY_TYPE']=='N' || $prop['PROPERTY_TYPE']=='S')
				{
					if ($prop["ROW_COUNT"] > 1)
						$html = '<textarea name="'.$VALUE_NAME.'" cols="'.$prop["COL_COUNT"].'" rows="'.$prop["ROW_COUNT"].'"></textarea>';
					else
						$html = '<input type="text" name="'.$VALUE_NAME.'" value="" size="'.$prop["COL_COUNT"].'">';
					if ($prop["WITH_DESCRIPTION"] == "Y")
						$html .= ' <span title="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC").'">'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_DESC_1").'<input type="text" name="'.$DESCR_NAME.'" value="" size="18"></span>';
					$arEditHTML[] = $html;
				}
				elseif ($prop['PROPERTY_TYPE']=='F')
				{
				}
				elseif ($prop['PROPERTY_TYPE']=='E')
				{
					$VALUE_NAME = 'FIELDS['.$f_ID.'][PROPERTY_'.$prop['ID'].'][n0]';
					$arEditHTML[] = '<input type="text" name="'.$VALUE_NAME.'" id="'.$VALUE_NAME.'" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$prop["LINK_IBLOCK_ID"].'&amp;n='.urlencode($VALUE_NAME).'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$VALUE_NAME.'" ></span>';
				}

				if ($prop["PROPERTY_TYPE"]!=="F" && $prop["PROPERTY_TYPE"]!=="G" && $prop["PROPERTY_TYPE"]!=="L" && !$bUserMultiple)
					$arEditHTML[] = '<input type="button" value="'.GetMessage("IBLOCK_ELEMENT_EDIT_PROP_ADD").'" onClick="addNewRow(\'tb'.$table_id.'\')">';
			}
			if (count($arViewHTML) > 0)
				$row->AddViewField("PROPERTY_".$aProp['ID'], implode(" / ", $arViewHTML)."&nbsp;");
			if (count($arEditHTML) > 0)
				$row->arRes['props']["PROPERTY_".$aProp['ID']] = array("table_id"=>$table_id, "html"=>$arEditHTML);
		}
	}

	$boolIBlockElementAdd = CIBlockSectionRights::UserHasRightTo($intSubIBlockID, $find_section_section, "section_element_bind");

	$arElementOps = CIBlockElementRights::UserHasRightTo(
		$intSubIBlockID,
		array_keys($arRows),
		"",
		CIBlockRights::RETURN_OPERATIONS
	);

	/** @var CAdminListRow $row */
	foreach ($arRows as $f_ID => $row)
	{
		$edit_url = CIBlock::GetAdminSubElementEditLink(
			$intSubIBlockID,
			$intSubPropValue,
			$row->arRes['orig']['ID'],
			array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID),
			$sThisSectionUrl,
			defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1
		);

		if (array_key_exists("PREVIEW_PICTURE", $arSelectedFieldsMap))
		{
			$row->AddFileField("PREVIEW_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				), array(
					'upload' => false,
					'medialib' => false,
					'file_dialog' => false,
					'cloud' => false,
					'del' => false,
					'description' => false,
				)
			);
		}
		if (array_key_exists("DETAIL_PICTURE", $arSelectedFieldsMap))
		{
			$row->AddFileField("DETAIL_PICTURE", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => $maxImageSize,
				"MIN_SIZE" => $minImageSize,
				), array(
					'upload' => false,
					'medialib' => false,
					'file_dialog' => false,
					'cloud' => false,
					'del' => false,
					'description' => false,
				)
			);
		}
		if (array_key_exists("PREVIEW_TEXT", $arSelectedFieldsMap))
			$row->AddViewField("PREVIEW_TEXT", ($row->arRes["PREVIEW_TEXT_TYPE"]=="text" ? htmlspecialcharsex($row->arRes["PREVIEW_TEXT"]) : HTMLToTxt($row->arRes["PREVIEW_TEXT"])));
		if (array_key_exists("DETAIL_TEXT", $arSelectedFieldsMap))
			$row->AddViewField("DETAIL_TEXT", ($row->arRes["DETAIL_TEXT_TYPE"]=="text" ? htmlspecialcharsex($row->arRes["DETAIL_TEXT"]) : HTMLToTxt($row->arRes["DETAIL_TEXT"])));

		if (isset($arElementOps[$f_ID]) && isset($arElementOps[$f_ID]["element_edit"]))
		{
			$row->AddCheckField("ACTIVE");
			$row->AddInputField("NAME", array('size'=>'35'));
			$row->AddViewField("NAME", '<div class="iblock_menu_icon_elements"></div>'.htmlspecialcharsex($row->arRes["NAME"]));
			$row->AddInputField("SORT", array('size'=>'3'));
			$row->AddInputField("CODE");
			$row->AddInputField("EXTERNAL_ID");
			if ($boolSubSearch)
			{
				$row->AddViewField("TAGS", htmlspecialcharsex($row->arRes["TAGS"]));
				$row->AddEditField("TAGS", InputTags("FIELDS[".$f_ID."][TAGS]", $row->arRes["TAGS"], $arSubIBlock["SITE_ID"]));
			}
			else
			{
				$row->AddInputField("TAGS");
			}
			if ($arWFStatus)
			{
				$row->AddSelectField("WF_STATUS_ID", $arWFStatus);
				if ($row->arRes['orig']['WF_NEW']=='Y' || $row->arRes['WF_STATUS_ID']=='1')
					$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]));
				else
					$row->AddViewField("WF_STATUS_ID", '<a href="'.$edit_url.'" title="'.GetMessage("IBEL_A_ED_TITLE").'">'.htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]).'</a> / <a href="'.'iblock_element_edit.php?ID='.$row->arRes['orig']['ID'].(!isset($arElementOps[$f_ID]) || !isset($arElementOps[$f_ID]["element_edit_any_wf_status"])?'&view=Y':'').$sThisSectionUrl.'" title="'.GetMessage("IBEL_A_ED2_TITLE").'">'.htmlspecialcharsex($arWFStatus[$row->arRes['orig']['WF_STATUS_ID']]).'</a>');
			}
			if (array_key_exists("PREVIEW_TEXT", $arSelectedFieldsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_ID.'][PREVIEW_TEXT_TYPE]" value="text" id="'.$f_ID.'PREVIEWtext"';
				if ($row->arRes["PREVIEW_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'PREVIEWtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_ID.'][PREVIEW_TEXT_TYPE]" value="html" id="'.$f_ID.'PREVIEWhtml"';
				if ($row->arRes["PREVIEW_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'PREVIEWhtml">html</label><br>';
				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_ID.'][PREVIEW_TEXT]">'.htmlspecialcharsbx($row->arRes["PREVIEW_TEXT"]).'</textarea>';
				$row->AddEditField("PREVIEW_TEXT", $sHTML);
			}
			if (array_key_exists("DETAIL_TEXT", $arSelectedFieldsMap))
			{
				$sHTML = '<input type="radio" name="FIELDS['.$f_ID.'][DETAIL_TEXT_TYPE]" value="text" id="'.$f_ID.'DETAILtext"';
				if ($row->arRes["DETAIL_TEXT_TYPE"]!="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'DETAILtext">text</label> /';
				$sHTML .= '<input type="radio" name="FIELDS['.$f_ID.'][DETAIL_TEXT_TYPE]" value="html" id="'.$f_ID.'DETAILhtml"';
				if ($row->arRes["DETAIL_TEXT_TYPE"]=="html")
					$sHTML .= ' checked';
				$sHTML .= '><label for="'.$f_ID.'DETAILhtml">html</label><br>';

				$sHTML .= '<textarea rows="10" cols="50" name="FIELDS['.$f_ID.'][DETAIL_TEXT]">'.htmlspecialcharsbx($row->arRes["DETAIL_TEXT"]).'</textarea>';
				$row->AddEditField("DETAIL_TEXT", $sHTML);
			}
			foreach ($row->arRes['props'] as $prop_id => $arEditHTML)
				$row->AddEditField($prop_id, '<table id="tb'.$arEditHTML['table_id'].'" border=0 cellpadding=0 cellspacing=0><tr><td nowrap>'.implode("</td></tr><tr><td nowrap>", $arEditHTML['html']).'</td></tr></table>');
		}
		else
		{
			$row->AddCheckField("ACTIVE", false);
			$row->AddViewField("NAME", '<div class="iblock_menu_icon_elements"></div>'.htmlspecialcharsex($row->arRes["NAME"]));
			$row->AddInputField("SORT", false);
			$row->AddInputField("CODE", false);
			$row->AddInputField("EXTERNAL_ID", false);
			$row->AddViewField("TAGS", htmlspecialcharsex($row->arRes["TAGS"]));
			if ($arWFStatus)
			{
				$row->AddViewField("WF_STATUS_ID", htmlspecialcharsex($arWFStatus[$row->arRes['WF_STATUS_ID']]));
			}
		}

		$arActions = array();

		$subElementEdit = CIBlock::GetAdminSubElementEditLink(
			$intSubIBlockID,
			$intSubPropValue,
			$row->arRes['orig']['ID'],
			array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID),
			$sThisSectionUrl,
			defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1
		);
		$actionEdit = "{
			'content_url': '".str_replace('iblock_', 'olympia_', $subElementEdit)."',
			'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y&".bitrix_sessid_get()."',
			'draggable': true,
			'resizable': true,
			'width': 900,
			'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
		}";

		$subElementCopy = CIBlock::GetAdminSubElementEditLink(
			$intSubIBlockID,
			$intSubPropValue,
			$row->arRes['orig']['ID'],
			array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID, 'action' => 'copy'),
			$sThisSectionUrl,
			defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1
		);
		$actionCopy = "{
			'content_url': '".str_replace('iblock_', 'olympia_', $subElementCopy)."',
			'content_post': '".(!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) ? '&bxsku=Y' : '')."&bxpublic=Y&".bitrix_sessid_get()."',
			'draggable': true,
			'resizable': true,
			'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
		}";

		if($row->arRes['ACTIVE'] == 'Y')
		{
			$arActive = array(
				"TEXT" => GetMessage("IBSEL_A_DEACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "deactivate", $sThisSectionUrl),
				"ONCLICK" => "",
			);
		}
		else
		{
			$arActive = array(
				"TEXT" => GetMessage("IBSEL_A_ACTIVATE"),
				"ACTION" => $lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "activate", $sThisSectionUrl),
				"ONCLICK" => "",
			);
		}
		$clearCounter = array(
			"TEXT" => GetMessage('IBSEL_A_CLEAR_COUNTER'),
			"TITLE" => GetMessage('IBSEL_A_CLEAR_COUNTER_TITLE'),
			"ACTION" => "if (confirm('".GetMessageJS("IBSEL_A_CLEAR_COUNTER_CONFIRM")."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "clear_counter", $sThisSectionUrl),
			"ONCLICK" => ""
		);


		{
			if (
				isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_edit"])
			)
			{
				$arActions[] = array(
					"ICON" => "edit",
					"TEXT" => GetMessage("IBEL_A_CHANGE"),
					"DEFAULT" => true,
					"ACTION"=>"(new BX.CAdminDialog(".$actionEdit.")).Show();",
				);
				$arActions[] = $arActive;
				$arActions[] = array('SEPARATOR' => 'Y');
				$arActions[] = $clearCounter;
				$arActions[] = array('SEPARATOR' => 'Y');
			}

			if ($boolIBlockElementAdd && isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_edit"]))
			{
				$arActions[] = array(
					"ICON" => "copy",
					"TEXT" => GetMessage("IBEL_A_COPY_ELEMENT"),
					"ACTION"=>"(new BX.CAdminDialog(".$actionCopy.")).Show();",
				);
			}

			$arActions[] = array("SEPARATOR"=>true);
			$arActions[] = array(
				"ICON" => "delete",
				"TEXT" => GetMessage('IBEL_A_UNPICK_ELEMENT'),
				"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
				"ACTION" => "if (confirm('".GetMessageJS('IBEL_A_UNPICK_ELEMENT_MESSAGE')."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "unpick", $sThisSectionUrl)
			);

			if (
				isset($arElementOps[$f_ID])
				&& isset($arElementOps[$f_ID]["element_delete"])
			)
			{
				$arActions[] = array("SEPARATOR"=>true);
				$arActions[] = array(
					"ICON" => "delete",
					"TEXT" => GetMessage('MAIN_DELETE'),
					"TITLE" => GetMessage("IBLOCK_DELETE_ALT"),
					"ACTION" => "if (confirm('".GetMessageJS('IBLOCK_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($row->arRes['orig']['ID'], "delete", $sThisSectionUrl)
				);
			}
		}

		if (!empty($arActions))
			$row->AddActions($arActions);
	}

	$lAdmin->AddFooter(
		array(
			array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
			array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
		)
	);

	$arGroupActions = array();
	foreach ($arElementOps as $id => $arOps)
	{
		if (isset($arOps["element_delete"]))
		{
			$arGroupActions["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");
			break;
		}
	}
	foreach ($arElementOps as $id => $arOps)
	{
		if (isset($arOps["element_edit"]))
		{
			$arGroupActions["activate"] = GetMessage("MAIN_ADMIN_LIST_ACTIVATE");
			$arGroupActions["deactivate"] = GetMessage("MAIN_ADMIN_LIST_DEACTIVATE");
			$arGroupActions['clear_counter'] = strtolower(GetMessage('IBSEL_A_CLEAR_COUNTER'));
			break;
		}
	}

	$arParams = array('disable_action_sub_target' => true);
	
	$lAdmin->AddGroupActionTable($arGroupActions, $arParams);

?>
<script type="text/javascript">
	function CheckProductName_<?=$intSubPropValue ?>(id)
	{
		if (!id)
			return false;
		var obj = BX(id),
			obFormElement;
		if (!obj)
			return false;
		obj.blur();
		obFormElement = BX.findParent(obj,{tag: 'form'});
		if (!obFormElement)
			return false;
		if ((obFormElement.elements['NAME']) && (0 < obFormElement.elements['NAME'].value.length))
			return obFormElement.elements['NAME'].value;
		else
			return false;

	}
	function ShowNewOffer_<?=$intSubPropValue ?>(id)
	{
		var mxProductName = CheckProductName_<?=$intSubPropValue ?>(id),
			PostParams;
		if (!mxProductName)
			alert('<? echo CUtil::JSEscape(GetMessage('IB_SE_L_ENTER_PRODUCT_NAME')); ?>');
		else
		{
			PostParams = {};
			PostParams.bxpublic = 'Y';
			<? if (!(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1))
			{
				?>PostParams.bxsku = 'Y';<?
			}
			?>
			PostParams.PRODUCT_NAME = mxProductName;
			PostParams.sessid = BX.bitrix_sessid();
			(new BX.CAdminDialog({
				'content_url': '<? echo str_replace('iblock_', 'olympia_', CIBlock::GetAdminSubElementEditLink($intSubIBlockID, $intSubPropValue, 0, array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID), $sThisSectionUrl, defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)); ?>',
				'content_post': PostParams,
				'draggable': true,
				'resizable': true,
				'width': 900,
				'buttons': [BX.CAdminDialog.btnSave, BX.CAdminDialog.btnCancel]
			})).Show();
		}
	}

	BX.ready(function()
	{
		BX.unbindAll(BX('PROP[<?=$arPropertyOriginal['ID'] ?>][n0]'));
		BX.bind(BX('PROP[<?=$arPropertyOriginal['ID'] ?>][n0]'), 'change', function()
		{
			BX.ajax.post(
				'<? echo str_replace('iblock_subelement_edit', 'olympia_subelement_admin', CIBlock::GetAdminSubElementEditLink($intSubIBlockID, $intSubPropValue, 0, array('WF' => 'Y', 'TMP_ID' => $strSubTMP_ID), $sThisSectionUrl, defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)); ?>',
				{'pick': this.value},
				function ()
				{
					ReloadSubList();
				}
			);
		});
	});
	
	BX.CDialog.prototype.defaultParams.width = 900;

</script>

<?

$prolog = '
<style>
	#tr_PROPERTY_'.$arPropertyOriginal['ID'].' > td:first-child {display: none}
	#tr_PROPERTY_'.$arPropertyOriginal['ID'].' .adm-list-table-cell {padding:11px 16px 10px 16px;}
</style>';

	foreach ($values as $id => $value)
		$prolog .= '<input type="hidden" name="PROP['.$arPropertyOriginal['ID'].']['.$id.']" value="'.$value['VALUE'].'">';

	$prolog .= '<input type="hidden" name="PROP['.$arPropertyOriginal['ID'].'][n0]" id="PROP['.$arPropertyOriginal['ID'].'][n0]" value="">';
?>
<?

	$lAdmin->sPrologContent = $prolog;

	$aContext = array();
	if ($boolIBlockElementAdd)
	{
		$aContext[] = array(
			"ICON" => "btn_sub_new",
			"TEXT" => htmlspecialcharsex('' != trim($arSubIBlock["ELEMENT_ADD"]) ? $arSubIBlock["ELEMENT_ADD"] : GetMessage('IB_SE_L_ADD_NEW_ELEMENT')),
			"LINK" => "javascript:ShowNewOffer_".$intSubPropValue."('btn_sub_new')",
			"TITLE" => GetMessage("IB_SE_L_ADD_NEW_ELEMENT_DESCR")
		);

		$aContext[] = array(
			"ICON" => "btn_sub_new",
			"TEXT" => htmlspecialcharsex(GetMessage('IB_SE_L_ADD_PICK_ELEMENT')),
			"LINK" => "javascript:jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=".$intSubIBlockID."&n=PROP[".$intSubPropValue."]&k=n0&iblockfix=y', 900, 700);",
			"TITLE" => GetMessage("IB_SE_L_ADD_PICK_ELEMENT_DESCR")
		);
	}

	$aContext[] = array(
		"ICON"=>"btn_sub_refresh",
		"TEXT"=>htmlspecialcharsex(GetMessage('IB_SE_L_REFRESH_ELEMENTS')),
		"LINK" => "javascript:".$lAdmin->ActionAjaxReload($lAdmin->GetListUrl(true)),
		"TITLE"=>GetMessage("IB_SE_L_REFRESH_ELEMENTS_DESCR"),
	);

	$lAdmin->AddAdminContextMenu($aContext);

	$lAdmin->CheckListMode();

	$lAdmin->DisplayList(B_ADMIN_SUBELEMENTS_LIST);
}
else
{
	ShowMessage(GetMessage('IB_SE_L_SHOW_PRICES_AFTER_COPY'));
}
?>
