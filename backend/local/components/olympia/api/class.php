<?php

use Bitrix\Main\Localization\Loc;
use Olympia\Fssmo\Api\Application;

class COlympiaApiComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}

	public function onIncludeComponentLang()
	{
		Loc::loadMessages(__FILE__);
	}

	public function executeComponent()
	{
		//if (in_array($_SERVER['HTTP_ORIGIN'], ['http://localhost:4444', 'https://fssmo.oly-d.ru/']))
		{
			header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
			header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Access-Control-Allow-Credentials, Origin, X-Requested-With, Content-Type, Accept");
			header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
			header("Access-Control-Allow-Credentials: true");
		}

		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
			die();

		Application::run();
	}
}