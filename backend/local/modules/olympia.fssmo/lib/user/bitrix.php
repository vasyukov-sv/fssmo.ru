<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Fssmo\User;

use COption;
use Olympia\Fssmo\Exception;

class Bitrix
{
	public static function sendPassword($LOGIN, $EMAIL, $SITE_ID = false, $captcha_word = "", $captcha_sid = 0)
	{
		/** @global \CMain $APPLICATION */
		global $DB, $APPLICATION;

		$arParams = [
			"LOGIN" => $LOGIN,
			"EMAIL" => $EMAIL,
			"SITE_ID" => $SITE_ID
		];

		$APPLICATION->ResetException();

		try
		{
			foreach (GetModuleEvents("main", "OnBeforeUserSendPassword", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$arParams)) === false)
				{
					if ($err = $APPLICATION->GetException())
						throw new Exception($err->GetString());
				}
			}

			if (COption::GetOptionString("main", "captcha_restoring_password", "N") == "Y")
			{
				if (!($APPLICATION->CaptchaCheckCode($captcha_word, $captcha_sid)))
					throw new Exception(GetMessage("main_user_captcha_error"));
			}

			if ($arParams["LOGIN"] == '' && $arParams["EMAIL"] == '')
				throw new Exception(GetMessage('DATA_NOT_FOUND'));

			$confirmation = (COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y");

			$strSql = "";

			if ($arParams["LOGIN"] <> '')
			{
				$strSql = "SELECT ID, LID, ACTIVE, CONFIRM_CODE, LOGIN, EMAIL, NAME, LAST_NAME, LANGUAGE_ID ".
					"FROM b_user u ".
					"WHERE LOGIN='".$DB->ForSQL($arParams["LOGIN"])."' AND (ACTIVE='Y' OR NOT(CONFIRM_CODE IS NULL OR CONFIRM_CODE=''))";
			}

			if ($arParams["EMAIL"] <> '')
			{
				if ($strSql <> '')
					$strSql .= "\nUNION\n";

				$strSql .= "SELECT ID, LID, ACTIVE, CONFIRM_CODE, LOGIN, EMAIL, NAME, LAST_NAME, LANGUAGE_ID ".
					"FROM b_user u ".
					"WHERE EMAIL='".$DB->ForSQL($arParams["EMAIL"])."' AND (ACTIVE='Y' OR NOT(CONFIRM_CODE IS NULL OR CONFIRM_CODE=''))";
			}

			$res = $DB->Query($strSql);

			if ($arUser = $res->Fetch())
			{
				if ($arParams["SITE_ID"] === false)
				{
					if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
						$arParams["SITE_ID"] = \CSite::GetDefSite($arUser["LID"]);
					else
						$arParams["SITE_ID"] = SITE_ID;
				}

				if ($arUser["ACTIVE"] == "Y")
					self::sendUserInfo($arUser["ID"], $arParams["SITE_ID"], GetMessage("INFO_REQ"), true, 'USER_PASS_REQUEST');
				elseif ($confirmation)
				{
					$arFields = [
						"USER_ID" => $arUser["ID"],
						"LOGIN" => $arUser["LOGIN"],
						"EMAIL" => $arUser["EMAIL"],
						"NAME" => $arUser["NAME"],
						"LAST_NAME" => $arUser["LAST_NAME"],
						"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
						"USER_IP" => $_SERVER["REMOTE_ADDR"],
						"USER_HOST" => @gethostbyaddr($_SERVER["REMOTE_ADDR"]),
					];

					$event = new \CEvent;
					$event->SendImmediate("NEW_USER_CONFIRM", $arParams["SITE_ID"], $arFields, "Y", "", array(), $arUser["LANGUAGE_ID"]);

					return [
						"MESSAGE" => GetMessage('MAIN_SEND_PASS_CONFIRM')."<br>",
						"TYPE" => "OK"
					];
				}
				else
					throw new Exception(GetMessage('DATA_NOT_FOUND'));

				if (COption::GetOptionString("main", "event_log_password_request", "N") === "Y")
					\CEventLog::Log("SECURITY", "USER_INFO", "main", $arUser["ID"]);
			}
			else
				throw new Exception(GetMessage('DATA_NOT_FOUND'));
		}
		catch (Exception $e)
		{
			return [
				"MESSAGE" => $e->getMessage()."<br>",
				"TYPE" => "ERROR"
			];
		}

		return [
			"MESSAGE" => GetMessage('ACCOUNT_INFO_SENT')."<br>",
			"TYPE" => "OK"
		];
	}

	public static function sendUserInfo($ID, $SITE_ID, $MSG, $bImmediate = false, $eventName = "USER_INFO")
	{
		global $DB;

		$ID = intval($ID);
		$salt = randString(8);
		$checkword = md5(\CMain::GetServerUniqID().uniqid());

		$strSql = "UPDATE b_user SET ".
			"	CHECKWORD = '".$salt.md5($salt.$checkword)."', ".
			"	CHECKWORD_TIME = ".$DB->CurrentTimeFunction().", ".
			"	LID = '".$DB->ForSql($SITE_ID, 2)."', ".
			"   TIMESTAMP_X = TIMESTAMP_X ".
			"WHERE ID = '".$ID."'";

		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$res = $DB->Query("SELECT u.* FROM b_user u WHERE ID = '".$ID."'");

		if ($res_array = $res->Fetch())
		{
			$event = new \CEvent;

			$arFields = [
				"USER_ID"=>$res_array["ID"],
				"STATUS"=>($res_array["ACTIVE"]=="Y"?GetMessage("STATUS_ACTIVE"):GetMessage("STATUS_BLOCKED")),
				"MESSAGE"=>$MSG,
				"LOGIN"=>$res_array["LOGIN"],
				"URL_LOGIN"=>urlencode($res_array["LOGIN"]),
				"CHECKWORD"=>$checkword,
				"NAME"=>$res_array["NAME"],
				"LAST_NAME"=>$res_array["LAST_NAME"],
				"EMAIL"=>$res_array["EMAIL"]
			];

			$arParams = [
				"FIELDS" => &$arFields,
				"USER_FIELDS" => $res_array,
				"SITE_ID" => &$SITE_ID,
				"EVENT_NAME" => &$eventName,
			];

			foreach (GetModuleEvents("main", "OnSendUserInfo", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arParams));

			if (!$bImmediate)
				$event->Send($eventName, $SITE_ID, $arFields);
			else
				$event->SendImmediate($eventName, $SITE_ID, $arFields);
		}
	}

	public static function changePassword($LOGIN, $CHECKWORD, $PASSWORD, $CONFIRM_PASSWORD, $SITE_ID = false, $captcha_word = "", $captcha_sid = 0)
	{
		/** @global \CMain $APPLICATION */
		global $DB, $APPLICATION;

		$arParams = [
			"LOGIN" => &$LOGIN,
			"CHECKWORD" => &$CHECKWORD,
			"PASSWORD" => &$PASSWORD,
			"CONFIRM_PASSWORD" => &$CONFIRM_PASSWORD,
			"SITE_ID" => &$SITE_ID
		];

		$APPLICATION->ResetException();

		try
		{
			foreach (GetModuleEvents("main", "OnBeforeUserChangePassword", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
				{
					if ($err = $APPLICATION->GetException())
						throw new Exception($err->GetString());
				}
			}

			if (COption::GetOptionString("main", "captcha_restoring_password", "N") == "Y")
			{
				if (!($APPLICATION->CaptchaCheckCode($captcha_word, $captcha_sid)))
					throw new Exception(GetMessage("main_user_captcha_error"));
			}

			if (strlen($arParams["LOGIN"]) < 3)
				throw new Exception(GetMessage('MIN_LOGIN'));

			if ($arParams["PASSWORD"] <> $arParams["CONFIRM_PASSWORD"])
				throw new Exception(GetMessage('WRONG_CONFIRMATION'));

			\CTimeZone::Disable();
			$db_check = $DB->Query(
				"SELECT ID, LID, CHECKWORD, ".$DB->DateToCharFunction("CHECKWORD_TIME", "FULL")." as CHECKWORD_TIME ".
				"FROM b_user ".
				"WHERE LOGIN='".$DB->ForSql($arParams["LOGIN"], 0)."'");
			\CTimeZone::Enable();

			if (!($res = $db_check->Fetch()))
				throw new Exception(preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage('LOGIN_NOT_FOUND')));

			$salt = substr($res["CHECKWORD"], 0, 8);

			if ($res["CHECKWORD"] == '' || $res["CHECKWORD"] != $salt.md5($salt.$arParams["CHECKWORD"]))
				throw new Exception(preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage("CHECKWORD_INCORRECT")));

			$arPolicy = \CUser::GetGroupPolicy($res["ID"]);

			$passwordErrors = \CUser::CheckPasswordAgainstPolicy($arParams["PASSWORD"], $arPolicy);

			if (!empty($passwordErrors))
				throw new Exception(implode("<br>", $passwordErrors));

			$site_format = \CSite::GetDateFormat();

			if (time() - $arPolicy["CHECKWORD_TIMEOUT"] * 60 > MakeTimeStamp($res["CHECKWORD_TIME"], $site_format))
				throw new Exception(preg_replace("/#LOGIN#/i", htmlspecialcharsbx($arParams["LOGIN"]), GetMessage("CHECKWORD_EXPIRE")));

			if ($arParams["SITE_ID"] === false)
			{
				if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
					$arParams["SITE_ID"] = \CSite::GetDefSite($res["LID"]);
				else
					$arParams["SITE_ID"] = SITE_ID;
			}

			$ID = $res["ID"];
			$obUser = new \CUser;

			$res = $obUser->Update($ID, ["PASSWORD" => $arParams["PASSWORD"]]);

			if (!$res && $obUser->LAST_ERROR <> '')
				throw new Exception($obUser->LAST_ERROR);

			self::sendUserInfo($ID, $arParams["SITE_ID"], GetMessage('CHANGE_PASS_SUCC'), true, 'USER_PASS_CHANGED');
		}
		catch (Exception $e)
		{
			return [
				"MESSAGE" => $e->getMessage()."<br>",
				"TYPE" => "ERROR"
			];
		}

		return [
			"MESSAGE" => GetMessage('PASSWORD_CHANGE_OK')."<br>",
			"TYPE" => "OK"
		];
	}

	public static function onUserLoginExternalHandler (&$arParams)
	{
		global $DB, $APPLICATION;

		$strSql =
			"SELECT U.ID, U.LOGIN, U.ACTIVE, U.PASSWORD, U.LOGIN_ATTEMPTS, U.CONFIRM_CODE, U.EMAIL ".
			"FROM b_user U  ".
			"WHERE U.LOGIN='".$DB->ForSQL($arParams["LOGIN"])."'";

		$result = $DB->Query($strSql);

		if ($arUser = $result->Fetch())
		{
			if (strlen($arUser["PASSWORD"]) > 32)
			{
				$salt = substr($arUser["PASSWORD"], 0, strlen($arUser["PASSWORD"]) - 32);
				$db_password = substr($arUser["PASSWORD"], -32);
			}
			else
			{
				$salt = "";
				$db_password = $arUser["PASSWORD"];
			}

			$user_password_no_otp = "";

			if ($arParams["PASSWORD_ORIGINAL"] == "Y")
			{
				$user_password =  md5($salt.$arParams["PASSWORD"]);

				if ($arParams["OTP"] <> '')
					$user_password_no_otp =  md5($salt.substr($arParams["PASSWORD"], 0, -6));
			}
			else
			{
				if (strlen($arParams["PASSWORD"]) > 32)
					$user_password = substr($arParams["PASSWORD"], -32);
				else
					$user_password = $arParams["PASSWORD"];
			}

			$passwordCorrect = ($db_password === $user_password || ($arParams["OTP"] <> '' && $db_password === $user_password_no_otp));

			if ($db_password === $user_password)
				$arParams["OTP"] = '';

			$arPolicy = \CUser::GetGroupPolicy($arUser["ID"]);

			$pol_login_attempts = intval($arPolicy["LOGIN_ATTEMPTS"]);
			$usr_login_attempts = intval($arUser["LOGIN_ATTEMPTS"]) + 1;

			if ($pol_login_attempts > 0 && $usr_login_attempts > $pol_login_attempts)
			{
				$_SESSION["BX_LOGIN_NEED_CAPTCHA"] = true;

				if (!$APPLICATION->CaptchaCheckCode($_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]))
					$passwordCorrect = false;
			}

			if ($passwordCorrect)
			{
				if ($salt == '' && $arParams["PASSWORD_ORIGINAL"] == "Y")
				{
					$salt = randString(8, array(
						"abcdefghijklnmopqrstuvwxyz",
						"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
						"0123456789",
						",.<>/?;:[]{}\\|~!@#\$%^&*()-_+=",
					));
					$new_password = $salt.md5($salt.$arParams["PASSWORD"]);

					$DB->Query("UPDATE b_user SET PASSWORD='".$DB->ForSQL($new_password)."', TIMESTAMP_X = TIMESTAMP_X WHERE ID = ".intval($arUser["ID"]));
				}

				if ($arUser["ACTIVE"] == "Y")
					return (int) $arUser["ID"];
				elseif($arUser["CONFIRM_CODE"] <> '')
					$APPLICATION->ThrowException(GetMessage("MAIN_LOGIN_EMAIL_CONFIRM", array("#EMAIL#" => $arUser["EMAIL"])));
				else
					$APPLICATION->ThrowException(GetMessage("LOGIN_BLOCK"));
			}
			else
			{
				$DB->Query("UPDATE b_user SET LOGIN_ATTEMPTS = ".$usr_login_attempts.", TIMESTAMP_X = TIMESTAMP_X WHERE ID = ".intval($arUser["ID"]));

				$APPLICATION->ThrowException(GetMessage("WRONG_LOGIN"));
			}
		}

		return 0;
	}
}