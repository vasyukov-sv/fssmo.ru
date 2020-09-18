<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2018 Olympia.Digital
 */

if (strpos($_SERVER['HTTP_HOST'], 'oly-d.ru') !== false)
{
	define('TEST_MODE', true);

	function custom_mail (/** @noinspection PhpUnusedParameterInspection */
		$to, $subject, $message, $additional_headers = "", $additional_parameters = "")
	{
		$to = 'ab@olympia.digital';

		if ($additional_parameters != "")
			return @mail($to, $subject, $message, $additional_headers, $additional_parameters);

		return @mail($to, $subject, $message, $additional_headers);
	}
}

error_reporting(E_ERROR);

ini_set("log_errors", 1);
ini_set("display_errors", 1);
ini_set("error_log", $_SERVER['DOCUMENT_ROOT'].'/php_errors.log');

define('IBLOCK_NEWS', 1);
define('IBLOCK_STRUCTURE', 2);
define('IBLOCK_COMPETITIONS', 3);
define('IBLOCK_COMPETITIONS_PRICE', 13);
define('IBLOCK_SERVICES', 14);
define('IBLOCK_DISCIPLINES', 4);
define('IBLOCK_FORM_FEEDBACK', 5);
define('IBLOCK_WINNERS', 6);
define('IBLOCK_SLIDER', 7);
define('IBLOCK_SPONSORS', 8);
define('IBLOCK_SITE_DISCIPLINES', 9);
define('IBLOCK_FORM_ENTER', 10);
define('IBLOCK_JUDGES', 11);
define('IBLOCK_CALENDAR', 12);

define('DEBUG_LOG', 'local/_debug.log');

Bitrix\Main\Loader::includeModule('iblock');
Bitrix\Main\Loader::includeModule('olympia.fssmo');
Bitrix\Main\Loader::includeModule('olympia.bitrix');

// подключение сторонних библиотек
include_once($_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php');
// подключение событий
include_once($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/handlers.php');