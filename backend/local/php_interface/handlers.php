<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2018 Olympia.Digital
 */

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Sale\Registry;
use Olympia\Fssmo;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Olympia\Fssmo\Admin\Property\CompetitionGroup', 'GetUserTypeDescription']);
$eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Olympia\Fssmo\Admin\Property\Shooter', 'GetIBlockPropertyDescription']);
$eventManager->addEventHandler('main', 'OnUserTypeBuildList', ['\Olympia\Fssmo\Admin\Property\Shooter', 'GetUserTypeDescription']);
$eventManager->addEventHandler("main", "OnAfterResizeImage", 'onAfterResizeImageHandler');
$eventManager->addEventHandler("main", "OnProlog", "onProlog");
$eventManager->addEventHandler("main", "OnPageStart", 'onPageStart');
$eventManager->addEventHandler('main', 'OnBuildGlobalMenu', 'onBuildMenuHandler', false, 1000);
$eventManager->addEventHandler('sale', 'OnSaleOrderPaid', ['Olympia\Fssmo\Competition\Order', 'onSaleOrderPaidHandler']);
$eventManager->addEventHandler('sale', 'OnBeforeSaleBasketItemEntityDeleted', ['Olympia\Fssmo\Competition\Order', 'onBeforeSaleBasketItemEntityDeletedHandler']);
$eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', ['Olympia\Fssmo\Competition\Order', 'onSaleOrderBeforeSavedHandler']);
$eventManager->addEventHandler('sale', 'OnSaleOrderCanceled', ['Olympia\Fssmo\Competition\Order', 'onSaleOrderCanceledHandler']);
$eventManager->addEventHandler('sale', 'OnSalePsServiceProcessRequestAfterPaid', ['Olympia\Fssmo\Competition\Order', 'OnSalePsServiceProcessRequestAfterPaidHandler']);

function onBuildMenuHandler (&$aGlobalMenu, &$aModuleMenu)
{
	$deletePages = [
		'menu_stickers',
		'menu_rating',
		'menu_smile',
		'sale_crm',
		'menu_marketplace',
		'sale_personalization',
		'menu_security_ddos',
		'menu_promo_https',
		'update_system_market',
		'global_menu_crm_site_master',
	];

	foreach ($aGlobalMenu as $k => $v)
	{
		if (in_array($v['items_id'], $deletePages))
			unset($aGlobalMenu[$k]);
	}

	foreach ($aModuleMenu as $k => $v)
	{
		if (in_array($v['items_id'], $deletePages))
			unset($aModuleMenu[$k]);
	}
}

function onAfterResizeImageHandler (/** @noinspection PhpUnusedParameterInspection */
	$file, $params, $callbackData, $cacheImageFile, $cacheImageFileTmp, $arImageSize)
{
	$factory = new ImageOptimizer\OptimizerFactory([
		'jpegoptim_options' => array('--strip-all', '--all-progressive', '--max=85'),
	]);

	$optimizer = $factory->get();

	$optimizer->optimize($_SERVER['DOCUMENT_ROOT'].$cacheImageFile);

	return true;
}

function onProlog ()
{
	if (defined('ADMIN_SECTION'))
		Asset::getInstance()->addString('<meta name="format-detection" content="telephone=no">');
}

function onPageStart ()
{
	Loader::includeModule('sale');

	$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
	$registry->set(Registry::ENTITY_ORDER, Fssmo\Sale\Order::class);
}

Fssmo\User\Handlers::registerHandlers();
Fssmo\Competition\Handlers::registerHandlers();
