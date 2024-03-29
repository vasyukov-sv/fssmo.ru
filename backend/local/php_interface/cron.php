<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(1800);
@ignore_user_abort(true);

CAgent::CheckAgents();
CEvent::CheckEvents();

define("BX_CRONTAB", true);
define("BX_CRONTAB_SUPPORT", true);

if(CModule::IncludeModule('sender'))
{
	\Bitrix\Sender\MailingManager::checkPeriod(false);
	\Bitrix\Sender\MailingManager::checkSend();
}

CMain::FinalActions();