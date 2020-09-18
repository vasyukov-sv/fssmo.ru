<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Olympia\Fssmo\Db\External\ShootersTable;
use Olympia\Fssmo\Helpers;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$POST_RIGHT = $APPLICATION->GetGroupRight("olympia.fssmo");

if ($POST_RIGHT == 'D')
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));

if (isset($_REQUEST["FN"]))
{
	$FN = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FN"]);
	$FC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FC"]);

	if($FN == "")
		$FN = "find_form";
	if($FC == "")
		$FC = "USER_ID";

	if (isset($_REQUEST['JSFUNC']))
		$JSFUNC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST['JSFUNC']);
	else
		$JSFUNC = '';
}

$oSort = new CAdminSorting($sTableID, 'id', 'desc');

$lAdmin = new CAdminUiList('shooters_list', $oSort);
$lAdmin->bMultipart = true;

$arFilter = [];
$filterFields = [
	['id' => 'id', 'name' => 'ID', 'type' => 'integer'],
	['id' => 'FirstName', 'name' => 'Имя', 'type' => 'string'],
	['id' => 'LastName', 'name' => 'Фамилия', 'type' => 'string'],
	['id' => 'MiddleName', 'name' => 'Отчество', 'type' => 'string'],
	['id' => 'Phone', 'name' => 'Телефон', 'type' => 'string'],
	['id' => 'Email', 'name' => 'Email', 'type' => 'string'],
	['id' => 'City', 'name' => 'Город', 'type' => 'string'],
	['id' => 'UserId', 'name' => 'Пользователь сайта', 'type' => 'custom_entity', 'selector' => ['type' => 'user']],
];

$lAdmin->AddFilter($filterFields, $arFilter);

$lAdmin->AddHeaders([
	['id' => 'id', 'content' => 'ID', 'sort' => 'id', 'default' => true],
	['id' => 'FirstName', 'content' => 'Имя', 'sort' => 'FirstName', 'default' => true],
	['id' => 'MiddleName', 'content' => 'Отчество', 'sort' => 'MiddleName', 'default' => false],
	['id' => 'LastName', 'content' => 'Фамилия', 'sort' => 'LastName', 'default' => true],
	['id' => 'Gender', 'content' => 'Пол', 'sort' => 'Gender', 'default' => false],
	['id' => 'BirthDay', 'content' => 'Дата Рождения', 'sort' => 'BirthDay', 'default' => true],
	['id' => 'CountryId', 'content' => 'Страна', 'sort' => 'CountryId', 'default' => false],
	['id' => 'City', 'content' => 'Город', 'sort' => 'City', 'default' => true],
	['id' => 'Phone', 'content' => 'Телефон', 'sort' => 'Phone', 'default' => true],
	['id' => 'Email', 'content' => 'Email', 'sort' => 'Email', 'default' => true],
	['id' => 'ClubId', 'content' => 'Клуб', 'sort' => 'ClubId', 'default' => true],
	['id' => 'UserId', 'content' => 'Пользователь сайта', 'sort' => 'UserId', 'default' => true],
]);

$lAdmin->AddVisibleHeaderColumn('id');

$visibleColumnsMap = array_fill_keys($lAdmin->GetVisibleHeaderColumns(), true);
$selectedFields = array_keys($visibleColumnsMap);

$grid_options = new Bitrix\Main\Grid\Options('shooters_list');
$sort = $grid_options->GetSorting(['sort' => ['id' => 'desc'], 'vars' => ['by' => 'by', 'order' => 'order']]);

$rows = [];

if (in_array('ClubId', $selectedFields))
	$selectedFields[] = 'Club.ClubName';

if (in_array('CountrybId', $selectedFields))
	$selectedFields[] = 'Country.Country';

if (isset($arFilter['UserId']))
{
	$user = UserTable::query()
		->setSelect(['ID', 'XML_ID'])
		->setFilter(['ID' => (int) $arFilter['UserId']])
		->exec()->fetch();

	if ($user && Helpers::isGUID($user['XML_ID']))
		$arFilter['UserId'] = $user['XML_ID'];
	else
		$arFilter['UserId'] = '';
}

$getList = [
	'select' => $selectedFields,
	'filter' => $arFilter
];

$query = new \CAdminUiResult(null, 'shooters_list');
$query->setNavParams('shooters_list', ShootersTable::class, $getList);

$getList['order'] = $sort['sort'];

$shooters = ShootersTable::query()
	->setOrder($getList['order'])
	->setSelect($getList['select'])
	->setFilter($getList['filter'])
	->setOffset($getList['offset'])
	->setLimit($getList['limit'])
	->exec();

$query->NavStart();

$lAdmin->SetNavigationParams($query, []);

/** @var ShootersTable $shooter */
foreach ($shooters as $shooter)
{
	$user = null;

	if ($shooter->UserId !== '')
	{
		$user = UserTable::query()
			->setSelect(['ID', 'NAME', 'LAST_NAME'])
			->setFilter(['XML_ID' => $shooter->UserId])
			->exec()->fetch();
	}

	$row = [
		'id' => $shooter->id,
		'FirstName' => $shooter->FirstName,
		'MiddleName' => $shooter->MiddleName,
		'LastName' => $shooter->LastName,
		'Gender' => $shooter->GenderId === $shooter::GENDER_MALE ? 'Мужской' : ($shooter->GenderId === $shooter::GENDER_FEMALE ? 'Женский' : ''),
		'BirthDay' => $shooter->BirthDay,
		'CountryId' => $shooter->Country->Country,
		'City' => $shooter->City,
		'Phone' => $shooter->Phone,
		'Email' => $shooter->Email,
		'ClubId' => $shooter->Club->ClubName,
		'UserId' => $shooter->UserId,
	];

	$row = $lAdmin->AddRow($shooter->id, $row, '', GetMessage("IBLIST_A_EDIT"));

	$row->AddViewField('UserId', $user ? sprintf(
		'[<a href="user_edit.php?lang=%s&ID=%d" target="_blank">%d</a>] %s %s',
		LANGUAGE_ID,
		$user['ID'],
		$user['ID'],
		$user['NAME'],
		$user['LAST_NAME']
	) : '');

	$arActions = array();
	$arActions[] = array(
		"ICON"=>"",
		"TEXT"=>"Выбрать",
		"DEFAULT"=>true,
		"ACTION"=>"SetValue('".$shooter->id."');"
	);
	$row->AddActions($arActions);

	$rows[] = [
		'data' => [],
		'actions' => []
	];
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=> $query->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle('Список стрелков');

if (!isset($FN))
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
else
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_popup_admin.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(['ACTION_PANEL' => true]);

if (isset($FN))
{
?>

<script language="JavaScript">
<!--
function SetValue(id)
{
	<?if ($JSFUNC <> ''){?>
	window.opener.SUV<?=$JSFUNC?>(id);
	<?}else{?>
	window.opener.document.<?echo $FN;?>["<?echo $FC;?>"].value=id;
	if (window.opener.BX)
		window.opener.BX.fireEvent(window.opener.document.<?echo $FN;?>["<?echo $FC;?>"], 'change');
	window.close();
	<?}?>
}
//-->
</script>

<?
}

if (!isset($FN))
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
else
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");