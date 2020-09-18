<?php

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Olympia\Fssmo\Db\External\UserProfilesTable;
use Olympia\Fssmo\Model\CompetitionsPriceTable;
use Olympia\Fssmo\Model\CompetitionsTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$POST_RIGHT = $APPLICATION->GetGroupRight("olympia.fssmo");

if ($POST_RIGHT == 'D')
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));

$oSort = new CAdminSorting($sTableID, 'id', 'desc');
$lAdmin = new CAdminUiList('registration_list', $oSort);
$lAdmin->bMultipart = true;

$offerCategories = [];

$items = PropertyEnumerationTable::query()
	->setSelect(['ID', 'VALUE'])
	->setFilter(['=PROPERTY.IBLOCK_ID' => IBLOCK_COMPETITIONS_PRICE, 'PROPERTY.CODE' => 'CATEGORY'])
	->exec();

foreach ($items as $item)
	$offerCategories[$item['ID']] = $item['VALUE'];

$competitions = [];

$items = CompetitionsTable::query()
	->setOrder(['PROPERTY.DATE_FROM' => 'DESC'])
	->setSelect(['ID', 'NAME', 'PROPERTY.DATE_FROM'])
	->setFilter(['=ACTIVE' => 'Y'])
	->exec();

foreach ($items as $item)
	$competitions[$item['ID']] = $item['NAME'].' - '.date('d.m.Y', strtotime($item['PROPERTY_DATE_FROM']));

$arFilter = [];
$filterFields = [
	['id' => 'COMPETITION_ID', 'name' => 'Соревнование', 'type' => 'list', 'items' => ['' => 'Не выбрано'] + $competitions],
	['id' => 'COMPETITION_CATEGORY', 'name' => 'Категория', 'type' => 'list', 'items' => ['' => 'Не выбрано'] + $offerCategories],
	['id' => 'USER_ID', 'name' => 'Клиент', 'type' => 'custom_entity', 'selector' => ['type' => 'user']],
	['id' => 'PAYMENT_DATE', 'name' => 'Дата оплаты', 'type' => 'date'],
];

$lAdmin->AddFilter($filterFields, $arFilter);

$lAdmin->AddHeaders([
	['id' => 'ORDER_ID', 'content' => 'Заказ', 'sort' => 'ORDER_ID', 'default' => true],
	['id' => 'USER', 'content' => 'Покупатель', 'sort' => 'USER_ID', 'default' => true],
	['id' => 'PHONE', 'content' => 'Телефон', 'sort' => '', 'default' => true],
	['id' => 'CITY', 'content' => 'Город', 'sort' => '', 'default' => true],
	['id' => 'PAYMENT_DATE', 'content' => 'Дата оплаты', 'sort' => 'PAYMENT_DATE', 'default' => true],
	['id' => 'COMPETITION', 'content' => 'Соревнование', 'sort' => 'COMPETITION_NAME', 'default' => true],
	['id' => 'CATEGORY', 'content' => 'Категория', 'sort' => 'COMPETITION_CATEGORY', 'default' => true],
	['id' => 'PRICE', 'content' => 'Цена', 'sort' => 'PRICE', 'default' => true],
]);

$selectedFields = [
	'ID',
	'ORDER_ID',
	'PRICE',
	'CURRENCY',
	'PAYMENT_DATE' => 'ORDER.DATE_PAYED',
	'PRODUCT_ID',
];

$grid_options = new Bitrix\Main\Grid\Options('registration_list');
$sort = $grid_options->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);

$arFilter['=ORDER.PAYED'] = 'Y';
$arFilter['=ORDER.STATUS_ID'] = 'F';

$selectedFields['USER_ID'] = 'ORDER.USER.ID';
$selectedFields['USER_XML_ID'] = 'ORDER.USER.XML_ID';
$selectedFields['COMPETITION_ID'] = 'COMPETITION.ID';
$selectedFields['COMPETITION_NAME'] = 'COMPETITION.NAME';
$selectedFields['COMPETITION_CODE'] = 'COMPETITION.CODE';
$selectedFields['COMPETITION_CATEGORY'] = 'OFFER.PROPERTY.CATEGORY';

$getList = [
	'select' => $selectedFields,
	'filter' => $arFilter,
];

$query = new \CAdminUiResult(null, 'registration_list');
$query->setNavParams('registration_list', \Bitrix\Sale\Internals\BasketTable::class, $getList);

$getList['order'] = $sort['sort'];

$items = \Bitrix\Sale\Internals\BasketTable::query()
	->setOrder($getList['order'])
	->setSelect($getList['select'])
	->setFilter($getList['filter'])
	->setOffset($getList['offset'])
	->setLimit($getList['limit'])
	->registerRuntimeField((new Reference('OFFER', CompetitionsPriceTable::class, Join::on('this.PRODUCT_ID', 'ref.ID')))
		->configureJoinType('inner'))
	->registerRuntimeField((new Reference('COMPETITION', CompetitionsTable::class, Join::on('this.OFFER.PROPERTY.CML2_LINK', 'ref.ID')))
		->configureJoinType('inner'))
	->exec();

$query->NavStart();

$lAdmin->SetNavigationParams($query, []);

foreach ($items as $item)
{
	$row = $lAdmin->AddRow($item['ID'], $item);

	$row->AddViewField('ORDER_ID', '<a href="/bitrix/admin/sale_order_view.php?ID='.$item['ORDER_ID'].'&lang='.LANGUAGE_ID.'">'.$item['ORDER_ID'].'</a>');
	$row->AddViewField('PRICE', FormatCurrency($item['PRICE'], $item['CURRENCY']));
	$row->AddViewField('COMPETITION', '<a href="/competitions/'.$item['COMPETITION_CODE'].'/" target="_blank">'.$item['COMPETITION_NAME'].'</a>');
	$row->AddViewField('CATEGORY', $offerCategories[$item['COMPETITION_CATEGORY']] ?? '');

	/** @var UserProfilesTable $profile */
	$profile = UserProfilesTable::query()
		->setSelect(['id', 'FirstName', 'LastName', 'MiddleName', 'Phone', 'City'])
		->setFilter(['UserId' => $item['USER_XML_ID']])
		->exec()->fetch();

	$row->AddViewField('USER', '<a href="/bitrix/admin/user_edit.php?ID='.$item['USER_ID'].'&lang='.LANGUAGE_ID.'">'.$profile->FirstName.' '.$profile->MiddleName.' '.$profile->LastName.'</a>');
	$row->AddViewField('PHONE', $profile->Phone);
	$row->AddViewField('CITY', $profile->City);
}

$lAdmin->CheckListMode();
$lAdmin->AddAdminContextMenu([]);

$APPLICATION->SetTitle('Регистрации на соревнования');

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(['ACTION_PANEL' => true]);

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");