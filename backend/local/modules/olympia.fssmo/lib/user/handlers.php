<?php

namespace Olympia\Fssmo\User;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\EventManager;
use Bitrix\Main\UserTable;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Helpers;
use Olympia\Fssmo\User;

class Handlers
{
	public static function registerHandlers ()
	{
		$eventManager = EventManager::getInstance();

		/**
		 * Ищем во внешней БД пользователя, если он найден, то проставляем корректный xml_id для связи сайта c внешней бд
		 * Для социальной сети пробуем продублировать email в login
		 */
		$eventManager->addEventHandler('main', 'OnBeforeUserAdd', [__CLASS__, 'onBeforeUserAddHandler']);
		/**
		 * После создания пользователя на сайте нужно создать пользователя во внешней бд
		 */
		$eventManager->addEventHandler('main', 'OnAfterUserAdd', [__CLASS__, 'onAfterUserAddHandler']);
		/**
		 * Обновление информации о пользователе во внешней бд
		 */
		$eventManager->addEventHandler('main', 'OnBeforeUserUpdate', [__CLASS__, 'onBeforeUserUpdateHandler']);
		$eventManager->addEventHandler('main', 'OnAfterUserUpdate', [__CLASS__, 'onAfterUserUpdateHandler']);
		/**
		 * Удалим пользователя из внешней бд при удалении его с сайта
		 */
		//$eventManager->addEventHandler('main', 'OnBeforeUserDelete', [__CLASS__, 'OnBeforeUserDeleteHandler']);
		/**
		 * Пробуем склеить одинаковых пользователей из социальных сетей по email
		 */
		$eventManager->addEventHandler("socialservices", "OnFindSocialservicesUser", [__CLASS__, 'onFindSocialservicesUserHandler']);

		$eventManager->addEventHandler("main", "OnUserLoginExternal", ['\Olympia\Fssmo\User\Bitrix', 'onUserLoginExternalHandler']);
	}

	public static function onBeforeUserAddHandler (&$arFields)
	{
		if (defined('SKIP_USER_ADD_CALLBACK'))
			return true;

		/** @var External\UsersTable $checkExist */
		$checkExist = External\UsersTable::query()
			->setSelect(['UserId'])
			->setFilter(['UserName' => $arFields['EMAIL']])
			->exec()->fetch();

		if ($checkExist)
			$arFields['XML_ID'] = $checkExist->UserId;

		if (filter_var($arFields['EMAIL'], FILTER_VALIDATE_EMAIL) && !filter_var($arFields['LOGIN'], FILTER_VALIDATE_EMAIL))
			$arFields['LOGIN'] = $arFields['EMAIL'];

		return true;
	}

	public static function onAfterUserAddHandler ($arFields)
	{
		if (defined('SKIP_USER_ADD_CALLBACK'))
			return;

		if (!$arFields['ID'])
			return;

		if (isset($arFields['XML_ID']) && Helpers::isGUID($arFields['XML_ID']))
			return;

		try {
			User::createExternalUser($arFields);
		}
		catch (\Exception $e) {
			Debug::writeToFile($e->getMessage(), __CLASS__.'::onAfterUserAddHandler('.print_r($arFields, true).')', DEBUG_LOG);
		}
	}

	public static function onBeforeUserUpdateHandler ($arFields)
	{
		if (defined('SKIP_USER_UPDATE_CALLBACK'))
			return true;

		$check = UserTable::getRow([
			'select' => ['ID'],
			'filter' => ['=LOGIN' => $arFields['LOGIN'], '!ID' => $arFields['ID']]
		]);

		if ($check)
		{
			global $APPLICATION;

			$APPLICATION->throwException('Пользователь с таким Логином/Email уже существует');

			return false;
		}

		return true;
	}

	public static function onAfterUserUpdateHandler ($arFields)
	{
		if ($arFields['RESULT'])
		{
			if (defined('SKIP_USER_UPDATE_CALLBACK'))
				return true;

			$userData = UserTable::query()
				->setSelect(['ID', 'XML_ID', 'EMAIL', 'NAME', 'LAST_NAME', 'PERSONAL_PHONE', 'PERSONAL_CITY', 'UF_DIGIT_ID', 'UF_CLUB_ID'])
				->setFilter(['ID' => $arFields['ID']])
				->exec()->fetch();

			try {
				User::updateExternalUser($userData['XML_ID'], $userData);
			}
			catch (\Exception $e) {
				Debug::writeToFile($e->getMessage(), __CLASS__.'::onBeforeUserUpdateHandler('.print_r($arFields, true).')', DEBUG_LOG);
			}
		}

		return true;
	}

	public static function OnBeforeUserDeleteHandler ($userId)
	{
		if (defined('SKIP_USER_DELETE_CALLBACK'))
			return;

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['ID' => $userId])
			->exec()->fetch();

		try {
			User::deleteExternalUser($user['XML_ID']);
		}
		catch (\Exception $e) {
			Debug::writeToFile($e->getMessage(), __CLASS__.'::OnBeforeUserDeleteHandler('.$userId.')', DEBUG_LOG);
		}
	}

	public static function onFindSocialservicesUserHandler ($fields)
	{
		if (isset($fields['EMAIL']) && $fields['EMAIL'] != '')
		{
			$checkExist = UserTable::getRow([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y', '=LID' => SITE_ID, '=EMAIL' => trim($fields['EMAIL'])]
			]);

			if ($checkExist)
				return (int) $checkExist['ID'];
		}

		return 0;
	}
}